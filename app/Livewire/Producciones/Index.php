<?php

namespace App\Livewire\Producciones;

use App\Http\Controllers\Api\ProduccionController;
use App\Models\AnimalType;
use App\Models\Desposte;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Todas las producciones (despostes) con sus valores: kg, rinde, merma, costo,
 * venta, ganancia y margen. Los terminados muestran el cálculo guardado al
 * terminarlos; los pendientes, lo cargado hasta ahora con los precios actuales.
 */
class Index extends Component
{
    use WithPagination;

    public const PERIODOS = ['hoy' => 'Hoy', 'ayer' => 'Ayer', 'semana' => 'Últimos 7 días', 'mes' => 'Este mes', 'todo' => 'Todo'];

    #[Url(as: 'tipo')]
    public string $filtroTipo = '';

    #[Url(as: 'estado')]
    public string $filtroEstado = 'terminados';

    #[Url(as: 'periodo')]
    public string $periodo = 'mes';

    public function updating(string $propiedad): void
    {
        if (in_array($propiedad, ['filtroTipo', 'filtroEstado', 'periodo'], true)) {
            $this->resetPage();
        }
    }

    private function usuario(): User
    {
        /** @var User */
        return Auth::user();
    }

    /** @return array{0: ?string, 1: ?string} */
    private function rango(): array
    {
        $hoy = today();

        return match ($this->periodo) {
            'hoy' => [$hoy->toDateString(), $hoy->toDateString()],
            'ayer' => [$hoy->copy()->subDay()->toDateString(), $hoy->copy()->subDay()->toDateString()],
            'semana' => [$hoy->copy()->subDays(6)->toDateString(), $hoy->toDateString()],
            'mes' => [$hoy->copy()->startOfMonth()->toDateString(), $hoy->toDateString()],
            default => [null, null],
        };
    }

    private function consulta(): Builder
    {
        $tipos = array_map('intval', $this->usuario()->carniceria?->tiposAnimalHabilitadosIds() ?? []);
        [$desde, $hasta] = $this->rango();

        return Desposte::query()
            ->whereIn('animal_type_id', $tipos)
            ->when((int) $this->filtroTipo, fn ($q) => $q->where('animal_type_id', (int) $this->filtroTipo))
            ->when($this->filtroEstado === 'terminados', fn ($q) => $q->where('estado', Desposte::TERMINADO))
            ->when($this->filtroEstado === 'pendientes', fn ($q) => $q->where('estado', Desposte::PENDIENTE))
            ->when($desde, fn ($q) => $q->whereDate('fecha_desposte', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha_desposte', '<=', $hasta));
    }

    public function render()
    {
        $tipos = AnimalType::query()
            ->whereIn('id', $this->usuario()->carniceria?->tiposAnimalHabilitadosIds() ?? [])
            ->orderBy('id')->get();

        $pagina = $this->consulta()
            ->with(['animales', 'cortes', 'pesadas', 'animalType'])
            ->latest('fecha_desposte')->latest('id')
            ->paginate(25);

        // Totales de los terminados del filtro, con el cálculo guardado.
        $totales = $this->consulta()->where('estado', Desposte::TERMINADO)
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(kg_medias), 0) as kg_medias, COALESCE(SUM(kg_cortes), 0) as kg_cortes,
                COALESCE(SUM(kg_merma), 0) as kg_merma, COALESCE(SUM(costo), 0) as costo, COALESCE(SUM(venta), 0) as venta,
                COALESCE(SUM(ganancia), 0) as ganancia')
            ->first();

        return view('livewire.producciones.index', [
            'tipos' => $tipos,
            'producciones' => $pagina,
            'filas' => $pagina->getCollection()->map(function (Desposte $d) {
                $r = ProduccionController::resumen($d);

                return $r + [
                    'kg_merma' => max(0, $r['peso_medias'] - $r['peso_cortes']),
                    'margen' => $r['costo_total'] > 0 ? $r['diferencia'] / $r['costo_total'] * 100 : null,
                ];
            }),
            'totales' => $totales,
            'periodos' => self::PERIODOS,
        ]);
    }
}
