<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function deleteAnimal(int $animalId): void
    {
        $animal = Animal::query()
            ->where('id', $animalId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $animal->delete();

        session()->flash('success', 'Animal eliminado correctamente.');
    }

    public function render()
    {
        $animals = Animal::query()
            ->with(['animalType', 'cuts'])
            ->where('user_id', Auth::id())
            ->when($this->search !== '', function ($query) {
                $query->where(function ($inner) {
                    $inner
                        ->where('fecha', 'like', '%' . $this->search . '%')
                        ->orWhereHas('animalType', fn ($type) => $type->where('nombre', 'like', '%' . $this->search . '%'));
                });
            })
            ->latest('fecha')
            ->latest('id')
            ->paginate(10);

        return view('livewire.animals.index', [
            'animals' => $animals,
        ]);
    }
}
