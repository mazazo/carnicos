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
    public bool $showCutModal = false;

    public ?int $editingCutId = null;

    public ?int $selectedAnimalTypeId = null;

    public string $cutNombre = '';

    public int $cutCantidadEsperada = 1;

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);
    }

    public function openCreateCutModal(int $animalTypeId): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        AnimalType::query()->findOrFail($animalTypeId);

        $this->resetCutForm();
        $this->selectedAnimalTypeId = $animalTypeId;
        $this->showCutModal = true;
    }

    public function openEditCutModal(int $cutCatalogId): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $cut = CutCatalog::withoutGlobalScope('carniceria')->findOrFail($cutCatalogId);

        $this->editingCutId = (int) $cut->id;
        $this->selectedAnimalTypeId = (int) $cut->animal_type_id;
        $this->cutNombre = (string) $cut->nombre_canonico;
        $this->cutCantidadEsperada = max(1, (int) ($cut->cantidad_esperada ?? 1));
        $this->showCutModal = true;
    }

    public function closeCutModal(): void
    {
        $this->resetCutForm();
    }

    public function saveCut(): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $validated = $this->validate([
            'selectedAnimalTypeId' => ['required', 'integer', 'exists:animal_types,id'],
            'cutNombre' => ['required', 'string', 'max:120'],
            'cutCantidadEsperada' => ['required', 'integer', 'min:1', 'max:999'],
        ], [], [
            'selectedAnimalTypeId' => 'tipo de animal',
            'cutNombre' => 'nombre del corte',
            'cutCantidadEsperada' => 'cantidad esperada',
        ]);

        if ($this->editingCutId !== null) {
            $cut = CutCatalog::withoutGlobalScope('carniceria')->findOrFail($this->editingCutId);
            $cut->update([
                'animal_type_id' => (int) $validated['selectedAnimalTypeId'],
                'nombre_canonico' => trim((string) $validated['cutNombre']),
                'cantidad_esperada' => (int) $validated['cutCantidadEsperada'],
            ]);
        } else {
            CutCatalog::query()->create([
                'carniceria_id' => null, // catálogo general
                'user_id' => null,
                'animal_type_id' => (int) $validated['selectedAnimalTypeId'],
                'nombre_canonico' => trim((string) $validated['cutNombre']),
                'cantidad_esperada' => (int) $validated['cutCantidadEsperada'],
                'activo' => true,
            ]);
        }

        $this->resetCutForm();
    }

    private function resetCutForm(): void
    {
        $this->showCutModal = false;
        $this->editingCutId = null;
        $this->selectedAnimalTypeId = null;
        $this->cutNombre = '';
        $this->cutCantidadEsperada = 1;
        $this->resetValidation();
    }

    public function cambiarEstadoCorte(int $cutCatalogId, bool $activo): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $cut = CutCatalog::withoutGlobalScope('carniceria')->findOrFail($cutCatalogId);
        $cut->update(['activo' => $activo]);
    }

    public function render()
    {
        $animalTypes = AnimalType::query()
            ->with(['cutCatalogs' => fn ($query) => $query->withoutGlobalScope('carniceria')->with('user')->orderBy('nombre_canonico')])
            ->withCount(['cutCatalogs' => fn ($query) => $query->withoutGlobalScope('carniceria')])
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
