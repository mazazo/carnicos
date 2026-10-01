<?php

namespace Tests\Feature;

use App\Livewire\Auth\Register;
use App\Livewire\Dashboard\Index as Dashboard;
use App\Models\Animal;
use App\Models\AnimalType;
use App\Models\Carniceria;
use App\Models\Cut;
use App\Models\CutCatalog;
use App\Models\Plan;
use App\Models\User;
use App\Services\Suscripciones;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Etapa 1: el cliente es la carnicería. Sus usuarios comparten los datos y
 * ninguna carnicería ve los de otra.
 */
class CarniceriaTenancyTest extends TestCase
{
    use RefreshDatabase;

    private AnimalType $vacuno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vacuno = AnimalType::query()->firstOrCreate(['nombre' => 'vacuno'], ['activo' => true]);
    }

    /** Una carnicería con su dueño y, opcionalmente, un empleado. */
    private function carniceria(string $nombre): array
    {
        $carniceria = Carniceria::query()->create(['nombre' => $nombre]);
        $dueno = User::factory()->create(['carniceria_id' => $carniceria->id, 'rol' => User::ROL_DUENO]);
        $empleado = User::factory()->create(['carniceria_id' => $carniceria->id, 'rol' => User::ROL_EMPLEADO]);
        app(Suscripciones::class)->activarPlan($carniceria, Plan::query()->where('codigo', 'plan-3')->sole(), meses: 1, origen: 'manual');

        return [$carniceria, $dueno, $empleado];
    }

    private function animal(User $user, float $peso = 100): Animal
    {
        $this->actingAs($user);

        return Animal::query()->create([
            'user_id' => $user->id, 'animal_type_id' => $this->vacuno->id,
            'peso_total' => $peso, 'precio_kg' => 5000, 'fecha' => today()->toDateString(),
        ]);
    }

    public function test_el_registro_crea_la_carniceria_con_su_dueno(): void
    {
        Livewire::test(Register::class)
            ->set('carniceria', '  Carnicería   Don Juan ')
            ->set('name', 'Juan')
            ->set('email', 'juan@example.test')
            ->set('movil', '1155551234')
            ->set('password', 'Clave-Segura1')
            ->set('password_confirmation', 'Clave-Segura1')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('billing.plans'));

        $user = User::query()->where('email', 'juan@example.test')->sole();
        $this->get(route('billing.plans'))->assertOk()->assertSee('Profesional');
        $this->assertSame('Carnicería Don Juan', $user->carniceria->nombre);
        $this->assertTrue($user->esDueno());
        $this->assertSame('activa', $user->carniceria->estado);

        auth()->logout(); // ya registrado, el formulario redirige
        Livewire::test(Register::class)->set('carniceria', '')->call('register')->assertHasErrors('carniceria');
    }

    public function test_los_usuarios_de_una_carniceria_comparten_sus_datos(): void
    {
        [$carniceria, $dueno, $empleado] = $this->carniceria('Don Juan');
        $animal = $this->animal($dueno);

        $this->assertSame($carniceria->id, $animal->carniceria_id);
        $this->assertSame($dueno->id, $animal->user_id); // quién lo cargó

        // El empleado ve y abre el animal que cargó el dueño.
        $this->actingAs($empleado);
        $this->assertSame([$animal->id], Animal::query()->pluck('id')->all());
        $this->assertTrue(Animal::query()->whereKey($animal->id)->exists());
    }

    public function test_una_carniceria_no_ve_ni_toca_los_datos_de_otra(): void
    {
        [, $duenoA] = $this->carniceria('A');
        [, $duenoB] = $this->carniceria('B');
        $animalA = $this->animal($duenoA);
        $corteA = Cut::query()->create(['animal_id' => $animalA->id, 'animal_type_id' => $this->vacuno->id, 'nombre' => 'Asado', 'peso' => 10]);

        $this->actingAs($duenoB);
        $this->assertSame(0, Animal::query()->count());
        $this->assertSame(0, Cut::query()->whereHas('animal')->count());
        $this->get(route('animals.show', $animalA))->assertNotFound();
        $this->get(route('cuts.edit', $corteA))->assertForbidden();

        $this->assertFalse(Animal::query()->whereKey($animalA->id)->exists()); // borrar/editar: 'no encontrado'
        $this->assertNotNull(Animal::withoutGlobalScopes()->find($animalA->id));
    }

    public function test_el_catalogo_general_es_de_todos_y_el_propio_de_cada_carniceria(): void
    {
        [$carniceriaA, $duenoA, $empleadoA] = $this->carniceria('A');
        [, $duenoB] = $this->carniceria('B');
        $general = CutCatalog::query()->create(['carniceria_id' => null, 'animal_type_id' => $this->vacuno->id, 'nombre_canonico' => 'Vacío', 'cantidad_esperada' => 2, 'activo' => true]);

        // Dar de baja un corte general lo oculta solo para A, sin copiarlo ni tocarlo.
        Livewire::actingAs($duenoA)->test(Dashboard::class, ['variant' => 'enterprise'])->call('darBajaCorteCatalogo', $general->id);
        $this->assertSame(0, CutCatalog::withoutGlobalScopes()->where('carniceria_id', $carniceriaA->id)->count());
        $this->assertTrue($general->fresh()->activo);
        $this->assertSame([], CutCatalog::query()->habilitadosPara($carniceriaA->id)->pluck('id')->all());

        // El empleado de A lo vuelve a habilitar.
        Livewire::actingAs($empleadoA)->test(Dashboard::class, ['variant' => 'enterprise'])->call('darAltaCorteCatalogo', $general->id);
        $this->assertSame([$general->id], CutCatalog::query()->habilitadosPara($carniceriaA->id)->pluck('id')->all());

        // B ve el general, no el de A.
        $this->actingAs($duenoB);
        $this->assertSame([$general->id], CutCatalog::query()->pluck('id')->all());
    }

    public function test_un_usuario_sin_carniceria_no_ve_datos(): void
    {
        [, $dueno] = $this->carniceria('A');
        $this->animal($dueno);

        $this->actingAs(User::factory()->create(['carniceria_id' => null, 'is_admin' => true]));
        $this->assertSame(0, Animal::query()->count());
    }
}
