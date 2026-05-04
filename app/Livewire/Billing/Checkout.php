<?php

namespace App\Livewire\Billing;

use App\Support\Billing\PlanCatalog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Checkout extends Component
{
    public array $plan = [];
    public string $metodo = 'mercadopago';

    public function mount(string $plan): void
    {
        $planData = PlanCatalog::find($plan);

        abort_if($planData === null || (int) $planData['precio'] <= 0, 404);

        $this->plan = $planData;
    }

    public function seleccionarMetodo(string $metodo): void
    {
        if (! in_array($metodo, ['mercadopago', 'stripe', 'transferencia'], true)) {
            return;
        }

        $this->metodo = $metodo;
    }

    public function continuar(): void
    {
        $proveedor = match ($this->metodo) {
            'stripe' => 'Stripe',
            'transferencia' => 'Transferencia bancaria',
            default => 'Mercado Pago',
        };

        session()->flash('success', 'Checkout listo para integrar con ' . $proveedor . ' en el plan ' . $this->plan['nombre'] . '.');
    }

    public function render()
    {
        return view('livewire.billing.checkout', [
            'user' => Auth::user(),
        ]);
    }
}