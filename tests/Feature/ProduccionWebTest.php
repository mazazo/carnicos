<?php

namespace Tests\Feature;

use App\Livewire\Produccion\Index;
use App\Models\Animal;
use App\Models\AnimalType;
use App\Models\CutCatalog;
use App\Models\Desposte;
use App\Models\PrecioCorte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreaCarnicerias;
use Tests\TestCase;

/** Producción desde la web: misma lógica y mismos despostes pendientes que la app. */
class ProduccionWebTest extends TestCase
{
    use CreaCarnicerias;
    use RefreshDatabase;

    private AnimalType $vacuno;

    protected function setUp(): void
    {
        parent::setUp();
        [$this->vacuno] = $this->tiposDeAnimal();
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

    private function corte(string $nombre): CutCatalog
    {
        return CutCatalog::withoutGlobalScopes()->create(['carniceria_id' => null, 'animal_type_id' => $this->vacuno->id, 'nombre_canonico' => $nombre, 'activo' => true]);
    }

    public function test_desposte_completo_desde_la_web(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        $m1 = $this->media($dueno, 100, 4000);
        $m2 = $this->media($dueno, 110, 4200);
        $asado = $this->corte('Asado');
        $vacio = $this->corte('Vacío');
        $this->actingAs($dueno);

        $this->get('/dashboard/enterprise')->assertOk()->assertSee(route('produccion', $this->vacuno->id));

        $lw = Livewire::test(Index::class, ['tipo' => $this->vacuno->id])
            ->assertSee('Ingresos disponibles')
            ->call('alternarMedia', $m1->id)
            ->call('alternarMedia', $m2->id)
            ->call('iniciar')
            ->assertHasNoErrors();

        $id = $lw->get('desposteId');
        $this->assertSame(Animal::EN_DESPOSTE, $m1->fresh()->estado);

        // Cada valor queda guardado en el sistema al salir del campo.
        $lw->set("pesos.{$asado->id}", '40,5')
            ->set("pesos.{$vacio->id}", '20')
            ->set("precios.{$asado->id}", '9.000')
            ->set("pesos.{$vacio->id}", '19,5')
            ->assertHasNoErrors()
            ->assertSee('Desposte pendiente #'.$id);

        $desposte = Desposte::withoutGlobalScopes()->findOrFail($id);
        $this->assertEqualsCanonicalizing([40.5, 19.5], $desposte->pesadas->map(fn ($p) => (float) $p->peso)->all());
        $this->assertSame('9000.00', PrecioCorte::withoutGlobalScopes()->sole()->precio_kg);

        $lw->call('terminar')->assertHasNoErrors()->assertSee('Desposte guardado')->assertSee('$ 364.500');

        $this->assertSame(Desposte::TERMINADO, $desposte->fresh()->estado);
        $this->assertSame(Animal::DESPOSTADA, $m2->fresh()->estado);
        $this->assertSame(2, $desposte->cortes()->count());
    }

    public function test_terminar_y_generar_el_pdf(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3', 'Doña Rosa');
        $media = $this->media($dueno, 100, 4000);
        $asado = $this->corte('Asado');
        $this->actingAs($dueno);

        $lw = Livewire::test(Index::class, ['tipo' => $this->vacuno->id])
            ->call('alternarMedia', $media->id)->call('iniciar')
            ->set("pesos.{$asado->id}", '35,5');
        $id = $lw->get('desposteId');

        // Pendiente: todavía no hay PDF.
        $this->get(route('produccion.pdf', $id))->assertNotFound();

        $lw->call('terminarConPdf')->assertHasNoErrors()->assertFileDownloaded('desposte-vacuno-'.$id.'.pdf');
        $this->assertSame(Desposte::TERMINADO, Desposte::withoutGlobalScopes()->find($id)->estado);

        $respuesta = $this->get(route('produccion.pdf', $id))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
        $this->assertStringContainsString('attachment; filename="desposte-vacuno-'.$id.'.pdf"', $respuesta->headers->get('Content-Disposition'));

        [, $ajeno] = $this->carniceriaCon('plan-3', 'Ajena');
        $this->actingAs($ajeno)->get(route('produccion.pdf', $id))->assertNotFound();
    }

    public function test_el_dashboard_muestra_producciones_pendientes_y_terminadas(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        $asado = $this->corte('Asado');
        $this->actingAs($dueno);
        $servicio = app(\App\Services\Despostes::class);

        $terminado = $servicio->iniciar($dueno, $this->vacuno->id, [$this->media($dueno, 100, 4000)->id]);
        $servicio->fijarPesoCorte($dueno, $terminado, $asado->id, 70);
        $servicio->terminar($terminado);
        $pendiente = $servicio->iniciar($dueno, $this->vacuno->id, [$this->media($dueno, 110, 4200)->id]);

        $this->get('/dashboard/enterprise')->assertOk()
            ->assertSee('Producciones')
            ->assertSee(route('produccion', ['tipo' => $this->vacuno->id, 'desposte' => $pendiente->id]), false)
            ->assertSee(route('produccion.pdf', $terminado->id), false)
            ->assertSee('rinde 70,0%')
            ->assertSee('En desposte');
    }

    public function test_seguir_y_descartar_pendientes_y_el_empleado_no_cambia_precios(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        $media = $this->media($dueno, 100, 4000);
        $asado = $this->corte('Asado');
        $empleado = User::factory()->create(['carniceria_id' => $dueno->carniceria_id, 'rol' => User::ROL_EMPLEADO]);

        $this->actingAs($dueno);
        $id = Livewire::test(Index::class, ['tipo' => $this->vacuno->id])
            ->call('alternarMedia', $media->id)->call('iniciar')
            ->set("pesos.{$asado->id}", '12')->get('desposteId');

        // El empleado lo retoma desde la lista de pendientes.
        $this->actingAs($empleado);
        Livewire::test(Index::class, ['tipo' => $this->vacuno->id])
            ->assertSee('Despostes pendientes')
            ->call('seguir', $id)
            ->assertSet("pesos.{$asado->id}", '12')
            ->assertDontSeeHtml('wire:model.blur="precios.')
            ->set("precios.{$asado->id}", '1')
            ->assertForbidden();

        Livewire::test(Index::class, ['tipo' => $this->vacuno->id])->call('descartar', $id);
        $this->assertNull(Desposte::withoutGlobalScopes()->find($id));
        $this->assertSame(Animal::DISPONIBLE, $media->fresh()->estado);
    }

    public function test_no_se_entra_a_tipos_fuera_del_plan_ni_a_despostes_ajenos(): void
    {
        [, $basico] = $this->carniceriaCon('plan-1');
        [, $dueno] = $this->carniceriaCon('plan-3', 'Otra');
        $media = $this->media($dueno, 100, 4000);
        $this->actingAs($dueno);
        $id = Livewire::test(Index::class, ['tipo' => $this->vacuno->id])->call('alternarMedia', $media->id)->call('iniciar')->get('desposteId');

        [, $ajeno] = $this->carniceriaCon('plan-3', 'Ajena');
        $this->actingAs($ajeno);
        Livewire::test(Index::class, ['tipo' => $this->vacuno->id])->call('descartar', $id)->assertNotFound();
        $this->assertNotNull(Desposte::withoutGlobalScopes()->find($id));

        $tipos = array_map('intval', $basico->carniceria->tiposAnimalHabilitadosIds());
        $fuera = AnimalType::query()->whereNotIn('id', $tipos)->value('id');
        if ($fuera !== null) {
            $this->actingAs($basico)->get('/produccion/'.$fuera)->assertRedirect(route('cuenta.tipos-animal'));
        }
    }
}
