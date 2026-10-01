<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\AnimalType;
use App\Models\CutCatalog;
use App\Models\Desposte;
use App\Models\PrecioCorte;
use App\Models\User;
use App\Services\Suscripciones;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Concerns\CreaCarnicerias;
use Tests\TestCase;

/** API de la app Android: ingreso, bloqueo por plan, ingreso de medias, desposte y precios. */
class ApiAppTest extends TestCase
{
    use CreaCarnicerias;
    use RefreshDatabase;

    private AnimalType $vacuno;

    private AnimalType $aviar;

    protected function setUp(): void
    {
        parent::setUp();
        [$this->vacuno, , $this->aviar] = $this->tiposDeAnimal();
        $this->aviar->update(['modalidad' => 'cajon']);
    }

    private function corte(string $nombre, ?AnimalType $tipo = null): CutCatalog
    {
        return CutCatalog::query()->create([
            'carniceria_id' => null,
            'animal_type_id' => ($tipo ?? $this->vacuno)->id,
            'nombre_canonico' => $nombre,
            'activo' => true,
        ]);
    }

    /** Dueño de una carnicería con Plan Completo, ya autenticado con token. */
    private function duenoConApp(string $nombre = 'Don Juan'): User
    {
        [, $dueno] = $this->carniceriaCon('plan-3', $nombre);
        Sanctum::actingAs($dueno);

        return $dueno;
    }

    private function media(float $peso, float $precio, ?User $user = null, ?AnimalType $tipo = null): Animal
    {
        $user ??= auth()->user();

        return Animal::withoutGlobalScopes()->create([
            'carniceria_id' => $user->carniceria_id,
            'user_id' => $user->id,
            'animal_type_id' => ($tipo ?? $this->vacuno)->id,
            'formato' => Animal::FORMATO_MEDIA_RES,
            'peso_total' => $peso,
            'precio_kg' => $precio,
            'fecha' => today()->toDateString(),
        ]);
    }

    public function test_login_devuelve_token_y_acceso(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');

        $this->postJson('/api/auth/login', ['email' => $dueno->email, 'password' => 'mala'])->assertUnprocessable();

        $respuesta = $this->postJson('/api/auth/login', ['email' => strtoupper($dueno->email), 'password' => 'password', 'dispositivo' => 'Moto G'])
            ->assertOk()
            ->assertJsonPath('acceso.permitido', true)
            ->assertJsonPath('plan.codigo', 'plan-3')
            ->assertJsonPath('usuario.es_dueno', true);

        $this->withToken($respuesta->json('token'))->getJson('/api/tipos-animal')->assertOk()->assertJsonCount(3, 'data');
        $this->assertSame('Moto G', $dueno->tokens()->sole()->name);
    }

    public function test_el_registro_crea_la_carniceria_en_prueba_sin_acceso_a_la_app(): void
    {
        $this->postJson('/api/auth/registro', [
            'carniceria' => ' Carnicería  Sur ',
            'name' => 'Ana',
            'email' => 'Ana@Example.test',
            'movil' => '11 5555 1234',
            'password' => 'Clave-Segura1',
            'password_confirmation' => 'Clave-Segura1',
        ])
            ->assertCreated()
            ->assertJsonPath('carniceria.nombre', 'Carnicería Sur')
            ->assertJsonPath('plan.prueba', true)
            ->assertJsonPath('acceso.permitido', false)
            ->assertJsonPath('acceso.codigo', 'email_no_verificado');

        $this->assertDatabaseHas('users', ['email' => 'ana@example.test', 'movil' => '1155551234', 'rol' => 'dueno']);
    }

    public function test_sin_plan_completo_la_app_queda_bloqueada(): void
    {
        [, $profesional] = $this->carniceriaCon('plan-2', 'Uno');
        Sanctum::actingAs($profesional);
        $this->getJson('/api/me')->assertOk()->assertJsonPath('acceso.codigo', 'plan_sin_app');
        $this->getJson('/api/tipos-animal')->assertForbidden()->assertJsonPath('codigo', 'plan_sin_app');

        [, $vencida] = $this->carniceriaCon(null, 'Dos');
        Sanctum::actingAs($vencida);
        $this->getJson('/api/animales')->assertForbidden()->assertJsonPath('codigo', 'sin_suscripcion');

        [$carniceria, $suspendida] = $this->carniceriaCon('plan-3', 'Tres');
        app(Suscripciones::class)->suspender($carniceria, 'deuda');
        Sanctum::actingAs($suspendida);
        $this->getJson('/api/animales')->assertForbidden()->assertJsonPath('codigo', 'suspendida');

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/animales')->assertUnauthorized();
    }

    public function test_ingreso_de_medias_y_listado_de_disponibles(): void
    {
        $this->duenoConApp();

        $this->postJson('/api/animales', ['animal_type_id' => $this->vacuno->id, 'formato' => 'cajon', 'peso_total' => 100, 'precio_kg' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('formato');

        $this->postJson('/api/animales', [
            'animal_type_id' => $this->vacuno->id,
            'formato' => 'media_res',
            'peso_total' => 112.5,
            'precio_kg' => 4000,
            'proveedor' => 'Frigorífico Norte',
        ])->assertCreated()
            ->assertJsonPath('data.formato_etiqueta', 'Media res')
            ->assertJsonPath('data.costo_total', 450000)
            ->assertJsonPath('data.estado', 'disponible');

        $this->postJson('/api/animales', ['animal_type_id' => $this->aviar->id, 'formato' => 'cajon', 'cantidad' => 3, 'peso_total' => 60, 'precio_kg' => 2500])
            ->assertCreated()->assertJsonPath('data.cantidad', 3);

        $this->getJson('/api/animales?animal_type_id='.$this->vacuno->id)->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/tipos-animal')->assertJsonPath('data.0.disponibles', 1)->assertJsonPath('data.0.etiqueta', 'Vacuno')
            ->assertJsonPath('data.0.formatos.0.valor', 'res')
            ->assertJsonPath('data.2.formatos', [['valor' => 'cajon', 'etiqueta' => 'Cajón']]);
    }

    public function test_desposte_de_varias_medias_con_precios_actuales(): void
    {
        $dueno = $this->duenoConApp();
        $asado = $this->corte('Asado');
        $vacio = $this->corte('Vacío');
        PrecioCorte::query()->create(['carniceria_id' => $dueno->carniceria_id, 'cut_catalog_id' => $asado->id, 'precio_kg' => 9000]);
        $m1 = $this->media(100, 4000);
        $m2 = $this->media(110, 4200);
        $otra = $this->media(90, 4000);

        $this->getJson('/api/cortes?animal_type_id='.$this->vacuno->id)->assertOk()
            ->assertJsonPath('data.0.nombre', 'Asado')->assertJsonPath('data.0.precio_kg', 9000)
            ->assertJsonPath('data.1.precio_kg', null);

        $respuesta = $this->postJson('/api/producciones', [
            'animal_type_id' => $this->vacuno->id,
            'animal_ids' => [$m1->id, $m2->id],
            'cortes' => [
                ['cut_catalog_id' => $asado->id, 'peso' => 40],
                ['cut_catalog_id' => $vacio->id, 'peso' => 20.5, 'cantidad' => 2],
            ],
        ])->assertCreated();

        $respuesta->assertJsonPath('data.medias', 2)
            ->assertJsonPath('data.peso_medias', 210)
            ->assertJsonPath('data.costo_total', 862000) // 100×4000 + 110×4200
            ->assertJsonPath('data.peso_cortes', 60.5)
            ->assertJsonPath('data.valor_total', 360000) // 40×9000; el vacío sin precio
            ->assertJsonPath('data.cortes.1.precio_kg', null);

        $this->assertSame(Animal::DESPOSTADA, $m1->fresh()->estado);
        $this->assertSame(Animal::DISPONIBLE, $otra->fresh()->estado);
        $this->getJson('/api/animales?animal_type_id='.$this->vacuno->id)->assertJsonCount(1, 'data');
        $this->getJson('/api/producciones')->assertJsonCount(1, 'data')->assertJsonPath('data.0.tipo', 'Vacuno');

        // Una media despostada no se puede volver a usar.
        $this->postJson('/api/producciones', [
            'animal_type_id' => $this->vacuno->id,
            'animal_ids' => [$m1->id],
            'cortes' => [['cut_catalog_id' => $asado->id, 'peso' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors('animal_ids');
    }

    public function test_no_se_usan_medias_ni_cortes_ajenos(): void
    {
        [, $otroDueno] = $this->carniceriaCon('plan-3', 'Otra');
        $ajena = $this->media(100, 4000, $otroDueno);
        $polloCorte = $this->corte('Pechuga', $this->aviar);

        $this->duenoConApp();
        $propia = $this->media(100, 4000);
        $asado = $this->corte('Asado');

        $this->postJson('/api/producciones', [
            'animal_type_id' => $this->vacuno->id,
            'animal_ids' => [$ajena->id],
            'cortes' => [['cut_catalog_id' => $asado->id, 'peso' => 10]],
        ])->assertUnprocessable()->assertJsonValidationErrors('animal_ids');

        $this->postJson('/api/producciones', [
            'animal_type_id' => $this->vacuno->id,
            'animal_ids' => [$propia->id],
            'cortes' => [['cut_catalog_id' => $polloCorte->id, 'peso' => 10]],
        ])->assertUnprocessable()->assertJsonValidationErrors('cortes');

        $this->getJson('/api/animales?estado=todas')->assertJsonCount(1, 'data');
        $desposteAjeno = Desposte::withoutGlobalScopes()->create(['carniceria_id' => $otroDueno->carniceria_id, 'animal_type_id' => $this->vacuno->id, 'fecha_desposte' => today()]);
        $this->getJson('/api/producciones/'.$desposteAjeno->id)->assertNotFound();
    }

    public function test_desposte_pendiente_por_pesadas_queda_en_el_sistema_y_se_termina(): void
    {
        $dueno = $this->duenoConApp();
        $m1 = $this->media(100, 4000);
        $m2 = $this->media(110, 4200);
        $asado = $this->corte('Asado');
        $vacio = $this->corte('Vacío');
        PrecioCorte::withoutGlobalScopes()->create(['carniceria_id' => $dueno->carniceria_id, 'cut_catalog_id' => $asado->id, 'precio_kg' => 9000]);

        $id = $this->postJson('/api/producciones/pendientes', [
            'animal_type_id' => $this->vacuno->id,
            'animal_ids' => [$m1->id, $m2->id],
        ])->assertCreated()->assertJsonPath('data.estado', 'pendiente')->json('data.id');

        $this->assertSame(Animal::EN_DESPOSTE, $m1->fresh()->estado);
        $this->getJson('/api/animales?animal_type_id='.$this->vacuno->id)->assertJsonCount(0, 'data');

        $this->postJson("/api/producciones/$id/pesadas", ['cut_catalog_id' => $asado->id, 'peso' => 25])->assertCreated();
        $this->postJson("/api/producciones/$id/pesadas", ['cut_catalog_id' => $asado->id, 'peso' => 15.5])->assertCreated();
        $errada = $this->postJson("/api/producciones/$id/pesadas", ['cut_catalog_id' => $vacio->id, 'peso' => 99])->json('data.pesadas.2.id');
        $this->postJson("/api/producciones/$id/pesadas", ['cut_catalog_id' => $vacio->id, 'peso' => 20])
            ->assertJsonCount(4, 'data.pesadas')->assertJsonPath('data.pesadas.0.nombre', 'Asado');
        $this->deleteJson("/api/producciones/$id/pesadas/$errada")->assertOk()
            ->assertJsonCount(3, 'data.pesadas')->assertJsonPath('data.peso_cortes', 60.5)
            ->assertJsonPath('data.valor_total', 364500); // 40,5 × 9000; el vacío sin precio

        // Otro usuario de la carnicería ve el pendiente; no sale entre los terminados.
        Sanctum::actingAs(User::factory()->create(['carniceria_id' => $dueno->carniceria_id, 'rol' => User::ROL_EMPLEADO, 'permisos' => array_keys(User::PERMISOS)]));
        $this->getJson('/api/producciones?estado=pendiente')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $id);
        $this->getJson('/api/producciones')->assertJsonCount(0, 'data');

        $this->postJson("/api/producciones/$id/terminar")->assertOk()
            ->assertJsonPath('data.estado', 'terminado')
            ->assertJsonCount(2, 'data.cortes')
            ->assertJsonPath('data.peso_cortes', 60.5)
            ->assertJsonPath('data.valor_total', 364500);

        $this->assertSame(Animal::DESPOSTADA, $m2->fresh()->estado);
        $this->assertEqualsCanonicalizing(['40.500', '20.000'], Desposte::withoutGlobalScopes()->find($id)->cortes->pluck('peso')->all());
        $this->postJson("/api/producciones/$id/pesadas", ['cut_catalog_id' => $asado->id, 'peso' => 1])->assertUnprocessable();
        $this->deleteJson("/api/producciones/$id")->assertUnprocessable();
    }

    public function test_cancelar_un_pendiente_devuelve_las_medias_y_no_se_ve_de_otra_carniceria(): void
    {
        $this->duenoConApp();
        $media = $this->media(100, 4000);
        $id = $this->postJson('/api/producciones/pendientes', ['animal_type_id' => $this->vacuno->id, 'animal_ids' => [$media->id]])->json('data.id');

        // La media en desposte no se puede usar en otro.
        $this->postJson('/api/producciones/pendientes', ['animal_type_id' => $this->vacuno->id, 'animal_ids' => [$media->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('animal_ids');
        $this->postJson("/api/producciones/$id/terminar")->assertUnprocessable()->assertJsonValidationErrors('pesadas');

        $this->duenoConApp('Otra');
        $this->getJson("/api/producciones/$id")->assertNotFound();
        $this->deleteJson("/api/producciones/$id")->assertNotFound();

        Sanctum::actingAs(User::query()->where('carniceria_id', $media->carniceria_id)->first());
        $this->deleteJson("/api/producciones/$id")->assertOk();
        $this->assertSame(Animal::DISPONIBLE, $media->fresh()->estado);
        $this->assertNull(Desposte::withoutGlobalScopes()->find($id));
    }

    public function test_producciones_se_filtran_por_rango_de_fechas(): void
    {
        $dueno = $this->duenoConApp();
        foreach ([today(), today()->subDay(), today()->subDays(10)] as $fecha) {
            Desposte::withoutGlobalScopes()->create(['carniceria_id' => $dueno->carniceria_id, 'animal_type_id' => $this->vacuno->id, 'fecha_desposte' => $fecha]);
        }

        $hoy = today()->toDateString();
        $ayer = today()->subDay()->toDateString();

        $this->getJson("/api/producciones?desde=$hoy&hasta=$hoy")->assertJsonCount(1, 'data')->assertJsonPath('data.0.fecha', $hoy);
        $this->getJson("/api/producciones?desde=$ayer&hasta=$ayer")->assertJsonCount(1, 'data')->assertJsonPath('data.0.fecha', $ayer);
        $this->getJson('/api/producciones?desde='.today()->subDays(6)->toDateString())->assertJsonCount(2, 'data');
        $this->getJson('/api/producciones')->assertJsonCount(3, 'data');
        $this->getJson("/api/producciones?desde=$hoy&hasta=$ayer")->assertUnprocessable()->assertJsonValidationErrors('hasta');
    }

    public function test_el_dueno_guarda_precios_nuevos_y_el_empleado_no(): void
    {
        $dueno = $this->duenoConApp();
        $asado = $this->corte('Asado');

        $this->putJson('/api/precios', ['precios' => [['cut_catalog_id' => $asado->id, 'precio_kg' => 9500]]])
            ->assertOk()->assertJsonPath('data.0.precio_kg', 9500);
        $this->putJson('/api/precios', ['precios' => [['cut_catalog_id' => $asado->id, 'precio_kg' => 9800]]])->assertOk();

        $this->assertSame('9800.00', PrecioCorte::withoutGlobalScopes()->sole()->precio_kg);

        $empleado = User::factory()->create(['carniceria_id' => $dueno->carniceria_id, 'rol' => User::ROL_EMPLEADO, 'permisos' => array_keys(User::PERMISOS)]);
        Sanctum::actingAs($empleado);
        $this->putJson('/api/precios', ['precios' => [['cut_catalog_id' => $asado->id, 'precio_kg' => 1]]])->assertForbidden();
        $this->getJson('/api/cortes?animal_type_id='.$this->vacuno->id)->assertOk()->assertJsonPath('data.0.precio_kg', 9800);
    }

    public function test_logout_revoca_el_token(): void
    {
        [, $dueno] = $this->carniceriaCon('plan-3');
        $token = $this->postJson('/api/auth/login', ['email' => $dueno->email, 'password' => 'password'])->json('token');

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
        $this->assertSame(0, $dueno->tokens()->count());
    }
}
