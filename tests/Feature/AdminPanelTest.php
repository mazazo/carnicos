<?php

namespace Tests\Feature;

use App\Livewire\Admin\CarniceriaShow;
use App\Livewire\Admin\Planes;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreaCarnicerias;
use Tests\TestCase;

/** Etapa 5: panel del administrador (carnicerías, planes, pagos). */
class AdminPanelTest extends TestCase
{
    use CreaCarnicerias;
    use RefreshDatabase;

    public function test_solo_el_admin_entra_al_panel(): void
    {
        [$carniceria, $dueno] = $this->carniceriaCon('plan-3');
        $rutas = [
            route('admin.carnicerias.index'),
            route('admin.carnicerias.show', $carniceria),
            route('admin.planes'),
            route('admin.pagos'),
        ];

        foreach ($rutas as $ruta) {
            $this->actingAs($dueno)->get($ruta)->assertForbidden();
        }

        $admin = $this->admin();
        foreach ($rutas as $ruta) {
            $this->actingAs($admin)->get($ruta)->assertOk();
        }

        $this->get(route('admin.carnicerias.index'))->assertSee('Don Juan');
        $this->get(route('admin.config.index'))->assertSee('Carnicerias')->assertSee('Planes y precios');
    }

    public function test_el_listado_filtra_por_estado(): void
    {
        $this->carniceriaCon('prueba', 'En Prueba');
        $this->carniceriaCon('plan-2', 'Pagando');
        $this->carniceriaCon(null, 'Vencida');

        $this->actingAs($this->admin());
        $this->get(route('admin.carnicerias.index', ['estado' => 'sin_acceso']))->assertSee('Vencida')->assertDontSee('Pagando');
        $this->get(route('admin.carnicerias.index', ['estado' => 'prueba']))->assertSee('En Prueba')->assertDontSee('Vencida');
    }

    public function test_el_admin_suma_dias_y_habilita_un_plan_a_mano(): void
    {
        [$carniceria] = $this->carniceriaCon('prueba');
        $this->actingAs($this->admin());

        Livewire::test(CarniceriaShow::class, ['carniceria' => $carniceria])
            ->set('dias', 10)
            ->call('sumarDias')
            ->assertHasNoErrors();
        $this->assertSame(15, $carniceria->suscripcionVigente()->diasRestantes());

        Livewire::test(CarniceriaShow::class, ['carniceria' => $carniceria])
            ->set('planId', $this->plan('plan-3')->id)
            ->set('planMeses', 0)
            ->set('planDias', 30)
            ->call('habilitarPlan')
            ->assertHasNoErrors();

        $vigente = $carniceria->suscripcionVigente();
        $this->assertSame('plan-3', $vigente->planModel->codigo);
        $this->assertSame('manual', $vigente->origen);
        $this->assertSame(30, $vigente->diasRestantes());
        $this->assertSame(Subscription::CANCELLED, $carniceria->suscripciones()->where('origen', 'prueba')->sole()->status);
        $this->assertSame(3, $carniceria->movimientos()->count()); // prueba, días, plan
    }

    public function test_el_admin_registra_un_pago_recibido(): void
    {
        [$carniceria] = $this->carniceriaCon(null);
        $this->actingAs($admin = $this->admin());

        Livewire::test(CarniceriaShow::class, ['carniceria' => $carniceria])
            ->set('pagoPlanId', $this->plan('plan-1')->id)
            ->set('pagoMeses', 3)
            ->set('pagoMonto', '45000')
            ->set('pagoMetodo', 'efectivo')
            ->call('registrarPago')
            ->assertHasNoErrors();

        $payment = Payment::query()->sole();
        $this->assertTrue($payment->isPaid());
        $this->assertSame($admin->id, $payment->registrado_por);
        $this->assertTrue($carniceria->suscripcionVigente()->ends_at->isSameDay(now()->addMonthsNoOverflow(3)));
    }

    public function test_el_admin_suspende_y_reactiva(): void
    {
        [$carniceria] = $this->carniceriaCon('plan-2');
        $this->actingAs($this->admin());

        Livewire::test(CarniceriaShow::class, ['carniceria' => $carniceria])->call('suspender')->assertHasErrors('motivo');
        Livewire::test(CarniceriaShow::class, ['carniceria' => $carniceria])->set('motivo', 'deuda')->call('suspender');
        $this->assertFalse($carniceria->fresh()->estaActiva());

        Livewire::test(CarniceriaShow::class, ['carniceria' => $carniceria])->call('reactivar');
        $this->assertTrue($carniceria->fresh()->estaActiva());
    }

    public function test_el_admin_edita_un_plan_y_sus_precios(): void
    {
        $plan = $this->plan('plan-2');
        $this->actingAs($this->admin());

        Livewire::test(Planes::class)
            ->call('editar', $plan->id)
            ->set('nombre', 'Profesional Plus')
            ->set('max_usuarios', 3)
            ->set('caracteristicas', "Soporte por WhatsApp\n\nCapacitación")
            ->set('precios.0.precio', '22000')
            ->call('agregarPrecio')
            ->set('precios.1.meses', 6)
            ->set('precios.1.precio', '120000')
            ->call('guardar')
            ->assertHasNoErrors();

        $plan->refresh()->load('precios');
        $this->assertSame('Profesional Plus', $plan->nombre);
        $this->assertSame(3, $plan->max_usuarios);
        $this->assertSame(['Soporte por WhatsApp', 'Capacitación'], $plan->caracteristicas);
        $this->assertSame([1 => '22000.00', 6 => '120000.00'], $plan->precios->pluck('precio', 'meses')->all());

        [, $dueno] = $this->carniceriaCon('prueba');
        $this->actingAs($dueno)->get(route('billing.plans'))->assertSee('Profesional Plus')->assertSee('$22.000');
    }

    public function test_los_meses_repetidos_no_se_guardan(): void
    {
        $plan = $this->plan('plan-1');
        $this->actingAs($this->admin());

        Livewire::test(Planes::class)
            ->call('editar', $plan->id)
            ->call('agregarPrecio')
            ->set('precios.1.meses', 1)
            ->set('precios.1.precio', '1')
            ->call('guardar')
            ->assertHasErrors('precios.1.meses');
    }
}
