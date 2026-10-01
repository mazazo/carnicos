<?php

namespace Tests\Feature;

use App\Livewire\Cuts\Index;
use App\Models\AnimalType;
use App\Models\CutCatalog;
use App\Models\Desposte;
use App\Models\DesposteCorte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreaCarnicerias;
use Tests\TestCase;

/** Cortes por carnicería: ocultar generales, cortes propios y lo que ve la app. */
class CatalogoCortesTest extends TestCase
{
    use CreaCarnicerias;
    use RefreshDatabase;

    private AnimalType $vacuno;

    private AnimalType $porcino;

    protected function setUp(): void
    {
        parent::setUp();
        [$this->vacuno, $this->porcino] = $this->tiposDeAnimal();
    }

    private function general(string $nombre, ?AnimalType $tipo = null): CutCatalog
    {
        return CutCatalog::withoutGlobalScopes()->create([
            'carniceria_id' => null,
            'animal_type_id' => ($tipo ?? $this->vacuno)->id,
            'nombre_canonico' => $nombre,
            'activo' => true,
        ]);
    }

    /** @return array<int, string> */
    private function cortesEnLaApp(User $user): array
    {
        Sanctum::actingAs($user);

        return collect($this->getJson('/api/cortes?animal_type_id='.$this->vacuno->id)->assertOk()->json('data'))->pluck('nombre')->all();
    }

    public function test_el_dueno_deshabilita_un_corte_general_solo_para_su_carniceria(): void
    {
        $asado = $this->general('Asado');
        $this->general('Vacío');
        [, $dueno] = $this->carniceriaCon('plan-3');
        [, $otro] = $this->carniceriaCon('plan-3', 'Otra');

        $this->actingAs($dueno);
        Livewire::test(Index::class)
            ->assertSet('tipoId', $this->vacuno->id)
            ->assertSeeHtml('2 de 2<span class="hidden sm:inline"> cortes habilitados')
            ->call('alternar', $asado->id)
            ->assertSeeHtml('1 de 2<span class="hidden sm:inline"> cortes habilitados');

        $this->assertSame(['Vacío'], $this->cortesEnLaApp($dueno));
        $this->assertSame(['Asado', 'Vacío'], $this->cortesEnLaApp($otro));
        $this->assertTrue($asado->fresh()->activo, 'El corte general no se modifica.');

        $this->actingAs($dueno);
        Livewire::test(Index::class)->call('alternar', $asado->id);
        $this->assertSame(['Asado', 'Vacío'], $this->cortesEnLaApp($dueno));
    }

    public function test_agregar_un_corte_propio_y_no_duplicar(): void
    {
        $vacio = $this->general('Vacío');
        [, $dueno] = $this->carniceriaCon('plan-3');
        [, $otro] = $this->carniceriaCon('plan-3', 'Otra');
        $this->actingAs($dueno);

        Livewire::test(Index::class)
            ->set('nuevoNombre', '  Tapa   de asado ')
            ->call('agregar')
            ->assertHasNoErrors()
            ->assertSee('Tapa de asado')
            ->set('nuevoNombre', 'vacío')
            ->call('agregar')
            ->assertHasErrors('nuevoNombre')
            ->call('alternar', $vacio->id)
            ->set('nuevoNombre', 'VACÍO')
            ->call('agregar')
            ->assertHasNoErrors();

        $this->assertSame(1, CutCatalog::withoutGlobalScopes()->where('nombre_canonico', 'like', 'vac%')->count(), 'Vacío se volvió a habilitar, no se duplicó.');
        $this->assertSame(['Tapa de asado', 'Vacío'], $this->cortesEnLaApp($dueno));
        $this->assertSame(['Vacío'], $this->cortesEnLaApp($otro));
    }

    public function test_renombrar_y_borrar_cortes_propios(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        $this->actingAs($dueno);
        $propio = CutCatalog::agregarPropio($dueno->carniceria_id, $this->vacuno->id, 'Chingolo', $dueno->id);
        $usado = CutCatalog::agregarPropio($dueno->carniceria_id, $this->vacuno->id, 'Palomita', $dueno->id);
        $desposte = Desposte::withoutGlobalScopes()->create(['carniceria_id' => $dueno->carniceria_id, 'animal_type_id' => $this->vacuno->id, 'fecha_desposte' => today()]);
        DesposteCorte::query()->create(['desposte_id' => $desposte->id, 'cut_catalog_id' => $usado->id, 'nombre' => 'Palomita', 'peso' => 3]);

        Livewire::test(Index::class)
            ->call('editar', $propio->id)
            ->set('editandoNombre', 'Chingolo especial')
            ->call('guardarNombre')
            ->assertHasNoErrors()
            ->call('eliminar', $usado->id)
            ->assertSee('quedó deshabilitado');

        $this->assertSame('Chingolo especial', $propio->fresh()->nombre_canonico);
        $this->assertFalse($usado->fresh()->activo);

        Livewire::test(Index::class)->call('eliminar', $propio->id);
        $this->assertNull(CutCatalog::withoutGlobalScopes()->find($propio->id));
    }

    public function test_el_empleado_ve_pero_no_cambia_y_no_toca_cortes_ajenos(): void
    {
        $asado = $this->general('Asado');
        [, $dueno] = $this->carniceriaCon('plan-3');
        [, $otro] = $this->carniceriaCon('plan-3', 'Otra');
        $ajeno = CutCatalog::agregarPropio($otro->carniceria_id, $this->vacuno->id, 'Secreto', $otro->id);
        $empleado = User::factory()->create(['carniceria_id' => $dueno->carniceria_id, 'rol' => User::ROL_EMPLEADO]);

        $this->actingAs($empleado);
        Livewire::test(Index::class)->assertSee('Asado')->assertDontSee('Secreto')->call('alternar', $asado->id)->assertForbidden();

        $this->actingAs($dueno);
        Livewire::test(Index::class)->call('alternar', $ajeno->id)->assertNotFound();
        $this->assertTrue($ajeno->fresh()->activo);
    }

    public function test_la_pagina_de_cortes_se_renderiza(): void
    {
        $this->general('Asado');
        $this->general('Bondiola', $this->porcino);
        [, $dueno] = $this->carniceriaCon('plan-3');

        $this->actingAs($dueno)->get('/cuts')->assertOk()->assertSee('Agregar corte')->assertSee('Asado');
        $this->actingAs($dueno)->get('/cuts?tipo='.$this->porcino->id)->assertOk()->assertSee('Bondiola')->assertDontSee('Asado');
    }
}
