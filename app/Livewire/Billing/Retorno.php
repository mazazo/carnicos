<?php

namespace App\Livewire\Billing;

use App\Actions\ProcesarPagoMercadoPago;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * Vuelta desde Mercado Pago. Si trae el id del pago, se consulta y procesa
 * (igual que el aviso: idempotente), así el plan se activa aunque el aviso
 * tarde. Nunca se confía en el "status" que viene en la URL.
 */
#[Layout('layouts.app')]
class Retorno extends Component
{
    #[Locked]
    public int $paymentId;

    public function mount(Payment $payment, ProcesarPagoMercadoPago $procesar): void
    {
        abort_unless((int) $payment->carniceria_id === (int) Auth::user()->carniceria_id, 404);
        $this->paymentId = $payment->id;

        $pagoId = (string) (request()->query('payment_id') ?? request()->query('collection_id') ?? '');

        if ($pagoId !== '' && $payment->proveedor === 'mercadopago' && ! $payment->isPaid()) {
            try {
                $procesar->handle($pagoId);
            } catch (Throwable $e) {
                report($e); // lo resuelve el aviso de Mercado Pago
            }
        }
    }

    public function render()
    {
        $payment = Payment::query()->with('plan')->findOrFail($this->paymentId);

        return view('livewire.billing.retorno', [
            'payment' => $payment,
            'destino' => Auth::user()->dashboardRouteName(),
        ]);
    }
}
