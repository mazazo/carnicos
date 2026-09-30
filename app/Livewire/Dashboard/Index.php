<?php

namespace App\Livewire\Dashboard;

use App\Models\Animal;
use App\Models\CutCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $variant = 'enterprise';
    public string $rangoResumen = 'semana';

    private function rangoFechas(): array
    {
        return match ($this->rangoResumen) {
            'dia' => [Carbon::today()->toDateString(), Carbon::today()->toDateString()],
            'mes' => [Carbon::now()->startOfMonth()->toDateString(), Carbon::now()->endOfMonth()->toDateString()],
            default => [Carbon::now()->startOfWeek()->toDateString(), Carbon::now()->endOfWeek()->toDateString()],
        };
    }

    private function currentUserId(): int
    {
        return (int) Auth::id();
    }

    private function resolveAnimalTypeIds(array $keywords): array
    {
        return \App\Models\AnimalType::query()
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $index => $keyword) {
                    $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                    $query->{$method}('LOWER(nombre) like ?', ['%' . mb_strtolower($keyword) . '%']);
                }
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function keywordsForKind(string $kind): array
    {
        return match ($kind) {
            'vacuno' => ['vacuno', 'vaca', 'bovino'],
            'porcino' => ['porcino', 'cerdo', 'chancho'],
            'avicola' => ['avicola', 'aviar', 'pollo', 'ave'],
            default => [],
        };
    }

    private function loadCutCatalogByKind(): array
    {
        $userId = $this->currentUserId();

        $keywordsByKind = [
            'vacuno' => ['vacuno', 'vaca', 'bovino'],
            'porcino' => ['porcino', 'cerdo', 'chancho'],
            'avicola' => ['avicola', 'aviar', 'pollo', 'ave'],
        ];

        $catalogByKind = [];

        foreach ($keywordsByKind as $kind => $keywords) {
            $animalTypeIds = $this->resolveAnimalTypeIds($keywords);

            $baseCatalog = CutCatalog::query()
                ->whereIn('animal_type_id', $animalTypeIds)
                ->whereNull('user_id')
                ->orderBy('nombre_canonico')
                ->get(['id', 'animal_type_id', 'user_id', 'nombre_canonico', 'cantidad_esperada', 'activo']);

            $userCatalog = CutCatalog::query()
                ->whereIn('animal_type_id', $animalTypeIds)
                ->where('user_id', $userId)
                ->orderBy('nombre_canonico')
                ->get(['id', 'animal_type_id', 'user_id', 'nombre_canonico', 'cantidad_esperada', 'activo']);

            $effectiveByName = [];

            foreach ($baseCatalog as $cut) {
                $key = mb_strtolower(trim((string) $cut->nombre_canonico));
                $effectiveByName[$key] = $cut;
            }

            foreach ($userCatalog as $cut) {
                $key = mb_strtolower(trim((string) $cut->nombre_canonico));
                $effectiveByName[$key] = $cut;
            }

            $catalogIds = collect(array_values($effectiveByName))->pluck('id')->all();

            $lastPrices = [];
            if (!empty($catalogIds)) {
                $maxIds = DB::table('desposte_cortes')
                    ->whereIn('cut_catalog_id', $catalogIds)
                    ->whereNotNull('precio_kg')
                    ->selectRaw('cut_catalog_id, MAX(id) as max_id')
                    ->groupBy('cut_catalog_id')
                    ->pluck('max_id', 'cut_catalog_id')
                    ->all();

                if (!empty($maxIds)) {
                    $lastPrices = DB::table('desposte_cortes')
                        ->whereIn('id', array_values($maxIds))
                        ->pluck('precio_kg', 'cut_catalog_id')
                        ->map(fn ($v) => round((float) $v, 2))
                        ->all();
                }
            }

            $catalogByKind[$kind] = collect(array_values($effectiveByName))
                ->sortBy(fn ($cut) => mb_strtolower((string) $cut->nombre_canonico))
                ->map(fn ($cut) => [
                    'id' => (int) $cut->id,
                    'nombre' => (string) $cut->nombre_canonico,
                    'cantidad_esperada' => (int) ($cut->cantidad_esperada ?? 1),
                    'activo' => (bool) $cut->activo,
                    'is_user_defined' => $cut->user_id !== null,
                    'ultimo_precio' => $lastPrices[(int) $cut->id] ?? null,
                ])
                ->values()
                ->all();
        }

        return $catalogByKind;
    }

    public function mount(string $variant = 'enterprise'): void
    {
        $this->variant = in_array($variant, ['pro', 'enterprise'], true)
            ? $variant
            : 'enterprise';
    }

    private function recentAnimalsByKeywords(int $userId, array $keywords)
    {
        return Animal::query()
            ->with('animalType')
            ->withCount('cuts')
            ->where('user_id', $userId)
            ->whereHas('animalType', function ($query) use ($keywords) {
                $query->where(function ($inner) use ($keywords) {
                    foreach ($keywords as $index => $keyword) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $inner->{$method}('nombre', 'like', '%' . mb_strtolower($keyword) . '%');
                    }
                });
            })
            ->latest('fecha')
            ->latest('id')
            ->take(8)
            ->get();
    }

    public function darAltaCorteCatalogo(int $cutCatalogId): void
    {
        $userId = $this->currentUserId();
        $cut = CutCatalog::query()->findOrFail($cutCatalogId);

        if ((int) ($cut->user_id ?? 0) === $userId) {
            $cut->update(['activo' => true]);
            return;
        }

        if ($cut->user_id !== null) {
            abort(403);
        }

        CutCatalog::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'animal_type_id' => (int) $cut->animal_type_id,
                'nombre_canonico' => (string) $cut->nombre_canonico,
            ],
            [
                'cantidad_esperada' => (int) ($cut->cantidad_esperada ?? 1),
                'activo' => true,
            ]
        );
    }

    public function darBajaCorteCatalogo(int $cutCatalogId): void
    {
        $userId = $this->currentUserId();
        $cut = CutCatalog::query()->findOrFail($cutCatalogId);

        if ((int) ($cut->user_id ?? 0) === $userId) {
            $cut->update(['activo' => false]);
            return;
        }

        if ($cut->user_id !== null) {
            abort(403);
        }

        CutCatalog::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'animal_type_id' => (int) $cut->animal_type_id,
                'nombre_canonico' => (string) $cut->nombre_canonico,
            ],
            [
                'cantidad_esperada' => (int) ($cut->cantidad_esperada ?? 1),
                'activo' => false,
            ]
        );
    }

    public function agregarCorteCatalogo(string $kind, string $nombre, int $cantidadEsperada = 1): void
    {
        $userId = $this->currentUserId();

        $kind = mb_strtolower(trim($kind));
        $nombre = trim($nombre);
        $cantidadEsperada = max(1, $cantidadEsperada);

        if (!in_array($kind, ['vacuno', 'porcino', 'avicola'], true)) {
            throw ValidationException::withMessages(['kind' => 'Tipo de corte inválido.']);
        }

        if ($nombre === '') {
            throw ValidationException::withMessages(['nombre' => 'Ingresá un nombre de corte.']);
        }

        $animalTypeId = $this->resolveAnimalTypeIds($this->keywordsForKind($kind))[0] ?? null;

        if ($animalTypeId === null) {
            throw ValidationException::withMessages(['kind' => 'No se encontró tipo de animal para esta categoría.']);
        }

        CutCatalog::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'animal_type_id' => (int) $animalTypeId,
                'nombre_canonico' => $nombre,
            ],
            [
                'cantidad_esperada' => $cantidadEsperada,
                'activo' => true,
            ]
        );
    }

    public function render()
    {
        $userId = Auth::id();
        $cutCatalogByKind = $this->loadCutCatalogByKind();

        [$fechaDesde, $fechaHasta] = $this->rangoFechas();

        $vacunoIds = $this->resolveAnimalTypeIds(['vacuno', 'vaca', 'bovino']);
        $porcinoIds = $this->resolveAnimalTypeIds(['porcino', 'cerdo', 'chancho']);
        $avicolaIds = $this->resolveAnimalTypeIds(['avicola', 'aviar', 'pollo', 'ave']);

        $vacunoCount = Animal::query()
            ->where('user_id', $userId)
            ->whereIn('animal_type_id', $vacunoIds)
            ->whereBetween('fecha', [$fechaDesde, $fechaHasta])
            ->count();

        $porcinoCount = Animal::query()
            ->where('user_id', $userId)
            ->whereIn('animal_type_id', $porcinoIds)
            ->whereBetween('fecha', [$fechaDesde, $fechaHasta])
            ->count();

        $cajonesCount = Animal::query()
            ->where('user_id', $userId)
            ->whereIn('animal_type_id', $avicolaIds)
            ->whereBetween('fecha', [$fechaDesde, $fechaHasta])
            ->count();

        $totalKgProcesados = Animal::query()
            ->where('user_id', $userId)
            ->whereBetween('fecha', [$fechaDesde, $fechaHasta])
            ->sum('peso_total');

        $resesCount = $vacunoCount + $porcinoCount;
        $produccionSemana = $resesCount + $cajonesCount;

        $vacunoAnimals = $this->recentAnimalsByKeywords($userId, ['vacuno', 'vaca', 'bovino']);
        $porcinoAnimals = $this->recentAnimalsByKeywords($userId, ['porcino', 'cerdo', 'chancho']);
        $avicolaAnimals = $this->recentAnimalsByKeywords($userId, ['avicola', 'pollo', 'ave']);

        return view('livewire.dashboard.' . $this->variant, [
            'variant' => $this->variant,
            'reses_count' => $resesCount,
            'vacuno_count' => $vacunoCount,
            'porcino_count' => $porcinoCount,
            'cajones_count' => $cajonesCount,
            'produccion_semana' => $produccionSemana,
            'total_kg_procesados' => $totalKgProcesados,
            'vacuno_animals' => $vacunoAnimals,
            'porcino_animals' => $porcinoAnimals,
            'avicola_animals' => $avicolaAnimals,
            'cut_catalog_by_kind' => $cutCatalogByKind,
        ]);
    }
}
