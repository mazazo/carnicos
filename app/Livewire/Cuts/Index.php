<?php

namespace App\Livewire\Cuts;

use App\Models\Cut;
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

    public function deleteCut(int $cutId): void
    {
        $cut = Cut::query()
            ->where('id', $cutId)
            ->whereHas('animal', fn ($query) => $query->where('user_id', Auth::id()))
            ->firstOrFail();

        $cut->delete();

        session()->flash('success', 'Corte eliminado correctamente.');
    }

    public function render()
    {
        $cuts = Cut::query()
            ->with(['animal.animalType'])
            ->whereHas('animal', fn ($query) => $query->where('user_id', Auth::id()))
            ->when($this->search !== '', function ($query) {
                $query->where(function ($inner) {
                    $inner
                        ->where('nombre', 'like', '%' . $this->search . '%')
                        ->orWhereHas('animal', function ($animalQuery) {
                            $animalQuery
                                ->where('fecha', 'like', '%' . $this->search . '%')
                                ->orWhereHas('animalType', fn ($typeQuery) => $typeQuery->where('nombre', 'like', '%' . $this->search . '%'));
                        });
                });
            })
            ->latest('id')
            ->paginate(10);

        return view('livewire.cuts.index', [
            'cuts' => $cuts,
        ]);
    }
}
