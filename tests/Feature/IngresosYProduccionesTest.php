<?php

namespace Tests\Feature;

use App\Livewire\Ingresos\Index as Ingresos;
use App\Livewire\Producciones\Index as Producciones;
use App\Models\Animal;
use App\Models\AnimalType;
use App\Models\CutCatalog;
use App\Models\Desposte;
use App\Models\User;
use App\Services\Despostes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreaCarnicerias;
use Tests\TestCase;

/** Páginas web de Ingresos y Producciones, y el cálculo guardado al terminar. */
class IngresosYProduccionesTest extends TestCase
{
    use CreaCarnicerias;
    use RefreshDatabase;

    private AnimalType $vacuno;

    private AnimalType $aviar;

    protected function setUp(): void
    {
        parent::setUp();
        [$this->vacuno, , $this->aviar] = $this->tiposDeAnimal();
        $this->aviar->update(['modalidad' => 'cajon']);
    }

    public function test_cargar_un_ingreso_desde_la_web(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        $this->actingAs($dueno);

        $this->get('/ingresos')->assertOk()->assertSee('Nuevo ingreso');

        Livewire::test(Ingresos::class)
            ->call('abrirNuevo')
            ->set('tipoId', $this->vacuno->id)
            ->set('formato', Animal::FORMATO_MEDIA_RES)
            ->set('peso', '112,5')
            ->set('precio', '4.200')
            ->set('proveedor', 'Frigorífico Sur')
            ->assertSee('$ 472.500')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Frigorífico Sur')
            ->assertSee('Disponible');

        $animal = Animal::withoutGlobalScopes()->sole();
        $this->assertSame($dueno->carniceria_id, $animal->carniceria_id);
        $this->assertSame(Animal::DISPONIBLE, $animal->estado);
        $this->assertSame('112.500', $animal->peso_total);
        $this->assertSame('4200.00', $animal->precio_kg);

        // El avícola solo acepta cajones.
        Livewire::test(Ingresos::class)
            ->set('tipoId', $this->aviar->id)
            ->assertSet('formato', Animal::FORMATO_CAJON)
            ->set('formato', Animal::FORMATO_RES)
            ->set('peso', '20')->set('precio', '3000')
            ->call('guardar')
            ->assertHasErrors('formato');

        // Sin kg ni precio no se guarda.
        Livewire::test(Ingresos::class)->set('tipoId', $this->vacuno->id)->call('guardar')->assertHasErrors(['peso', 'precio']);
    }

    public function test_muestra_hace_cuantos_dias_esta_cada_ingreso_en_stock(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        $this->actingAs($dueno);
        $vieja = $this->media($dueno, 100, 4000);
        $vieja->update(['fecha' => today()->subDays(4)]);
        $nueva = $this->media($dueno, 90, 4000);
        $despostada = $this->media($dueno, 80, 4000);
        $despostada->update(['fecha' => today()->subDays(9), 'estado' => Animal::DESPOSTADA]);

        $this->assertSame(4, $vieja->fresh()->diasEnStock());
        $this->assertSame(0, $nueva->diasEnStock());

        Livewire::test(Ingresos::class)
            ->assertSee('4 días')
            ->assertSee('Hoy')
            ->assertDontSee('9 días');

        Livewire::test(\App\Livewire\Produccion\Index::class, ['tipo' => $this->vacuno->id])->assertSee('4 días');
        $this->get('/dashboard/enterprise')->assertSee('4 días');
    }

    public function test_solo_se_elimina_un_ingreso_que_no_se_uso(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        $this->actingAs($dueno);
        $libre = $this->media($dueno, 100, 4000);
        $usada = $this->media($dueno, 100, 4000);
        app(Despostes::class)->iniciar($dueno, $this->vacuno->id, [$usada->id]);

        Livewire::test(Ingresos::class)->call('eliminar', $usada->id)->assertForbidden();
        Livewire::test(Ingresos::class)->call('eliminar', $libre->id)->assertHasNoErrors();

        $this->assertNull(Animal::withoutGlobalScopes()->find($libre->id));
        $this->assertNotNull(Animal::withoutGlobalScopes()->find($usada->id));
    }

    public function test_el_calculo_queda_guardado_aunque_cambien_los_precios(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        $this->actingAs($dueno);
        $asado = CutCatalog::withoutGlobalScopes()->create(['carniceria_id' => null, 'animal_type_id' => $this->vacuno->id, 'nombre_canonico' => 'Asado', 'activo' => true]);
        $servicio = app(Despostes::class);
        $servicio->fijarPrecio($dueno, $asado->id, 9000);

        $d = $servicio->iniciar($dueno, $this->vacuno->id, [$this->media($dueno, 100, 4000)->id]);
        $servicio->fijarPesoCorte($dueno, $d, $asado->id, 70);
        $d = $servicio->terminar($d);

        $this->assertSame(100.0, $d->kg_medias);
        $this->assertSame(70.0, $d->kg_cortes);
        $this->assertSame(30.0, $d->kg_merma);
        $this->assertSame(70.0, $d->rinde_pct);
        $this->assertSame(400000.0, $d->costo);
        $this->assertSame(630000.0, $d->venta);
        $this->assertSame(230000.0, $d->ganancia);
        $this->assertSame(57.5, $d->margen_pct);

        // Un precio nuevo no cambia lo que ya se terminó.
        $servicio->fijarPrecio($dueno, $asado->id, 1);
        Livewire::test(Producciones::class)
            ->assertSee('$ 630.000')
            ->assertSee('$ 230.000')
            ->assertSee('57,5%')
            ->assertSee('70,0%')
            ->assertSee(route('produccion.pdf', $d->id));
    }

    public function test_el_listado_filtra_y_no_muestra_otras_carnicerias(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        [, $otro] = $this->carniceriaCon('plan-3', 'Otra');
        $servicio = app(Despostes::class);

        $this->actingAs($otro);
        $ajeno = $servicio->iniciar($otro, $this->vacuno->id, [$this->media($otro, 90, 1)->id]);

        $this->actingAs($dueno);
        $pendiente = $servicio->iniciar($dueno, $this->vacuno->id, [$this->media($dueno, 100, 4000)->id]);

        $this->get('/producciones')->assertOk()->assertSee('Producciones');

        Livewire::test(Producciones::class)
            ->assertDontSee('#'.$pendiente->id)
            ->set('filtroEstado', 'pendientes')
            ->assertSee('#'.$pendiente->id)
            ->assertSee('Seguir')
            ->assertDontSee('#'.$ajeno->id);
    }

    private function media(User $user, float $peso, float $precio): Animal
    {
        return Animal::withoutGlobalScopes()->create([
            'carniceria_id' => $user->carniceria_id,
            'user_id' => $user->id,
            'animal_type_id' => $this->vacuno->id,
            'formato' => Animal::FORMATO_MEDIA_RES,
            'cantidad' => 1,
            'estado' => Animal::DISPONIBLE,
            'peso_total' => $peso,
            'precio_kg' => $precio,
            'fecha' => today(),
        ]);
    }
}
