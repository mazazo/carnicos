<?php

namespace App\Livewire\Billing;

use App\Models\Payment;
use App\Support\Billing\PlanCatalog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Página de planes: estado de la suscripción de la carnicería (prueba, plan,
 * vencimiento) y los planes para contratar o renovar.
 */
#[Layout('layouts.app')]
class Plans extends Component
{
    public function render()
    {
        $user = Auth::user();
        $carniceria = $user->carniceria;
        $vigente = $carniceria?->suscripcionVigente();

        return view('livewire.billing.plans', [
            'planes' => PlanCatalog::all(),
            'planActual' => $vigente?->planModel?->codigo,
            'vigente' => $vigente,
            'carniceria' => $carniceria,
            'user' => $user,
            'pendientes' => $carniceria
                ? $carniceria->pagos()->where('status', Payment::PENDING)->with('plan')->get()
                : collect(),
        ]);
    }
}
