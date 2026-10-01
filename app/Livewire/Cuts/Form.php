<?php

namespace App\Livewire\Cuts;

use App\Models\Animal;
use App\Models\Cut;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Form extends Component
{
    public ?Cut $cut = null;
    public string $animal_id = '';
    public string $nombre = '';
    public string $peso = '';
    public string $precio_kg = '';

    public function mount(?Cut $cut = null): void
    {
        if ($cut) {
            $cut->load('animal');
            abort_unless((int) $cut->animal?->carniceria_id === (int) Auth::user()->carniceria_id, 403);

            $this->cut = $cut;
            $this->animal_id = (string) $cut->animal_id;
            $this->nombre = (string) $cut->nombre;
            $this->peso = (string) $cut->peso;
            $this->precio_kg = $cut->precio_kg !== null ? (string) $cut->precio_kg : '';
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'animal_id' => ['required', 'exists:animals,id'],
            'nombre' => ['required', 'string', 'max:120'],
            'peso' => ['required', 'numeric', 'gt:0'],
            'precio_kg' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $animal = Animal::query()
            ->where('id', $data['animal_id'])
            ->firstOrFail();

        if ($this->cut) {
            abort_unless((int) $this->cut->animal?->carniceria_id === (int) Auth::user()->carniceria_id, 403);

            $this->cut->update([
                'animal_id' => (int) $animal->id,
                'animal_type_id' => (int) $animal->animal_type_id,
                'nombre' => $data['nombre'],
                'peso' => (float) $data['peso'],
                'precio_kg' => $data['precio_kg'] !== '' ? (float) $data['precio_kg'] : null,
            ]);

            session()->flash('success', 'Corte actualizado correctamente.');
        } else {
            Cut::query()->create([
                'animal_id' => (int) $animal->id,
                'animal_type_id' => (int) $animal->animal_type_id,
                'nombre' => $data['nombre'],
                'peso' => (float) $data['peso'],
                'precio_kg' => $data['precio_kg'] !== '' ? (float) $data['precio_kg'] : null,
            ]);

            session()->flash('success', 'Corte creado correctamente.');
        }

        $this->redirectRoute('cuts.index', navigate: true);
    }

    public function render()
    {
        $animals = Animal::query()
            ->with('animalType')
            ->latest('fecha')
            ->latest('id')
            ->get();

        return view('livewire.cuts.form', [
            'animals' => $animals,
        ]);
    }
}
