<?php

namespace Tests\Feature;

use App\Livewire\Ingresos\Index as Ingresos;
use App\Livewire\Produccion\Index as Produccion;
use App\Models\Animal;
use App\Models\AnimalType;
use App\Models\CutCatalog;
use App\Models\Desposte;
use App\Models\User;
use App\Services\Despostes;
use App\Support\CortesPrimarios;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreaCarnicerias;
use Tests\TestCase;

/** Cortes primarios (piezas grandes) del vacuno, desposte por piezas y cuarteo. */
class CortesPrimariosTest extends TestCase
{
    use CreaCarnicerias;
    use RefreshDatabase;

    private AnimalType $vacuno;

    protected function setUp(): void
    {
        parent::setUp();
        [$this->vacuno] = $this->tiposDeAnimal();
        foreach (['nalga', 'peceto', 'cuadril', 'asado', 'vacío', 'matambre'] as $musculo) {
            CutCatalog::withoutGlobalScopes()->create(['carniceria_id' => null, 'animal_type_id' => $this->vacuno->id, 'nombre_canonico' => $musculo, 'activo' => true]);
        }
        CortesPrimarios::instalar();
    }

    private function corte(string $nombre): CutCatalog
    {
        return CutCatalog::withoutGlobalScopes()->whereNull('carniceria_id')->where('nombre_canonico', $nombre)->sole();
    }

    private function media(User $user, float $peso, float $precio): Animal
    {
        return Animal::withoutGlobalScopes()->create([
            'carniceria_id' => $user->carniceria_id, 'user_id' => $user->id, 'animal_type_id' => $this->vacuno->id,
            'formato' => Animal::FORMATO_MEDIA_RES, 'cantidad' => 1, 'estado' => Animal::DISPONIBLE,
            'peso_total' => $peso, 'precio_kg' => $precio, 'fecha' => today(),
        ]);
    }

    public function test_las_piezas_grandes_se_instalan_con_sus_musculos_sin_duplicar(): void
    {
        CortesPrimarios::instalar();

        $this->assertSame(9, CutCatalog::withoutGlobalScopes()->where('nivel', CutCatalog::NIVEL_PRIMARIO)->where('animal_type_id', $this->vacuno->id)->count());
        $this->assertEqualsCanonicalizing(['nalga', 'peceto', 'cuadril'], $this->corte('Mocho')->partes->pluck('nombre_canonico')->all());
        $this->assertEqualsCanonicalizing(['asado', 'vacío', 'matambre'], $this->corte('Parrillero completo')->partes->pluck('nombre_canonico')->all());
    }

    public function test_desposte_por_piezas_y_cuarteo_de_una_pieza(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        $this->actingAs($dueno);
        $servicio = app(Despostes::class);
        $mocho = $this->corte('Mocho');
        $parrillero = $this->corte('Parrillero completo');

        // 1) Media de 100 kg a $4.000 → piezas grandes.
        $desposte = $servicio->iniciar($dueno, $this->vacuno->id, [$this->media($dueno, 100, 4000)->id], Desposte::MODO_PRIMARIO);
        $servicio->fijarPesoCorte($dueno, $desposte, $mocho->id, 50);
        $servicio->fijarPesoCorte($dueno, $desposte, $parrillero->id, 30);

        try {
            $servicio->fijarPesoCorte($dueno, $desposte, $this->corte('nalga')->id, 5);
            $this->fail('En un desposte por piezas no van músculos.');
        } catch (ValidationException) {
        }

        $servicio->terminar($desposte);

        // Las piezas quedan disponibles con el costo repartido: $400.000 / 80 kg = $5.000/kg.
        $piezas = Animal::query()->piezas()->disponibles()->orderBy('peso_total', 'desc')->get();
        $this->assertCount(2, $piezas);
        $this->assertSame([$mocho->id, $parrillero->id], $piezas->pluck('cut_catalog_id')->all());
        $this->assertSame('5000.00', $piezas[0]->precio_kg);
        $this->assertSame('Mocho', $piezas[0]->etiquetaFormato());
        $this->assertSame($desposte->id, $piezas[0]->desposte_origen_id);

        // La app no ve piezas ni cortes primarios.
        Sanctum::actingAs($dueno);
        $this->getJson('/api/animales?animal_type_id='.$this->vacuno->id)->assertJsonCount(0, 'data');
        $this->assertNotContains('Mocho', collect($this->getJson('/api/cortes?animal_type_id='.$this->vacuno->id)->json('data'))->pluck('nombre')->all());

        // 2) Cuarteo del Mocho: primero solo sus músculos.
        $this->actingAs($dueno);
        Livewire::test(Produccion::class, ['tipo' => $this->vacuno->id])
            ->assertSee('¿Cómo vas a despostar?')
            ->call('elegirModo', Desposte::MODO_CUARTEO)
            ->assertSee('Piezas grandes disponibles')
            ->call('alternarMedia', $piezas[0]->id)
            ->call('iniciar')
            ->assertHasNoErrors()
            ->assertSee('Cuarteo pendiente')
            ->assertSee('nalga')
            ->assertDontSee('asado')
            ->set('soloDeLaPieza', false)
            ->assertSee('asado')
            ->set('pesos.'.$this->corte('nalga')->id, '30')
            ->set('pesos.'.$this->corte('peceto')->id, '12')
            ->call('terminar')
            ->assertHasNoErrors()
            ->assertSee('Cuarteo guardado');

        $cuarteo = Desposte::query()->where('modo', Desposte::MODO_CUARTEO)->sole();
        $this->assertSame(250000.0, $cuarteo->costo); // 50 kg × $5.000
        $this->assertSame(84.0, $cuarteo->rinde_pct); // 42 de 50 kg
        $this->assertSame(Animal::DESPOSTADA, $piezas[0]->fresh()->estado);
        $this->assertSame(Animal::DISPONIBLE, $piezas[1]->fresh()->estado);
    }

    public function test_ingresar_una_pieza_grande_comprada(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        $this->actingAs($dueno);

        // Los cuartos y demás piezas están a la vista en el mismo desplegable de formato.
        Livewire::test(Ingresos::class)
            ->call('abrirNuevo')
            ->set('tipoId', $this->vacuno->id)
            ->assertSeeHtml('<optgroup label="Piezas grandes">')
            ->assertSee('Cuarto delantero')
            ->assertSee('Cuarto trasero')
            ->assertSee('Cuarto pistola')
            ->set('formato', Animal::FORMATO_PIEZA)
            ->set('peso', '48')->set('precio', '5.200')
            ->call('guardar')
            ->assertHasErrors('piezaId')
            ->set('formatoElegido', 'pieza:'.$this->corte('Mocho')->id)
            ->assertSet('formato', Animal::FORMATO_PIEZA)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Mocho');

        $pieza = Animal::withoutGlobalScopes()->sole();
        $this->assertTrue($pieza->esPieza());
        $this->assertSame($this->corte('Mocho')->id, $pieza->cut_catalog_id);

        // La pieza comprada se cuartea igual que una salida de un desposte por piezas.
        Livewire::test(Produccion::class, ['tipo' => $this->vacuno->id])
            ->assertSee('Cuarteo')
            ->call('elegirModo', Desposte::MODO_CUARTEO)
            ->assertSee('Mocho')
            ->assertSee('#'.$pieza->id)
            ->call('alternarMedia', $pieza->id)
            ->call('iniciar')
            ->assertHasNoErrors()
            ->set('pesos.'.$this->corte('nalga')->id, '20')
            ->set('pesos.'.$this->corte('cuadril')->id, '15,5')
            ->call('terminar')
            ->assertHasNoErrors();

        $cuarteo = Desposte::query()->where('modo', Desposte::MODO_CUARTEO)->sole();
        $this->assertSame(249600.0, $cuarteo->costo); // 48 kg × $5.200
        $this->assertSame(35.5, $cuarteo->kg_cortes);
        $this->assertSame(Animal::DESPOSTADA, $pieza->fresh()->estado);
    }

    public function test_el_dashboard_muestra_las_piezas_y_lleva_al_cuarteo(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        $this->actingAs($dueno);

        $this->get('/dashboard/enterprise')->assertOk()
            ->assertSee('Cuartos y piezas')
            ->assertSee(route('ingresos.index', ['nuevo' => 1]), false);

        foreach ([['Cuarto trasero', 95.5], ['Mocho', 52]] as [$pieza, $kg]) {
            Animal::query()->create([
                'animal_type_id' => $this->vacuno->id, 'user_id' => $dueno->id, 'formato' => Animal::FORMATO_PIEZA,
                'cut_catalog_id' => $this->corte($pieza)->id, 'cantidad' => 1, 'peso_total' => $kg, 'precio_kg' => 5000,
                'fecha' => today(), 'estado' => Animal::DISPONIBLE,
            ]);
        }

        $this->get('/dashboard/enterprise')->assertOk()
            ->assertSee('147,5 kg · Cuartear →')
            ->assertSee(route('produccion', ['tipo' => $this->vacuno->id, 'modo' => 'cuarteo']), false);
    }

    public function test_cada_animal_tiene_sus_piezas_grandes(): void
    {
        [, $porcino, $aviar] = $this->tiposDeAnimalExistentes();
        foreach (['pernil', 'bondiola', 'panceta'] as $m) {
            CutCatalog::withoutGlobalScopes()->create(['carniceria_id' => null, 'animal_type_id' => $porcino->id, 'nombre_canonico' => $m, 'activo' => true]);
        }
        CortesPrimarios::instalar();
        [, $dueno] = $this->carniceriaCon('plan-3');
        $this->actingAs($dueno);

        $this->assertEqualsCanonicalizing(['pernil'], CutCatalog::withoutGlobalScopes()->where('nombre_canonico', 'Pierna de cerdo')->sole()->partes->pluck('nombre_canonico')->all());

        Livewire::test(Ingresos::class)
            ->call('abrirNuevo')
            ->set('tipoId', $porcino->id)
            ->assertSee('Pierna de cerdo')
            ->assertSee('Costillar de cerdo')
            ->assertDontSee('Cuarto pistola')
            ->set('tipoId', $aviar->id)
            ->assertSee('Cuarto trasero de pollo')
            ->assertDontSee('Pierna de cerdo');
    }

    /** @return array<int, AnimalType> vacuno, porcino y aviar ya creados por tiposDeAnimal(). */
    private function tiposDeAnimalExistentes(): array
    {
        return [
            $this->vacuno,
            AnimalType::query()->where('nombre', 'porcino')->sole(),
            AnimalType::query()->where('nombre', 'aviar')->sole(),
        ];
    }

    public function test_sin_piezas_grandes_habilitadas_no_se_ofrece_el_formato_pieza(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        $this->actingAs($dueno);
        foreach (CutCatalog::withoutGlobalScopes()->where('nivel', CutCatalog::NIVEL_PRIMARIO)->get() as $pieza) {
            $pieza->habilitarPara($dueno->carniceria_id, false);
        }

        Livewire::test(Ingresos::class)
            ->set('tipoId', $this->vacuno->id)
            ->assertDontSee('Pieza grande')
            ->set('formato', Animal::FORMATO_PIEZA)
            ->set('peso', '10')->set('precio', '100')
            ->call('guardar')
            ->assertHasErrors('formato');
    }
}
