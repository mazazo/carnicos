<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Show extends Component
{
    public Animal $animal;

    public function mount(Animal $animal): void
    {
        abort_unless((int) $animal->carniceria_id === (int) Auth::user()->carniceria_id, 403);
        $this->animal = $animal->load(['animalType', 'cuts']);
    }

    public function getRendimientoKgProperty(): float
    {
        return (float) $this->animal->cuts->sum('peso');
    }

    public function getRendimientoPorcentajeProperty(): float
    {
        if ((float) $this->animal->peso_total <= 0) {
            return 0;
        }

        return ($this->rendimientoKg / (float) $this->animal->peso_total) * 100;
    }

    public function getValorTotalProperty(): float
    {
        return (float) $this->animal->cuts->sum(function ($cut) {
            return (float) $cut->peso * (float) ($cut->precio_kg ?? 0);
        });
    }

    public function render()
    {
        return view('livewire.animals.show');
    }
}
