<?php

namespace App\Livewire\Admin;

use App\Models\Carniceria;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Clientes de la plataforma: estado de prueba/plan, vencimiento y pagos pendientes. */
#[Layout('layouts.app')]
class Carnicerias extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $busqueda = '';

    #[Url]
    public string $estado = '';

    public function boot(): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);
    }

    public function updated(string $propiedad): void
    {
        if (in_array($propiedad, ['busqueda', 'estado'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $vigentes = fn (Builder $q, ?string $status = null) => $q->vigentes()->when($status, fn ($q) => $q->where('status', $status));

        $carnicerias = Carniceria::query()
            ->with(['dueno', 'suscripciones' => fn ($q) => $q->vigentes()->with('planModel')])
            ->withCount(['usuarios', 'pagos as pagos_pendientes_count' => fn ($q) => $q->where('status', Payment::PENDING)])
            ->when(trim($this->busqueda) !== '', function (Builder $q) {
                $texto = '%'.trim($this->busqueda).'%';
                $q->where(fn ($q) => $q->where('nombre', 'like', $texto)
                    ->orWhere('cuit', 'like', $texto)
                    ->orWhere('email', 'like', $texto)
                    ->orWhereHas('usuarios', fn ($q) => $q->where('email', 'like', $texto)));
            })
            ->when($this->estado === 'prueba', fn ($q) => $q->whereHas('suscripciones', fn ($s) => $vigentes($s, Subscription::TRIAL)))
            ->when($this->estado === 'activa', fn ($q) => $q->whereHas('suscripciones', fn ($s) => $vigentes($s, Subscription::ACTIVE)))
            ->when($this->estado === 'sin_acceso', fn ($q) => $q->whereDoesntHave('suscripciones', fn ($s) => $vigentes($s)))
            ->when($this->estado === 'suspendida', fn ($q) => $q->where('estado', Carniceria::ESTADO_SUSPENDIDA))
            ->when($this->estado === 'pago_pendiente', fn ($q) => $q->whereHas('pagos', fn ($p) => $p->where('status', Payment::PENDING)))
            ->orderBy('nombre')
            ->paginate(25);

        return view('livewire.admin.carnicerias', ['carnicerias' => $carnicerias]);
    }
}
