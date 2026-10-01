<?php

namespace App\Livewire\Billing;

use App\Models\Payment;
use App\Models\Plan;
use App\Services\MercadoPago;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * Pago único de un período de un plan. Mercado Pago (Checkout Pro) cuando
 * está configurado, o aviso de transferencia que confirma el administrador.
 * Paga el dueño de la carnicería.
 */
#[Layout('layouts.app')]
class Checkout extends Component
{
    #[Locked]
    public int $planId;

    public ?int $meses = null;

    public string $metodo = '';

    public string $referencia = '';

    // El parámetro de la ruta es {codigo}: si se llamara {plan}, Livewire lo
    // asignaría a una propiedad con ese nombre.
    public function mount(string $codigo, MercadoPago $mercadoPago): void
    {
        $plan = Plan::query()->where('activo', true)->where('codigo', $codigo)->with(['precios' => fn ($q) => $q->where('activo', true)])->first();
        abort_if(! $plan || $plan->precios->isEmpty(), 404);

        $this->planId = $plan->id;
        $this->meses = $plan->precios->first()->meses;
        $this->metodo = $mercadoPago->configurado() ? 'mercadopago' : 'transferencia';
    }

    private function plan(): Plan
    {
        return Plan::query()->with(['precios' => fn ($q) => $q->where('activo', true)])->findOrFail($this->planId);
    }

    public function pagar(MercadoPago $mercadoPago)
    {
        $user = Auth::user();
        abort_unless($user->carniceria_id && $user->esDueno(), 403);

        $plan = $this->plan();
        $precio = $plan->precios->firstWhere('meses', (int) $this->meses);

        if (! $precio) {
            throw ValidationException::withMessages(['meses' => 'Elegí un período.']);
        }

        $metodosValidos = array_keys($this->metodos($mercadoPago));
        if (! in_array($this->metodo, $metodosValidos, true)) {
            throw ValidationException::withMessages(['metodo' => 'Elegí cómo pagar.']);
        }

        if ($this->metodo === 'transferencia') {
            $this->validate(['referencia' => ['required', 'string', 'max:100']], ['referencia.required' => 'Indicá el número de operación o comprobante.']);
        }

        $payment = Payment::query()->create([
            'carniceria_id' => $user->carniceria_id,
            'plan_id' => $plan->id,
            'meses' => $precio->meses,
            'user_id' => $user->id,
            'amount' => $precio->precio,
            'currency' => $precio->moneda,
            'status' => Payment::PENDING,
            'method' => $this->metodo,
            'proveedor' => $this->metodo === 'mercadopago' ? 'mercadopago' : 'manual',
            'reference' => $this->metodo === 'transferencia' ? trim($this->referencia) : null,
        ]);

        if ($this->metodo === 'transferencia') {
            session()->flash('success', 'Recibimos tu aviso de transferencia. Administración lo confirma y se activa tu plan.');

            return $this->redirectRoute('billing.plans');
        }

        try {
            $preferencia = $mercadoPago->crearPreferencia($payment);
        } catch (Throwable $e) {
            report($e);
            $payment->update(['status' => Payment::FAILED, 'notes' => 'No se pudo iniciar el pago en Mercado Pago.']);
            throw ValidationException::withMessages(['metodo' => 'Mercado Pago no respondió. Probá de nuevo en unos minutos.']);
        }

        $payment->update(['proveedor_preferencia_id' => $preferencia['id']]);

        return $this->redirect($preferencia['url']);
    }

    /** @return array<string, string> */
    private function metodos(MercadoPago $mercadoPago): array
    {
        return array_filter([
            'mercadopago' => $mercadoPago->configurado() ? 'Mercado Pago (tarjeta, débito o dinero en cuenta)' : null,
            'transferencia' => config('carnicos.pagos.transferencia.habilitada') ? 'Transferencia bancaria' : null,
        ]);
    }

    public function render(MercadoPago $mercadoPago)
    {
        $plan = $this->plan();
        $user = Auth::user();

        return view('livewire.billing.checkout', [
            'plan' => $plan,
            'precio' => $plan->precios->firstWhere('meses', (int) $this->meses),
            'metodos' => $this->metodos($mercadoPago),
            'esDueno' => $user->carniceria_id && $user->esDueno(),
            'vigente' => $user->carniceria?->suscripcionVigente(),
            'instruccionesTransferencia' => config('carnicos.pagos.transferencia.instrucciones'),
        ]);
    }
}
