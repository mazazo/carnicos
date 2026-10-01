<?php

namespace App\Livewire\Dashboard;

use App\Http\Controllers\Api\ProduccionController;
use App\Models\Animal;
use App\Models\CutCatalog;
use App\Models\Desposte;
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

    /** Carnicería del usuario: el catálogo propio y los animales son de ella. */
    private function currentCarniceriaId(): int
    {
        return (int) Auth::user()->carniceria_id;
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
        $keywordsByKind = [
            'vacuno' => ['vacuno', 'vaca', 'bovino'],
            'porcino' => ['porcino', 'cerdo', 'chancho'],
            'avicola' => ['avicola', 'aviar', 'pollo', 'ave'],
        ];

        $catalogByKind = [];

        foreach ($keywordsByKind as $kind => $keywords) {
            $animalTypeIds = $this->resolveAnimalTypeIds($keywords);

            $effectiveByName = [];
            foreach ($animalTypeIds as $animalTypeId) {
                foreach (CutCatalog::catalogoPara($this->currentCarniceriaId(), (int) $animalTypeId) as $cut) {
                    $effectiveByName[mb_strtolower(trim((string) $cut->nombre_canonico))] = $cut;
                }
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
                    'activo' => (bool) $cut->habilitado,
                    'is_user_defined' => $cut->esPropio(),
                    'ultimo_precio' => $lastPrices[(int) $cut->id] ?? null,
                ])
                ->values()
                ->all();
        }

        return $catalogByKind;
    }

    public function mount(string $variant = 'enterprise'): void
    {
        $user = Auth::user();

        // El dashboard con estadísticas es del plan que lo incluye (Plan 3).
        if ($variant === 'enterprise' && ! $user->isAdmin() && ! $user->tieneDashboardCompleto()) {
            $variant = 'pro';
        }

        $this->variant = in_array($variant, ['pro', 'enterprise'], true)
            ? $variant
            : 'enterprise';
    }

    private function recentAnimalsByKeywords(int $userId, array $keywords)
    {
        return Animal::query()
            ->with(['animalType', 'cutCatalog'])
            ->withCount('cuts')
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
        $this->corteDelCatalogo($cutCatalogId)->habilitarPara($this->currentCarniceriaId(), true);
    }

    public function darBajaCorteCatalogo(int $cutCatalogId): void
    {
        $this->corteDelCatalogo($cutCatalogId)->habilitarPara($this->currentCarniceriaId(), false);
    }

    /** Un corte general o propio de la carnicería (el scope global filtra los ajenos). */
    private function corteDelCatalogo(int $cutCatalogId): CutCatalog
    {
        return CutCatalog::query()->findOrFail($cutCatalogId);
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

        $carniceria = Auth::user()->carniceria;
        if ($animalTypeId !== null && $carniceria && ! in_array((int) $animalTypeId, $carniceria->tiposAnimalHabilitadosIds(), true)) {
            throw ValidationException::withMessages(['kind' => 'Tu plan no incluye este tipo de animal.']);
        }

        if ($animalTypeId === null) {
            throw ValidationException::withMessages(['kind' => 'No se encontró tipo de animal para esta categoría.']);
        }

        CutCatalog::agregarPropio($this->currentCarniceriaId(), (int) $animalTypeId, $nombre, $userId, $cantidadEsperada);
    }

    public function render()
    {
        $userId = Auth::id();
        $cutCatalogByKind = $this->loadCutCatalogByKind();

        [$fechaDesde, $fechaHasta] = $this->rangoFechas();

        $vacunoIds = $this->resolveAnimalTypeIds(['vacuno', 'vaca', 'bovino']);
        $porcinoIds = $this->resolveAnimalTypeIds(['porcino', 'cerdo', 'chancho']);
        $avicolaIds = $this->resolveAnimalTypeIds(['avicola', 'aviar', 'pollo', 'ave']);

        $vacunoCount = Animal::query()->sinPiezas()
            ->whereIn('animal_type_id', $vacunoIds)
            ->whereBetween('fecha', [$fechaDesde, $fechaHasta])
            ->count();

        $porcinoCount = Animal::query()->sinPiezas()
            ->whereIn('animal_type_id', $porcinoIds)
            ->whereBetween('fecha', [$fechaDesde, $fechaHasta])
            ->count();

        $cajonesCount = Animal::query()->sinPiezas()
            ->whereIn('animal_type_id', $avicolaIds)
            ->whereBetween('fecha', [$fechaDesde, $fechaHasta])
            ->count();

        $totalKgProcesados = Animal::query()->whereNull('desposte_origen_id') // las piezas de un desposte propio no son un ingreso nuevo
            ->whereBetween('fecha', [$fechaDesde, $fechaHasta])
            ->sum('peso_total');

        $resesCount = $vacunoCount + $porcinoCount;
        $produccionSemana = $resesCount + $cajonesCount;

        $vacunoAnimals = $this->recentAnimalsByKeywords($userId, ['vacuno', 'vaca', 'bovino']);
        $porcinoAnimals = $this->recentAnimalsByKeywords($userId, ['porcino', 'cerdo', 'chancho']);
        $avicolaAnimals = $this->recentAnimalsByKeywords($userId, ['avicola', 'pollo', 'ave']);

        // Producciones: pendientes (para seguirlas) y las últimas terminadas.
        $resumen = fn (Desposte $d) => ProduccionController::resumen($d);
        $pendientes = Desposte::query()->with(['animales', 'pesadas', 'animalType'])
            ->where('estado', Desposte::PENDIENTE)->whereNotNull('animal_type_id')
            ->latest('id')->take(5)->get()->map($resumen);
        $terminadas = Desposte::query()->with(['animales', 'cortes', 'animalType'])
            ->where('estado', Desposte::TERMINADO)->whereNotNull('animal_type_id')
            ->latest('fecha_desposte')->latest('id')->take(5)->get()->map($resumen);

        return view('livewire.dashboard.panel', [
            'variant' => $this->variant,
            'producciones_pendientes' => $pendientes,
            'producciones_terminadas' => $terminadas,
            // Stock sin despostar hoy (medias, cajones y piezas disponibles o en un desposte pendiente).
            'kg_restantes' => (float) Animal::query()
                ->whereIn('estado', [Animal::DISPONIBLE, Animal::EN_DESPOSTE])
                ->whereIn('animal_type_id', Auth::user()->carniceria?->tiposAnimalHabilitadosIds() ?? [])
                ->sum('peso_total'),
            // Cuartos y piezas grandes disponibles para cuartear, por tipo de animal.
            'piezas_disponibles' => Animal::query()->piezas()->disponibles()
                ->whereIn('animal_type_id', Auth::user()->carniceria?->tiposAnimalHabilitadosIds() ?? [])
                ->selectRaw('animal_type_id, COUNT(*) as cantidad, SUM(peso_total) as kg')
                ->groupBy('animal_type_id')->orderByDesc('cantidad')->get(),
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
