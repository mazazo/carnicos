<?php

namespace App\Livewire\Admin;

use App\Models\Payment;
use App\Models\User;
use App\Services\Suscripciones;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Pagos de todas las carnicerías; los avisos de transferencia se aprueban o rechazan acá. */
#[Layout('layouts.app')]
class Pagos extends Component
{
    use WithPagination;

    #[Url]
    public string $estado = Payment::PENDING;

    public string $motivo = '';

    public function boot(): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);
    }

    public function updatedEstado(): void
    {
        $this->resetPage();
    }

    public function aprobar(int $paymentId, Suscripciones $suscripciones): void
    {
        $payment = Payment::query()->where('status', Payment::PENDING)->findOrFail($paymentId);
        $suscripciones->confirmarPago($payment, Auth::user());
        session()->flash('success', "Pago #{$payment->id} aprobado; plan activado.");
    }

    public function rechazar(int $paymentId, Suscripciones $suscripciones): void
    {
        $payment = Payment::query()->where('status', Payment::PENDING)->findOrFail($paymentId);
        $suscripciones->rechazarPago($payment, trim($this->motivo) ?: 'sin detalle', Auth::user());
        $this->reset('motivo');
        session()->flash('success', "Pago #{$payment->id} rechazado.");
    }

    public function render()
    {
        return view('livewire.admin.pagos', [
            'pagos' => Payment::query()
                ->with(['carniceria', 'plan', 'user'])
                ->when($this->estado !== '', fn ($q) => $q->where('status', $this->estado))
                ->latest('id')
                ->paginate(30),
        ]);
    }
}
