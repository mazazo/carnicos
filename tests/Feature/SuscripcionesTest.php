<?php

namespace Tests\Feature;

use App\Livewire\Auth\Register;
use App\Livewire\Cuenta\TiposAnimal;
use App\Livewire\Cuenta\Usuarios;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Suscripciones;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreaCarnicerias;
use Tests\TestCase;

/**
 * Etapa 3: prueba gratis, bloqueo total al vencer, límites del plan
 * (tipos de animal, usuarios, dashboard) y vencimiento automático.
 */
class SuscripcionesTest extends TestCase
{
    use CreaCarnicerias;
    use RefreshDatabase;

    public function test_al_registrarse_recibe_la_prueba_gratis_del_plan_1(): void
    {
        Livewire::test(Register::class)
            ->set('carniceria', 'Don Juan')
            ->set('name', 'Juan')
            ->set('email', 'juan@example.test')
            ->set('movil', '1155551234')
            ->set('password', 'Clave-Segura1')
            ->set('password_confirmation', 'Clave-Segura1')
            ->call('register')
            ->assertHasNoErrors();

        $vigente = User::query()->where('email', 'juan@example.test')->sole()->carniceria->suscripcionVigente();

        $this->assertNotNull($vigente);
        $this->assertTrue($vigente->isTrial());
        $this->assertSame('plan-1', $vigente->planModel->codigo);
        $this->assertSame(5, $vigente->diasRestantes());
        $this->assertDatabaseHas('suscripcion_movimientos', ['carniceria_id' => $vigente->carniceria_id, 'tipo' => 'prueba']);
    }

    public function test_al_vencer_la_prueba_se_bloquea_todo_menos_los_planes(): void
    {
        $this->tiposDeAnimal();
        [$carniceria, $dueno] = $this->carniceriaCon('prueba');
        $carniceria->tiposAnimal()->sync([$this->tiposDeAnimal()[0]->id]);

        $this->actingAs($dueno)->get(route('cuenta.usuarios'))->assertOk();

        $this->travel(6)->days();

        $this->get(route('cuenta.usuarios'))->assertRedirect(route('billing.plans'));
        $this->get(route('animals.index'))->assertRedirect(route('billing.plans'));
        $this->get(route('billing.plans'))->assertOk()->assertSee('Tu prueba o plan vencio');
        $this->assertSame('billing.plans', $dueno->fresh()->dashboardRouteName());
    }

    public function test_sumar_dias_extiende_la_prueba_y_reabre_una_vencida(): void
    {
        [$carniceria] = $this->carniceriaCon('prueba');
        $servicio = app(Suscripciones::class);

        $servicio->sumarDias($carniceria, 10);
        $this->assertSame(15, $carniceria->suscripcionVigente()->diasRestantes());

        $this->travel(20)->days();
        $this->assertNull($carniceria->suscripcionVigente());

        $servicio->sumarDias($carniceria, 3);
        $vigente = $carniceria->suscripcionVigente();
        $this->assertTrue($vigente->isTrial());
        $this->assertSame(3, $vigente->diasRestantes());
        $this->assertSame(1, $carniceria->suscripciones()->count()); // reabre la misma
    }

    public function test_el_plan_1_obliga_a_elegir_un_solo_tipo_de_animal(): void
    {
        [$vacuno, $porcino] = $this->tiposDeAnimal();
        [$carniceria, $dueno] = $this->carniceriaCon('plan-1');

        $this->actingAs($dueno)->get(route('cuenta.usuarios'))->assertRedirect(route('cuenta.tipos-animal'));

        Livewire::test(TiposAnimal::class)
            ->set('seleccion', [(string) $vacuno->id, (string) $porcino->id])
            ->call('guardar')
            ->assertHasErrors('seleccion');

        Livewire::test(TiposAnimal::class)
            ->set('seleccion', [(string) $porcino->id])
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard.pro'));

        $this->assertSame([$porcino->id], $carniceria->tiposAnimalHabilitadosIds());
        $this->get(route('cuenta.usuarios'))->assertOk();
    }

    public function test_el_plan_3_habilita_todos_los_tipos_sin_elegir(): void
    {
        $tipos = $this->tiposDeAnimal();
        [$carniceria] = $this->carniceriaCon('plan-3');

        $this->assertFalse($carniceria->debeElegirTiposAnimal());
        $this->assertEqualsCanonicalizing(collect($tipos)->pluck('id')->all(), $carniceria->tiposAnimalHabilitadosIds());
    }

    public function test_los_usuarios_se_limitan_segun_el_plan(): void
    {
        [$carniceria, $dueno] = $this->carniceriaCon('plan-1');
        $this->actingAs($dueno);

        // Plan 1: un solo usuario (el dueño).
        Livewire::test(Usuarios::class)->call('nuevo')->assertForbidden();

        app(Suscripciones::class)->activarPlan($carniceria, $this->plan('plan-2'), meses: 1, origen: 'manual');

        $crear = fn (string $email, string $movil) => Livewire::test(Usuarios::class)
            ->set('name', 'Empleado')
            ->set('email', $email)
            ->set('movil', $movil)
            ->set('password', 'Clave-Segura1')
            ->call('crear');

        $crear('empleado1@example.test', '1155550001')->assertHasNoErrors();
        $crear('empleado2@example.test', '1155550002')->assertHasErrors('email'); // plan 2: 2 usuarios

        $empleado = User::query()->where('email', 'empleado1@example.test')->sole();
        $this->assertSame($carniceria->id, $empleado->carniceria_id);
        $this->assertFalse($empleado->esDueno());
        $this->assertNotNull($empleado->email_verified_at);
    }

    public function test_el_empleado_no_administra_usuarios(): void
    {
        [$carniceria] = $this->carniceriaCon('plan-3');
        $empleado = User::factory()->create(['carniceria_id' => $carniceria->id, 'rol' => User::ROL_EMPLEADO]);

        $this->actingAs($empleado);
        Livewire::test(Usuarios::class)->call('nuevo')->assertForbidden();
    }

    public function test_el_dashboard_completo_es_solo_del_plan_3(): void
    {
        [, $basico] = $this->carniceriaCon('plan-2', 'Uno');
        [, $completo] = $this->carniceriaCon('plan-3', 'Dos');

        $this->assertSame('dashboard.pro', $basico->dashboardRouteName());
        $this->assertSame('dashboard.enterprise', $completo->dashboardRouteName());
    }

    public function test_una_carniceria_suspendida_queda_bloqueada(): void
    {
        $this->tiposDeAnimal();
        [$carniceria, $dueno] = $this->carniceriaCon('plan-3');
        app(Suscripciones::class)->suspender($carniceria, 'falta de pago');

        $this->actingAs($dueno)->get(route('cuenta.usuarios'))->assertRedirect(route('billing.plans'));
    }

    public function test_el_comando_marca_vencidas_las_suscripciones(): void
    {
        [$carniceria] = $this->carniceriaCon('prueba');
        [$otra] = $this->carniceriaCon('plan-2', 'Otra');

        $this->travel(6)->days();
        $this->artisan('suscripciones:vencer')->assertSuccessful();

        $this->assertSame(Subscription::EXPIRED, $carniceria->suscripciones()->first()->status);
        $this->assertSame(Subscription::ACTIVE, $otra->suscripciones()->first()->status);
    }
}
