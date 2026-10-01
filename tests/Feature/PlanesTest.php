<?php

namespace Tests\Feature;

use App\Models\Carniceria;
use App\Models\Plan;
use App\Models\User;
use App\Support\Billing\PlanCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Etapa 2: planes por funciones y tiempo, en base de datos.
 */
class PlanesTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        $carniceria = Carniceria::query()->create(['nombre' => 'Don Juan']);

        return User::factory()->create(['carniceria_id' => $carniceria->id, 'rol' => User::ROL_DUENO]);
    }

    public function test_la_migracion_carga_los_tres_planes_con_su_precio_mensual(): void
    {
        $planes = Plan::query()->activos()->with('precioMensual')->get();

        $this->assertSame(['plan-1', 'plan-2', 'plan-3'], $planes->pluck('codigo')->all());
        $this->assertSame([15000, 20000, 25000], $planes->map(fn (Plan $p) => (int) $p->precioMensual->precio)->all());
        $this->assertSame([1, 2, 3], $planes->pluck('max_tipos_animal')->all());
        $this->assertSame([1, 2, 4], $planes->pluck('max_usuarios')->all());
        $this->assertSame([false, false, true], $planes->pluck('incluye_dashboard')->all());
        $this->assertSame([false, false, true], $planes->pluck('incluye_app')->all());
    }

    public function test_los_textos_salen_de_los_limites_del_plan(): void
    {
        [$basico, , $completo] = Plan::query()->activos()->get()->all();

        $this->assertContains('1 tipo de animal a elección (vacuno, porcino o aviar)', $basico->incluye());
        $this->assertContains('1 usuario', $basico->incluye());
        $this->assertSame(['Dashboard con estadísticas', 'App Android'], $basico->noIncluye());

        $this->assertContains('Vacuno, porcino y aviar', $completo->incluye());
        $this->assertContains('Hasta 4 usuarios', $completo->incluye());
        $this->assertContains('App Android', $completo->incluye());
        $this->assertSame([], $completo->noIncluye());

        $this->assertFalse($basico->permiteUsuarios(1));
        $this->assertTrue($completo->permiteUsuarios(3));
        $this->assertFalse($completo->permiteUsuarios(4));
    }

    public function test_la_pagina_de_planes_y_el_checkout_leen_la_base(): void
    {
        Plan::query()->where('codigo', 'plan-2')->update(['nombre' => 'Profesional Plus']);
        Plan::query()->where('codigo', 'plan-1')->update(['activo' => false]);

        $this->assertSame(['plan-2', 'plan-3'], array_column(PlanCatalog::all(), 'id'));
        $this->assertNull(PlanCatalog::find('plan-1'));

        $this->actingAs($this->usuario());
        $this->get(route('billing.plans'))->assertOk()->assertSee('Profesional Plus')->assertSee('$20.000')->assertDontSee('Básico');
        $this->get(route('billing.checkout', 'plan-3'))->assertOk()->assertSee('Completo');
        $this->get(route('billing.checkout', 'plan-1'))->assertNotFound();
        $this->get(route('billing.checkout', 'no-existe'))->assertNotFound();
    }
}
