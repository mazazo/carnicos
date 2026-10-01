<?php

namespace App\Actions;

use App\Models\Payment;
use App\Services\MercadoPago;
use App\Services\Suscripciones;
use Illuminate\Support\Facades\Log;

/**
 * Procesa un pago de Mercado Pago: lo consulta en su API, lo cruza con
 * nuestro Payment (external_reference) y, si está aprobado y el importe
 * coincide, activa el período. Idempotente: se puede llamar varias veces
 * (aviso repetido, vuelta del cliente) sin duplicar nada.
 */
class ProcesarPagoMercadoPago
{
    public function __construct(
        private MercadoPago $mercadoPago,
        private Suscripciones $suscripciones,
    ) {}

    public function handle(string $pagoId): ?Payment
    {
        $datos = $this->mercadoPago->consultarPago($pagoId);
        $payment = Payment::query()->find((int) ($datos['external_reference'] ?? 0));

        if (! $payment || $payment->proveedor !== 'mercadopago') {
            Log::warning('Pago de Mercado Pago sin Payment asociado', ['pago' => $pagoId, 'ref' => $datos['external_reference'] ?? null]);

            return null;
        }

        $payment->update([
            'proveedor_pago_id' => (string) $datos['id'],
            'proveedor_respuesta' => array_intersect_key($datos, array_flip(['id', 'status', 'status_detail', 'transaction_amount', 'currency_id', 'date_approved', 'payment_method_id'])),
            'reference' => (string) $datos['id'],
        ]);

        $estado = (string) ($datos['status'] ?? '');

        if ($estado === 'approved') {
            $importeOk = abs((float) ($datos['transaction_amount'] ?? 0) - (float) $payment->amount) < 0.01
                && ($datos['currency_id'] ?? $payment->currency) === $payment->currency;

            if (! $importeOk) {
                Log::error('Pago de Mercado Pago con importe distinto', ['payment' => $payment->id, 'mp' => $datos['transaction_amount'] ?? null]);
                $payment->update(['notes' => 'Importe distinto al esperado: revisar.']);

                return $payment->refresh();
            }

            return $this->suscripciones->confirmarPago($payment);
        }

        if (in_array($estado, ['rejected', 'cancelled', 'refunded', 'charged_back'], true) && ! $payment->isPaid()) {
            $payment->update(['status' => $estado === 'refunded' ? Payment::REFUNDED : Payment::FAILED]);
        }

        return $payment->refresh();
    }
}
