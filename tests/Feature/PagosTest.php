<?php

namespace Tests\Feature;

use App\Livewire\Admin\Pagos;
use App\Livewire\Billing\Checkout;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Suscripciones;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreaCarnicerias;
use Tests\TestCase;

/** Etapa 4: pago único por período, manual (transferencia) y Mercado Pago. */
class PagosTest extends TestCase
{
    use CreaCarnicerias;
    use RefreshDatabase;

    private function configurarMercadoPago(): void
    {
        config([
            'carnicos.pagos.mercadopago.access_token' => 'TEST-token',
            'carnicos.pagos.mercadopago.webhook_secret' => 'secreto',
            'carnicos.pagos.mercadopago.base_url' => 'https://api.mercadopago.test',
            'carnicos.pagos.mercadopago.sandbox' => true,
        ]);
    }

    private function pagoMercadoPago(int $carniceriaId, float $monto = 20000): Payment
    {
        return Payment::query()->create([
            'carniceria_id' => $carniceriaId,
            'plan_id' => $this->plan('plan-2')->id,
            'meses' => 1,
            'amount' => $monto,
            'currency' => 'ARS',
            'status' => Payment::PENDING,
            'method' => 'mercadopago',
            'proveedor' => 'mercadopago',
        ]);
    }

    private function fakePagoMp(Payment $payment, string $estado = 'approved', ?float $monto = null): void
    {
        Http::fake([
            'api.mercadopago.test/v1/payments/*' => Http::response([
                'id' => 999,
                'status' => $estado,
                'status_detail' => 'accredited',
                'transaction_amount' => $monto ?? (float) $payment->amount,
                'currency_id' => 'ARS',
                'external_reference' => (string) $payment->id,
            ]),
        ]);
    }

    /** Aviso de Mercado Pago firmado como lo firma MP. */
    private function aviso(string $requestId, string $secreto = 'secreto')
    {
        $ts = '1700000000';
        $v1 = hash_hmac('sha256', "id:999;request-id:{$requestId};ts:{$ts};", $secreto);

        return $this->postJson('/webhooks/mercadopago?data.id=999&type=payment', ['type' => 'payment', 'data' => ['id' => '999']], [
            'x-signature' => "ts={$ts},v1={$v1}",
            'x-request-id' => $requestId,
        ]);
    }

    public function test_aviso_de_transferencia_y_aprobacion_del_admin(): void
    {
        [$carniceria, $dueno] = $this->carniceriaCon('prueba');

        $this->actingAs($dueno);
        Livewire::test(Checkout::class, ['codigo' => 'plan-2'])
            ->assertSet('metodo', 'transferencia') // Mercado Pago sin configurar
            ->set('referencia', 'OP-123')
            ->call('pagar')
            ->assertHasNoErrors()
            ->assertRedirect(route('billing.plans'));

        $payment = Payment::query()->sole();
        $this->assertSame(Payment::PENDING, $payment->status);
        $this->assertSame(20000.0, (float) $payment->amount);
        $this->assertTrue($carniceria->suscripcionVigente()->isTrial()); // sigue en prueba hasta aprobar

        $this->actingAs($this->admin());
        Livewire::test(Pagos::class)->call('aprobar', $payment->id);

        $vigente = $carniceria->suscripcionVigente();
        $this->assertSame('plan-2', $vigente->planModel->codigo);
        $this->assertSame(Subscription::ACTIVE, $vigente->status);
        $this->assertTrue($vigente->ends_at->between(now()->addMonth()->subMinute(), now()->addMonth()->addMinute()));
        $this->assertTrue($payment->fresh()->isPaid());
        $this->assertSame($vigente->id, $payment->fresh()->subscription_id);
    }

    public function test_el_empleado_no_puede_pagar(): void
    {
        [$carniceria] = $this->carniceriaCon('prueba');
        $empleado = User::factory()->create(['carniceria_id' => $carniceria->id, 'rol' => User::ROL_EMPLEADO]);

        $this->actingAs($empleado);
        Livewire::test(Checkout::class, ['codigo' => 'plan-2'])->set('referencia', 'X')->call('pagar')->assertForbidden();
    }

    public function test_confirmar_dos_veces_no_suma_dos_veces_y_renovar_suma_al_final(): void
    {
        [$carniceria] = $this->carniceriaCon('plan-2');
        $finOriginal = $carniceria->suscripcionVigente()->ends_at;
        $payment = $this->pagoMercadoPago($carniceria->id);
        $servicio = app(Suscripciones::class);

        $servicio->confirmarPago($payment);
        $servicio->confirmarPago($payment);

        $vigente = $carniceria->suscripcionVigente();
        $this->assertSame(1, $carniceria->suscripciones()->count()); // mismo plan: se extiende
        $this->assertEquals($finOriginal->copy()->addMonthsNoOverflow(1)->toDateTimeString(), $vigente->ends_at->toDateTimeString());
    }

    public function test_mercado_pago_crea_la_preferencia_y_redirige(): void
    {
        $this->configurarMercadoPago();
        Http::fake([
            'api.mercadopago.test/checkout/preferences' => Http::response([
                'id' => 'pref-1',
                'init_point' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=pref-1',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=pref-1',
            ], 201),
        ]);
        [, $dueno] = $this->carniceriaCon('prueba');

        $this->actingAs($dueno);
        Livewire::test(Checkout::class, ['codigo' => 'plan-3'])
            ->assertSet('metodo', 'mercadopago')
            ->call('pagar')
            ->assertRedirect('https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=pref-1');

        $payment = Payment::query()->sole();
        $this->assertSame('pref-1', $payment->proveedor_preferencia_id);
        Http::assertSent(fn ($request) => $request['external_reference'] === (string) $payment->id
            && $request['items'][0]['unit_price'] === 25000.0
            && $request->hasHeader('Authorization', 'Bearer TEST-token'));
    }

    public function test_el_aviso_con_firma_invalida_se_rechaza(): void
    {
        $this->configurarMercadoPago();
        Http::fake();

        $this->aviso('req-1', 'otro-secreto')->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_el_aviso_aprobado_activa_el_plan_una_sola_vez(): void
    {
        $this->configurarMercadoPago();
        [$carniceria] = $this->carniceriaCon('prueba');
        $payment = $this->pagoMercadoPago($carniceria->id);
        $this->fakePagoMp($payment);

        $this->aviso('req-1')->assertOk();
        $this->aviso('req-1')->assertOk()->assertJson(['repetido' => true]);

        $payment->refresh();
        $this->assertTrue($payment->isPaid());
        $this->assertSame('999', $payment->proveedor_pago_id);
        $this->assertSame('plan-2', $carniceria->suscripcionVigente()->planModel->codigo);
        $this->assertDatabaseHas('webhook_eventos', ['evento_id' => 'req-1', 'estado' => 'procesado']);
        Http::assertSentCount(1);
    }

    public function test_un_importe_distinto_no_activa_el_plan(): void
    {
        $this->configurarMercadoPago();
        [$carniceria] = $this->carniceriaCon('prueba');
        $payment = $this->pagoMercadoPago($carniceria->id);
        $this->fakePagoMp($payment, monto: 10);

        $this->aviso('req-2')->assertOk();

        $this->assertSame(Payment::PENDING, $payment->fresh()->status);
        $this->assertTrue($carniceria->suscripcionVigente()->isTrial());
    }

    public function test_el_retorno_procesa_el_pago_y_solo_lo_ve_su_carniceria(): void
    {
        $this->configurarMercadoPago();
        [$carniceria, $dueno] = $this->carniceriaCon('prueba');
        [, $otroDueno] = $this->carniceriaCon('prueba', 'Otra');
        $payment = $this->pagoMercadoPago($carniceria->id);
        $this->fakePagoMp($payment);

        $this->actingAs($otroDueno)->get(route('billing.retorno', $payment).'?payment_id=999')->assertNotFound();
        $this->assertFalse($payment->fresh()->isPaid());

        $this->actingAs($dueno)->get(route('billing.retorno', $payment).'?payment_id=999&status=approved')
            ->assertOk()
            ->assertSee('Pago aprobado');
        $this->assertTrue($payment->fresh()->isPaid());
    }

    public function test_un_pago_rechazado_queda_fallido(): void
    {
        $this->configurarMercadoPago();
        [$carniceria] = $this->carniceriaCon('prueba');
        $payment = $this->pagoMercadoPago($carniceria->id);
        $this->fakePagoMp($payment, 'rejected');

        $this->aviso('req-3')->assertOk();

        $this->assertSame(Payment::FAILED, $payment->fresh()->status);
    }
}
