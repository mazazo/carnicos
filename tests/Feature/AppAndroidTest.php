<?php

namespace Tests\Feature;

use App\Http\Controllers\AppAndroidController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\CreaCarnicerias;
use Tests\TestCase;

/** Página y descarga de la app Android: solo Plan Completo con permiso de app. */
class AppAndroidTest extends TestCase
{
    use CreaCarnicerias;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tiposDeAnimal();
        Storage::fake('local');
    }

    public function test_el_plan_completo_ve_el_enlace_los_pasos_y_descarga_el_apk(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        $this->actingAs($dueno);

        $this->get('/dashboard/enterprise')->assertSee(route('app.android'), false);
        $this->get('/app-android')->assertOk()
            ->assertSee('Cómo instalarla')
            ->assertSee('Instalá igualmente')
            ->assertSee('La descarga todavía no está disponible');
        $this->get('/app-android/descargar')->assertNotFound();

        Storage::disk('local')->put(AppAndroidController::ARCHIVO, str_repeat('x', 2048));

        $this->get('/app-android')->assertSee('Descargar la app')->assertSee(route('app.android.descargar'), false);
        $this->get('/app-android/descargar')->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.android.package-archive')
            ->assertDownload('carnico.apk');
    }

    public function test_el_admin_publica_una_version_nueva_y_queda_la_anterior(): void
    {
        $admin = $this->admin();
        Storage::disk('local')->put(AppAndroidController::ARCHIVO, 'version vieja');

        $this->actingAs($admin)->get('/app-android')->assertOk()->assertSee('Publicar una versión nueva');

        $this->actingAs($admin)->post('/app-android/subir', [
            'apk' => \Illuminate\Http\UploadedFile::fake()->create('app-release.apk', 1500, 'application/vnd.android.package-archive'),
        ])->assertRedirect(route('app.android'))->assertSessionHas('success');

        Storage::disk('local')->assertExists(AppAndroidController::ARCHIVO);
        $this->assertSame('version vieja', Storage::disk('local')->get(AppAndroidController::ANTERIOR));
        $this->assertNotSame('version vieja', Storage::disk('local')->get(AppAndroidController::ARCHIVO));

        // Un archivo que no es .apk se rechaza y no toca lo publicado.
        $this->actingAs($admin)->post('/app-android/subir', [
            'apk' => \Illuminate\Http\UploadedFile::fake()->create('foto.jpg', 10),
        ])->assertSessionHasErrors('apk');
    }

    public function test_solo_el_admin_puede_publicar(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        $this->actingAs($dueno)->get('/app-android')->assertDontSee('Publicar una versión nueva');
        $this->actingAs($dueno)->post('/app-android/subir', [
            'apk' => \Illuminate\Http\UploadedFile::fake()->create('app.apk', 10),
        ])->assertForbidden();

        Storage::disk('local')->assertMissing(AppAndroidController::ARCHIVO);
    }

    public function test_sin_plan_completo_o_sin_permiso_de_app_no_hay_enlace_ni_descarga(): void
    {
        Storage::disk('local')->put(AppAndroidController::ARCHIVO, 'apk');

        [$carniceriaBasica, $basico] = $this->carniceriaCon('plan-2', 'Básica');
        $carniceriaBasica->tiposAnimal()->sync(\App\Models\AnimalType::query()->orderBy('id')->limit(2)->pluck('id'));
        $this->actingAs($basico);
        $this->get('/dashboard/pro')->assertOk()->assertDontSee(route('app.android'), false);
        $this->get('/app-android')->assertForbidden();
        $this->get('/app-android/descargar')->assertForbidden();

        [, $dueno] = $this->carniceriaCon('plan-3', 'Completa');
        $sinApp = User::factory()->create(['carniceria_id' => $dueno->carniceria_id, 'rol' => User::ROL_EMPLEADO, 'permisos' => ['ingresos']]);
        $this->actingAs($sinApp);
        $this->get('/app-android')->assertForbidden();
        $this->get('/app-android/descargar')->assertForbidden();

        $conApp = User::factory()->create(['carniceria_id' => $dueno->carniceria_id, 'rol' => User::ROL_EMPLEADO, 'permisos' => ['app']]);
        $this->actingAs($conApp);
        $this->get('/app-android/descargar')->assertOk();
    }
}
