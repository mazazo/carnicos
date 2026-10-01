<?php

namespace App\Livewire\Cuenta;

use App\Models\AnimalType;
use App\Services\Suscripciones;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * La carnicería elige con qué tipos de animal trabaja (Plan 1: uno, Plan 2:
 * dos). Lo elige el dueño cuando todavía no eligió (o eligió de más tras
 * bajar de plan); después, cambiarlo lo hace el administrador.
 */
#[Layout('layouts.app')]
class TiposAnimal extends Component
{
    /** @var list<int|string> */
    public array $seleccion = [];

    public function mount(): void
    {
        $this->seleccion = Auth::user()->carniceria?->tiposAnimal()->pluck('animal_types.id')->map(fn ($id) => (string) $id)->all() ?? [];
    }

    public function guardar(Suscripciones $suscripciones)
    {
        $user = Auth::user();
        $carniceria = $user->carniceria;
        abort_unless($carniceria && $user->esDueno() && $carniceria->debeElegirTiposAnimal(), 403);

        $max = (int) $carniceria->planVigente()->max_tipos_animal;
        $ids = AnimalType::query()->where('activo', true)->whereIn('id', $this->seleccion)->pluck('id');

        if ($ids->count() !== $max) {
            throw ValidationException::withMessages([
                'seleccion' => $max === 1 ? 'Elegí 1 tipo de animal.' : "Elegí {$max} tipos de animal.",
            ]);
        }

        $carniceria->tiposAnimal()->sync($ids->all());
        $nombres = AnimalType::query()->whereIn('id', $ids)->orderBy('nombre')->pluck('nombre')->join(', ');
        $suscripciones->movimiento($carniceria, $carniceria->suscripcionVigente(), 'tipos_animal', "Eligió: {$nombres}.", $user);

        session()->flash('success', "Listo: trabajás con {$nombres}.");

        return $this->redirectRoute($user->dashboardRouteName());
    }

    public function render()
    {
        $user = Auth::user();
        $carniceria = $user->carniceria;

        return view('livewire.cuenta.tipos-animal', [
            'tipos' => AnimalType::query()->where('activo', true)->orderBy('nombre')->get(),
            'plan' => $carniceria?->planVigente(),
            'puedeElegir' => $carniceria && $user->esDueno() && $carniceria->debeElegirTiposAnimal(),
            'debeElegir' => (bool) $carniceria?->debeElegirTiposAnimal(),
        ]);
    }
}
