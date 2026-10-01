<?php

namespace App\Livewire\Admin;

use App\Models\AnimalType;
use App\Models\Carniceria;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\Suscripciones;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Ficha de una carnicería para el administrador: sumar días de prueba,
 * habilitar un plan a mano, registrar/aprobar pagos, suspender y ajustar los
 * tipos de animal. Todo pasa por el servicio Suscripciones (queda historial).
 */
#[Layout('layouts.app')]
class CarniceriaShow extends Component
{
    #[Locked]
    public int $carniceriaId;

    public ?int $dias = 5;

    public ?int $planId = null;

    public ?int $planMeses = 1;

    public ?int $planDias = 0;

    public ?int $pagoPlanId = null;

    public ?int $pagoMeses = 1;

    public ?string $pagoMonto = null;

    public string $pagoMetodo = 'transferencia';

    public string $pagoReferencia = '';

    public string $motivo = '';

    /** @var list<int|string> */
    public array $tipos = [];

    public function boot(): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);
    }

    public function mount(Carniceria $carniceria): void
    {
        $this->carniceriaId = $carniceria->id;
        $plan = $carniceria->planVigente() ?? Plan::query()->activos()->first();
        $this->planId = $plan?->id;
        $this->pagoPlanId = $plan?->id;
        $this->tipos = $carniceria->tiposAnimal()->pluck('animal_types.id')->map(fn ($id) => (string) $id)->all();
    }

    private function carniceria(): Carniceria
    {
        return Carniceria::query()->findOrFail($this->carniceriaId);
    }

    public function sumarDias(Suscripciones $suscripciones): void
    {
        $this->validate(['dias' => ['required', 'integer', 'min:1', 'max:365']]);
        $suscripciones->sumarDias($this->carniceria(), (int) $this->dias, Auth::user());
        session()->flash('success', "Se sumaron {$this->dias} días.");
    }

    public function habilitarPlan(Suscripciones $suscripciones): void
    {
        $this->validate([
            'planId' => ['required', Rule::exists('planes', 'id')],
            'planMeses' => ['nullable', 'integer', 'min:0', 'max:36'],
            'planDias' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        if ((int) $this->planMeses <= 0 && (int) $this->planDias <= 0) {
            throw ValidationException::withMessages(['planMeses' => 'Indicá meses o días.']);
        }

        $plan = Plan::query()->findOrFail($this->planId);
        $suscripciones->activarPlan($this->carniceria(), $plan, (int) $this->planMeses, (int) $this->planDias, 'manual', null, Auth::user());
        session()->flash('success', "Plan {$plan->nombre} habilitado.");
    }

    /** Pago recibido por fuera (transferencia, efectivo): se registra y activa el período. */
    public function registrarPago(Suscripciones $suscripciones): void
    {
        $this->validate([
            'pagoPlanId' => ['required', Rule::exists('planes', 'id')],
            'pagoMeses' => ['required', 'integer', 'min:1', 'max:36'],
            'pagoMonto' => ['required', 'numeric', 'min:0'],
            'pagoMetodo' => ['required', Rule::in(['transferencia', 'efectivo', 'otro'])],
            'pagoReferencia' => ['nullable', 'string', 'max:100'],
        ]);

        $carniceria = $this->carniceria();
        $payment = Payment::query()->create([
            'carniceria_id' => $carniceria->id,
            'plan_id' => $this->pagoPlanId,
            'meses' => (int) $this->pagoMeses,
            'amount' => $this->pagoMonto,
            'currency' => config('carnicos.moneda'),
            'status' => Payment::PENDING,
            'method' => $this->pagoMetodo,
            'proveedor' => 'manual',
            'reference' => trim($this->pagoReferencia) ?: null,
            'registrado_por' => Auth::id(),
        ]);

        $suscripciones->confirmarPago($payment, Auth::user());
        $this->reset('pagoMonto', 'pagoReferencia');
        session()->flash('success', 'Pago registrado y plan activado.');
    }

    public function aprobarPago(int $paymentId, Suscripciones $suscripciones): void
    {
        $payment = $this->carniceria()->pagos()->where('status', Payment::PENDING)->findOrFail($paymentId);
        $suscripciones->confirmarPago($payment, Auth::user());
        session()->flash('success', "Pago #{$payment->id} aprobado.");
    }

    public function rechazarPago(int $paymentId, Suscripciones $suscripciones): void
    {
        $payment = $this->carniceria()->pagos()->where('status', Payment::PENDING)->findOrFail($paymentId);
        $suscripciones->rechazarPago($payment, trim($this->motivo) ?: 'sin detalle', Auth::user());
        $this->reset('motivo');
        session()->flash('success', "Pago #{$payment->id} rechazado.");
    }

    public function suspender(Suscripciones $suscripciones): void
    {
        $this->validate(['motivo' => ['required', 'string', 'max:200']], ['motivo.required' => 'Indicá el motivo.']);
        $suscripciones->suspender($this->carniceria(), trim($this->motivo), Auth::user());
        $this->reset('motivo');
        session()->flash('success', 'Carnicería suspendida.');
    }

    public function reactivar(Suscripciones $suscripciones): void
    {
        $suscripciones->reactivar($this->carniceria(), Auth::user());
        session()->flash('success', 'Carnicería reactivada.');
    }

    public function guardarTipos(Suscripciones $suscripciones): void
    {
        $this->validate([
            'tipos' => ['array'],
            'tipos.*' => [Rule::exists('animal_types', 'id')],
        ]);

        $carniceria = $this->carniceria();
        $max = $carniceria->planVigente()?->max_tipos_animal;
        $ids = array_map('intval', $this->tipos);

        if ($max !== null && count($ids) > $max) {
            throw ValidationException::withMessages(['tipos' => "Su plan permite {$max} tipo(s) de animal."]);
        }

        $carniceria->tiposAnimal()->sync($ids);
        $nombres = AnimalType::query()->whereIn('id', $ids)->pluck('nombre')->implode(', ');
        $suscripciones->movimiento($carniceria, null, 'tipos_animal', 'Tipos de animal (admin): '.($nombres ?: 'ninguno'), Auth::user());
        session()->flash('success', 'Tipos de animal actualizados.');
    }

    public function render()
    {
        $carniceria = $this->carniceria();

        return view('livewire.admin.carniceria-show', [
            'carniceria' => $carniceria,
            'vigente' => $carniceria->suscripcionVigente(),
            'usuarios' => $carniceria->usuarios()->orderBy('rol')->orderBy('name')->get(),
            'pagos' => $carniceria->pagos()->with('plan')->limit(30)->get(),
            'movimientos' => $carniceria->movimientos()->with('user')->limit(30)->get(),
            'planes' => Plan::query()->orderBy('orden')->get(),
            'tiposDisponibles' => AnimalType::query()->where('activo', true)->orderBy('nombre')->get(),
        ]);
    }
}
