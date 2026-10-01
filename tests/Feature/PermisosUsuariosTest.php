<?php

namespace Tests\Feature;

use App\Livewire\Cuenta\Usuarios;
use App\Models\AnimalType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreaCarnicerias;
use Tests\TestCase;

/** Permisos de los empleados (ingresos, producciones, cortes, app) y lo que es solo del dueño. */
class PermisosUsuariosTest extends TestCase
{
    use CreaCarnicerias;
    use RefreshDatabase;

    private AnimalType $vacuno;

    protected function setUp(): void
    {
        parent::setUp();
        [$this->vacuno] = $this->tiposDeAnimal();
    }

    private function empleado(User $dueno, array $permisos): User
    {
        return User::factory()->create(['carniceria_id' => $dueno->carniceria_id, 'rol' => User::ROL_EMPLEADO, 'permisos' => $permisos]);
    }

    public function test_el_dueno_crea_un_empleado_con_permisos_y_los_cambia(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        $this->actingAs($dueno);

        Livewire::test(Usuarios::class)
            ->call('nuevo')
            ->assertSee('App del celular')
            ->set('name', 'Pedro')->set('email', 'pedro@carne.com')->set('movil', '1155554444')->set('password', 'Clave1234x')
            ->set('permisos', ['ingresos', 'app'])
            ->call('crear')
            ->assertHasNoErrors()
            ->assertSee('Ingresos');

        $pedro = User::query()->where('email', 'pedro@carne.com')->sole();
        $this->assertSame(User::ROL_EMPLEADO, $pedro->rol);
        $this->assertSame(['ingresos', 'app'], $pedro->permisos);
        $this->assertTrue($pedro->puede('ingresos'));
        $this->assertFalse($pedro->puede('producciones'));

        // El dueño le cambia los permisos: sin app se le cierra la sesión del celular.
        $pedro->createToken('app');
        Livewire::test(Usuarios::class)
            ->call('editarPermisos', $pedro->id)
            ->assertSet('permisosEditando', ['ingresos', 'app'])
            ->set('permisosEditando', ['producciones', 'cortes'])
            ->call('guardarPermisos')
            ->assertHasNoErrors();

        $pedro->refresh();
        $this->assertSame(['producciones', 'cortes'], $pedro->permisos);
        $this->assertSame(0, $pedro->tokens()->count());
    }

    public function test_la_web_respeta_los_permisos_del_empleado(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        $soloIngresos = $this->empleado($dueno, ['ingresos']);

        $this->actingAs($soloIngresos);
        $this->get('/ingresos')->assertOk();
        $this->get('/producciones')->assertForbidden();
        $this->get('/produccion/'.$this->vacuno->id)->assertForbidden();
        $this->get('/cuts')->assertForbidden();

        $inicio = $this->get('/dashboard/enterprise')->assertOk();
        $inicio->assertSee(route('ingresos.index'), false)
            ->assertDontSee(route('producciones.index'), false)
            ->assertDontSee(route('cuts.index'), false)
            ->assertDontSee('Carga rápida de desposte');

        // El dueño ve y usa todo.
        $this->actingAs($dueno);
        $this->get('/producciones')->assertOk();
        $this->get('/cuts')->assertOk();
    }

    public function test_usuarios_y_planes_son_solo_del_dueno(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        $empleado = $this->empleado($dueno, array_keys(User::PERMISOS));

        $this->actingAs($empleado);
        $this->get('/cuenta/usuarios')->assertForbidden();
        $this->get('/billing/planes')->assertOk()->assertSee('Los planes y los pagos los maneja el dueño')->assertDontSee('Elegí tu plan');
        $this->get('/billing/pasarela/plan-3')->assertForbidden();
        $this->get('/dashboard/enterprise')->assertOk()
            ->assertDontSee(route('cuenta.usuarios'), false)
            ->assertDontSee(route('billing.plans'), false);

        $this->actingAs($dueno);
        $this->get('/cuenta/usuarios')->assertOk();
        $this->get('/dashboard/enterprise')->assertSee(route('cuenta.usuarios'), false)->assertSee(route('billing.plans'), false);
    }

    public function test_la_app_respeta_los_permisos(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');

        // Sin permiso de app: bloqueado con su código (la app muestra el mensaje).
        Sanctum::actingAs($this->empleado($dueno, ['ingresos', 'producciones']));
        $this->getJson('/api/tipos-animal')->assertForbidden()->assertJsonPath('codigo', 'sin_permiso_app');
        $this->getJson('/api/me')->assertOk()->assertJsonPath('acceso.permitido', false)
            ->assertJsonPath('usuario.permisos', ['ingresos', 'producciones']);

        // Con app pero sin ingresos ni producciones: ve, pero no carga.
        Sanctum::actingAs($this->empleado($dueno, ['app']));
        $this->getJson('/api/tipos-animal')->assertOk();
        $this->getJson('/api/producciones')->assertOk();
        $this->postJson('/api/animales', ['animal_type_id' => $this->vacuno->id, 'formato' => 'media_res', 'peso_total' => 100, 'precio_kg' => 4000])
            ->assertForbidden()->assertJsonMissingPath('codigo');
        $this->postJson('/api/producciones/pendientes', ['animal_type_id' => $this->vacuno->id, 'animal_ids' => [1]])
            ->assertForbidden();

        // El dueño, todo.
        Sanctum::actingAs($dueno);
        $this->getJson('/api/me')->assertJsonPath('usuario.permisos', array_keys(User::PERMISOS));
    }
}
