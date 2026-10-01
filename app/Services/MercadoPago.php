<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Mercado Pago Checkout Pro (pago único por período), con el cliente HTTP de
 * Laravel (sin SDK). Credenciales en .env (usar las de PRUEBA para sandbox):
 * MERCADOPAGO_ACCESS_TOKEN, MERCADOPAGO_PUBLIC_KEY, MERCADOPAGO_WEBHOOK_SECRET.
 */
class MercadoPago
{
    public function configurado(): bool
    {
        return filled(config('carnicos.pagos.mercadopago.access_token'));
    }

    /**
     * Crea la preferencia de pago del Payment y devuelve [id, url de pago].
     *
     * @return array{id: string, url: string}
     */
    public function crearPreferencia(Payment $payment): array
    {
        $payment->loadMissing('plan', 'carniceria');

        $respuesta = $this->cliente()->post('/checkout/preferences', [
            'items' => [[
                'id' => $payment->plan->codigo,
                'title' => "Carnicos · {$payment->plan->nombre} · {$payment->meses} ".($payment->meses === 1 ? 'mes' : 'meses'),
                'quantity' => 1,
                'currency_id' => $payment->currency,
                'unit_price' => (float) $payment->amount,
            ]],
            'external_reference' => (string) $payment->id,
            'notification_url' => route('webhooks.mercadopago'),
            'back_urls' => [
                'success' => route('billing.retorno', $payment),
                'pending' => route('billing.retorno', $payment),
                'failure' => route('billing.retorno', $payment),
            ],
            'auto_return' => 'approved',
            'statement_descriptor' => 'CARNICOS',
        ]);

        if (! $respuesta->successful()) {
            throw new RuntimeException('Mercado Pago no pudo iniciar el pago ('.$respuesta->status().').');
        }

        $sandbox = (bool) config('carnicos.pagos.mercadopago.sandbox');

        return [
            'id' => (string) $respuesta->json('id'),
            'url' => (string) ($sandbox ? ($respuesta->json('sandbox_init_point') ?? $respuesta->json('init_point')) : $respuesta->json('init_point')),
        ];
    }

    /** Datos de un pago consultados a Mercado Pago (nunca se confía en lo que llega del navegador o del aviso). */
    public function consultarPago(string $pagoId): array
    {
        $respuesta = $this->cliente()->get('/v1/payments/'.urlencode($pagoId));

        if (! $respuesta->successful()) {
            throw new RuntimeException("No se pudo consultar el pago {$pagoId} ({$respuesta->status()}).");
        }

        return $respuesta->json();
    }

    /**
     * Firma del aviso (header x-signature: "ts=...,v1=..."): HMAC-SHA256 de
     * "id:{data.id};request-id:{x-request-id};ts:{ts};" con el secreto.
     * Sin secreto configurado no se puede validar: se rechaza.
     */
    public function firmaValida(Request $request): bool
    {
        $secreto = (string) config('carnicos.pagos.mercadopago.webhook_secret');
        $firma = (string) $request->header('x-signature');

        if ($secreto === '' || $firma === '') {
            return false;
        }

        $partes = [];
        foreach (explode(',', $firma) as $parte) {
            [$clave, $valor] = array_pad(explode('=', trim($parte), 2), 2, '');
            $partes[$clave] = $valor;
        }

        if (empty($partes['ts']) || empty($partes['v1'])) {
            return false;
        }

        $dataId = mb_strtolower((string) ($request->query('data_id') ?? $request->input('data.id', '')));
        $manifiesto = "id:{$dataId};request-id:{$request->header('x-request-id')};ts:{$partes['ts']};";

        return hash_equals(hash_hmac('sha256', $manifiesto, $secreto), $partes['v1']);
    }

    private function cliente(): PendingRequest
    {
        if (! $this->configurado()) {
            throw new RuntimeException('Mercado Pago no está configurado (MERCADOPAGO_ACCESS_TOKEN).');
        }

        return Http::baseUrl((string) config('carnicos.pagos.mercadopago.base_url'))
            ->withToken((string) config('carnicos.pagos.mercadopago.access_token'))
            ->acceptJson()
            ->timeout(20);
    }
}
