<?php

namespace App\Livewire\Ingresos;

use App\Models\Animal;
use App\Models\AnimalType;
use App\Models\CutCatalog;
use App\Models\User;
use App\Support\Numero;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Ingresos de mercadería (res, media res o cajón) con sus filtros, y el alta
 * de un ingreso con los mismos datos que la app (queda disponible para producir).
 */
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'tipo')]
    public string $filtroTipo = '';

    #[Url(as: 'estado')]
    public string $filtroEstado = 'todos';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'nuevo')]
    public bool $nuevo = false;

    public ?int $tipoId = null;

    public string $formato = '';

    /** Corte primario cuando el formato es "pieza grande". */
    public string $piezaId = '';

    /** Lo que se elige en el desplegable: "res", "media_res", "cajon" o "pieza:<id>" (cuartos y demás piezas a la vista). */
    public string $formatoElegido = '';

    public string $cantidad = '1';

    public string $peso = '';

    public string $precio = '';

    public string $fecha = '';

    public string $proveedor = '';

    public string $frigorifico = '';

    public function mount(): void
    {
        $this->prepararFormulario();
    }

    private function usuario(): User
    {
        /** @var User */
        return Auth::user();
    }

    /** @return array<int, int> */
    private function tiposHabilitados(): array
    {
        return array_map('intval', $this->usuario()->carniceria?->tiposAnimalHabilitadosIds() ?? []);
    }

    private function prepararFormulario(): void
    {
        $tipos = $this->tiposHabilitados();
        if ($this->tipoId === null || ! in_array($this->tipoId, $tipos, true)) {
            $this->tipoId = (int) $this->filtroTipo && in_array((int) $this->filtroTipo, $tipos, true) ? (int) $this->filtroTipo : ($tipos[0] ?? null);
        }
        $this->formato = $this->formatos()[0];
        $this->piezaId = '';
        $this->formatoElegido = $this->formato;
        $this->fecha = today()->toDateString();
    }

    public function updatedTipoId(): void
    {
        $this->formato = $this->formatos()[0];
        $this->piezaId = '';
        $this->formatoElegido = $this->formato;
    }

    public function updatedFormatoElegido(string $valor): void
    {
        if (str_starts_with($valor, Animal::FORMATO_PIEZA.':')) {
            $this->formato = Animal::FORMATO_PIEZA;
            $this->piezaId = substr($valor, strlen(Animal::FORMATO_PIEZA) + 1);
        } else {
            $this->formato = $valor;
            $this->piezaId = '';
        }
    }

    /** Formatos del tipo elegido; con piezas grandes habilitadas, también "pieza". @return list<string> */
    private function formatos(): array
    {
        $formatos = Animal::formatosPara($this->tipoId ? AnimalType::find($this->tipoId) : null);
        if ($this->piezas()->isNotEmpty()) {
            $formatos[] = Animal::FORMATO_PIEZA;
        }

        return $formatos;
    }

    /** Piezas grandes habilitadas del tipo elegido. */
    private function piezas()
    {
        if ($this->tipoId === null || $this->usuario()->carniceria_id === null) {
            return collect();
        }

        return CutCatalog::catalogoPara((int) $this->usuario()->carniceria_id, $this->tipoId)
            ->filter(fn (CutCatalog $c) => $c->habilitado && $c->esPrimario())->values();
    }

    public function updating(string $propiedad): void
    {
        if (in_array($propiedad, ['filtroTipo', 'filtroEstado', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function abrirNuevo(): void
    {
        $this->nuevo = true;
        $this->prepararFormulario();
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $this->resetValidation();
        $tipo = AnimalType::query()->whereIn('id', $this->tiposHabilitados())->find($this->tipoId);
        if ($tipo === null) {
            throw ValidationException::withMessages(['tipoId' => 'Elegí un tipo de animal de tu plan.']);
        }

        $peso = Numero::leer($this->peso);
        $precio = Numero::leer($this->precio);

        if (! in_array($this->formato, $this->formatos(), true)) {
            throw ValidationException::withMessages(['formato' => 'Ese formato no está disponible para este animal.']);
        }

        $esPieza = $this->formato === Animal::FORMATO_PIEZA;
        $pieza = $esPieza ? $this->piezas()->firstWhere('id', (int) $this->piezaId) : null;
        if ($esPieza && $pieza === null) {
            throw ValidationException::withMessages(['piezaId' => 'Elegí qué pieza grande es.']);
        }

        validator([
            'formato' => $this->formato,
            'cantidad' => $this->cantidad,
            'peso' => $peso,
            'precio' => $precio,
            'fecha' => $this->fecha,
            'proveedor' => $this->proveedor,
            'frigorifico' => $this->frigorifico,
        ], [
            'formato' => ['required', Rule::in($this->formatos())],
            'cantidad' => ['required', 'integer', 'min:1', 'max:999'],
            'peso' => ['required', 'numeric', 'gt:0', 'max:99999'],
            'precio' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'proveedor' => ['nullable', 'string', 'max:150'],
            'frigorifico' => ['nullable', 'string', 'max:150'],
        ], [
            'peso.required' => 'Ingresá los kg.',
            'peso.gt' => 'Ingresá los kg.',
            'precio.required' => 'Ingresá el precio por kg.',
            'precio.gt' => 'Ingresá el precio por kg.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
        ])->validate();

        $animal = Animal::query()->create([
            'animal_type_id' => $tipo->id,
            'user_id' => $this->usuario()->id,
            'formato' => $this->formato,
            'cut_catalog_id' => $pieza?->id,
            'cantidad' => (int) $this->cantidad,
            'peso_total' => $peso,
            'precio_kg' => $precio,
            'fecha' => $this->fecha,
            'proveedor' => trim($this->proveedor) ?: null,
            'frigorifico' => trim($this->frigorifico) ?: null,
            'estado' => Animal::DISPONIBLE,
        ]);

        session()->flash('success', 'Ingreso guardado: '.$animal->etiquetaFormato().' de '.Numero::texto($peso).' kg, disponible para producir.');
        $this->reset(['peso', 'precio', 'proveedor', 'frigorifico', 'cantidad', 'nuevo', 'piezaId', 'formatoElegido']);
        $this->resetPage();
    }

    /** Solo se borra un ingreso disponible que nunca entró a un desposte (para corregir errores de carga). */
    public function eliminar(int $id): void
    {
        $animal = Animal::query()->findOrFail($id);
        $usado = DB::table('desposte_animales')->where('animal_id', $animal->id)->exists();
        abort_if($animal->estado !== Animal::DISPONIBLE || $usado, 403, 'Ese ingreso ya se usó en un desposte.');

        $animal->delete();
        session()->flash('success', 'Ingreso eliminado.');
    }

    public function render()
    {
        $tipos = AnimalType::query()->whereIn('id', $this->tiposHabilitados())->orderBy('id')->get();
        $buscar = trim($this->search);

        $consulta = Animal::query()
            ->with(['animalType', 'cutCatalog'])
            ->whereIn('animal_type_id', $tipos->pluck('id'))
            ->when((int) $this->filtroTipo, fn ($q) => $q->where('animal_type_id', (int) $this->filtroTipo))
            ->when($this->filtroEstado !== 'todos', fn ($q) => $q->where('estado', $this->filtroEstado))
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w->where('proveedor', 'like', "%$buscar%")->orWhere('frigorifico', 'like', "%$buscar%")));

        $disponibles = Animal::query()->disponibles()->whereIn('animal_type_id', $tipos->pluck('id'))
            ->selectRaw('animal_type_id, COUNT(*) as unidades, SUM(peso_total) as kg, SUM(peso_total * precio_kg) as costo')
            ->groupBy('animal_type_id')->get()->keyBy('animal_type_id');

        $ingresos = (clone $consulta)->latest('fecha')->latest('id')->paginate(20);

        return view('livewire.ingresos.index', [
            'tipos' => $tipos,
            'ingresos' => $ingresos,
            'totales' => (clone $consulta)->selectRaw('COUNT(*) as unidades, COALESCE(SUM(peso_total), 0) as kg, COALESCE(SUM(peso_total * precio_kg), 0) as costo')->first(),
            'disponibles' => $disponibles,
            'formatos' => $this->formatos(),
            'piezas' => $this->piezas(),
            'costoNuevo' => (Numero::leer($this->peso) ?? 0) * (Numero::leer($this->precio) ?? 0),
            'usados' => DB::table('desposte_animales')->whereIn('animal_id', $ingresos->pluck('id'))->pluck('animal_id')->flip(),
        ]);
    }
}
