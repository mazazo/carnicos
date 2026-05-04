<?php

namespace App\Livewire\Admin;

use App\Models\AnimalType;
use App\Models\Cut;
use App\Models\CutCatalog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CorteTipoAudit extends Component
{
    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);
    }

    public function cambiarEstadoCorte(int $cutCatalogId, bool $activo): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $cut = CutCatalog::query()->findOrFail($cutCatalogId);
        $cut->update(['activo' => $activo]);
    }

    public function render()
    {
        $animalTypes = AnimalType::query()
            ->with(['cutCatalogs' => fn ($query) => $query->with('user')->orderBy('nombre_canonico')])
            ->withCount('cutCatalogs')
            ->orderBy('nombre')
            ->get();

        $mismatchCount = Cut::query()
            ->join('animals', 'animals.id', '=', 'cuts.animal_id')
            ->whereNotNull('cuts.animal_type_id')
            ->whereColumn('cuts.animal_type_id', '!=', 'animals.animal_type_id')
            ->count();

        return view('livewire.admin.corte-tipo-audit', [
            'animalTypes' => $animalTypes,
            'mismatchCount' => $mismatchCount,
        ]);
    }
}
