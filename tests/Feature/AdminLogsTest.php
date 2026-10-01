<?php

namespace Tests\Feature;

use App\Livewire\Admin\Logs;
use App\Models\Carniceria;
use App\Models\User;
use App\Services\Suscripciones;
use App\Support\Logs\LogReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Visor de logs del administrador de la plataforma.
 */
class AdminLogsTest extends TestCase
{
    use RefreshDatabase;

    private string $directorio;

    protected function setUp(): void
    {
        parent::setUp();

        // Un directorio propio: el test nunca toca storage/logs real.
        $this->directorio = storage_path('framework/testing/logs-'.uniqid());
        File::ensureDirectoryExists($this->directorio);
        File::put($this->directorio.'/laravel.log', implode("\n", [
            '[2026-09-30 10:00:00] local.INFO: Arranque normal',
            '[2026-09-30 10:05:00] local.ERROR: Fallo al guardar el animal {"userId":3}',
            '#0 app/Livewire/Animals/Form.php(120): save()',
            '#1 {main}',
            '[2026-09-30 10:10:00] local.WARNING: Precio sin cargar',
        ]));
        $this->app->instance(LogReader::class, new LogReader($this->directorio));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directorio);
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_el_admin_ve_los_errores_con_su_detalle_y_filtra(): void
    {
        $this->actingAs($this->admin())->get(route('admin.config.logs'))->assertOk()
            ->assertSeeInOrder(['Precio sin cargar', 'Fallo al guardar el animal', 'Arranque normal'])
            ->assertSee('app/Livewire/Animals/Form.php(120)');

        Livewire::actingAs($this->admin())->test(Logs::class)
            ->set('nivel', 'error')
            ->assertSee('Fallo al guardar el animal')->assertDontSee('Precio sin cargar')
            ->set('nivel', '')->set('busqueda', 'Form.php')
            ->assertSee('Fallo al guardar el animal')->assertDontSee('Arranque normal');
    }

    public function test_descargar_y_vaciar(): void
    {
        Livewire::actingAs($this->admin())->test(Logs::class)->call('descargar')->assertFileDownloaded('laravel.log');

        Livewire::actingAs($this->admin())->test(Logs::class)->call('vaciar')->assertSee('No hay entradas');
        $this->assertSame('', File::get($this->directorio.'/laravel.log'));
    }

    public function test_solo_el_admin_de_la_plataforma(): void
    {
        $carniceria = Carniceria::query()->create(['nombre' => 'Don Juan']);
        $dueno = User::factory()->create(['carniceria_id' => $carniceria->id, 'rol' => User::ROL_DUENO]);
        app(Suscripciones::class)->iniciarPrueba($carniceria, $dueno);

        $this->actingAs($dueno)->get(route('admin.config.logs'))->assertForbidden();
        Livewire::actingAs($dueno)->test(Logs::class)->assertForbidden();
    }
}
