<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\AnimalType;
use App\Models\CutCatalog;
use App\Models\Desposte;
use App\Models\User;
use App\Support\CortesPrimarios;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Concerns\CreaCarnicerias;
use Tests\TestCase;

/** API de la app: piezas grandes (ingreso, desposte por piezas y cuarteo), sin romper lo que ya usaba. */
class ApiPiezasTest extends TestCase
{
    use CreaCarnicerias;
    use RefreshDatabase;

    private AnimalType $vacuno;

    private User $dueno;

    protected function setUp(): void
    {
        parent::setUp();
        [$this->vacuno] = $this->tiposDeAnimal();
        foreach (['nalga', 'peceto', 'asado', 'vacío'] as $musculo) {
            CutCatalog::withoutGlobalScopes()->create(['carniceria_id' => null, 'animal_type_id' => $this->vacuno->id, 'nombre_canonico' => $musculo, 'activo' => true]);
        }
        CortesPrimarios::instalar();
        [, $this->dueno] = $this->carniceriaCon('plan-3');
        Sanctum::actingAs($this->dueno);
    }

    private function id(string $nombre): int
    {
        return CutCatalog::withoutGlobalScopes()->whereNull('carniceria_id')->where('nombre_canonico', $nombre)->value('id');
    }

    public function test_tipos_trae_las_piezas_con_sus_musculos(): void
    {
        $vacuno = collect($this->getJson('/api/tipos-animal')->assertOk()->json('data'))->firstWhere('nombre', 'vacuno');

        $mocho = collect($vacuno['piezas'])->firstWhere('nombre', 'Mocho');
        $this->assertNotNull($mocho);
        $this->assertEqualsCanonicalizing([$this->id('nalga'), $this->id('peceto')], $mocho['musculos']);
        $this->assertSame(0, $vacuno['piezas_disponibles']);
        $this->assertContains('Cuarto trasero', collect($vacuno['piezas'])->pluck('nombre'));
    }

    public function test_ingreso_de_pieza_y_cuarteo_desde_la_app(): void
    {
        // Ingreso de un Mocho comprado.
        $this->postJson('/api/animales', ['animal_type_id' => $this->vacuno->id, 'formato' => 'pieza', 'peso_total' => 50, 'precio_kg' => 5000])
            ->assertUnprocessable()->assertJsonValidationErrors('cut_catalog_id');
        $this->postJson('/api/animales', ['animal_type_id' => $this->vacuno->id, 'formato' => 'pieza', 'cut_catalog_id' => $this->id('nalga'), 'peso_total' => 50, 'precio_kg' => 5000])
            ->assertUnprocessable()->assertJsonValidationErrors('cut_catalog_id');
        $pieza = $this->postJson('/api/animales', [
            'animal_type_id' => $this->vacuno->id, 'formato' => 'pieza', 'cut_catalog_id' => $this->id('Mocho'),
            'peso_total' => 50, 'precio_kg' => 5000,
        ])->assertCreated()->assertJsonPath('data.formato_etiqueta', 'Mocho')->json('data.id');

        // Las medias siguen por separado; las piezas, con piezas=1.
        $this->getJson('/api/animales?animal_type_id='.$this->vacuno->id)->assertJsonCount(0, 'data');
        $this->getJson('/api/animales?animal_type_id='.$this->vacuno->id.'&piezas=1')->assertJsonCount(1, 'data')->assertJsonPath('data.0.cut_catalog_id', $this->id('Mocho'));
        $this->assertSame(1, collect($this->getJson('/api/tipos-animal')->json('data'))->firstWhere('nombre', 'vacuno')['piezas_disponibles']);

        // Cuarteo: músculos por pesadas, como siempre.
        $id = $this->postJson('/api/producciones/pendientes', ['animal_type_id' => $this->vacuno->id, 'animal_ids' => [$pieza], 'modo' => 'cuarteo'])
            ->assertCreated()->assertJsonPath('data.modo', 'cuarteo')->assertJsonPath('data.piezas.0', 'Mocho')->json('data.id');
        $this->postJson("/api/producciones/$id/pesadas", ['cut_catalog_id' => $this->id('nalga'), 'peso' => 30])->assertCreated();
        $this->postJson("/api/producciones/$id/pesadas", ['cut_catalog_id' => $this->id('Mocho'), 'peso' => 1])->assertUnprocessable();
        $this->postJson("/api/producciones/$id/terminar")->assertOk()
            ->assertJsonPath('data.costo_total', 250000)
            ->assertJsonPath('data.peso_cortes', 30);

        $this->assertSame(Animal::DESPOSTADA, Animal::withoutGlobalScopes()->find($pieza)->estado);
    }

    public function test_desposte_por_piezas_desde_la_app(): void
    {
        $media = Animal::withoutGlobalScopes()->create([
            'carniceria_id' => $this->dueno->carniceria_id, 'user_id' => $this->dueno->id, 'animal_type_id' => $this->vacuno->id,
            'formato' => Animal::FORMATO_MEDIA_RES, 'cantidad' => 1, 'estado' => Animal::DISPONIBLE,
            'peso_total' => 100, 'precio_kg' => 4000, 'fecha' => today(),
        ]);

        $this->getJson('/api/cortes?animal_type_id='.$this->vacuno->id.'&nivel=primario')->assertOk()
            ->assertJsonFragment(['nombre' => 'Cuarto delantero']);
        $this->assertNotContains('Mocho', collect($this->getJson('/api/cortes?animal_type_id='.$this->vacuno->id)->json('data'))->pluck('nombre'));

        $id = $this->postJson('/api/producciones/pendientes', ['animal_type_id' => $this->vacuno->id, 'animal_ids' => [$media->id], 'modo' => 'primario'])
            ->assertCreated()->json('data.id');
        $this->postJson("/api/producciones/$id/pesadas", ['cut_catalog_id' => $this->id('nalga'), 'peso' => 5])->assertUnprocessable();
        $this->postJson("/api/producciones/$id/pesadas", ['cut_catalog_id' => $this->id('Cuarto delantero'), 'peso' => 40])->assertCreated();
        $this->postJson("/api/producciones/$id/pesadas", ['cut_catalog_id' => $this->id('Cuarto trasero'), 'peso' => 40])->assertCreated();
        $this->postJson("/api/producciones/$id/terminar")->assertOk()->assertJsonPath('data.modo', 'primario');

        // Las dos piezas quedan disponibles a $5.000/kg ($400.000 / 80 kg).
        $this->getJson('/api/animales?animal_type_id='.$this->vacuno->id.'&piezas=1')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.precio_kg', 5000);
        $this->assertSame(Desposte::MODO_PRIMARIO, Desposte::withoutGlobalScopes()->find($id)->modo);
    }
}
