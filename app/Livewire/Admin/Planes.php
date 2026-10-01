<?php

namespace App\Livewire\Admin;

use App\Models\Plan;
use App\Models\PlanPrecio;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Edición de los planes que se muestran en la página de planes: textos,
 * límites (tipos de animal, usuarios), dashboard/app, estilo, orden y los
 * precios por período. Un cambio de precio no toca lo ya pagado.
 */
#[Layout('layouts.app')]
class Planes extends Component
{
    public ?int $editandoId = null;

    public string $codigo = '';

    public string $nombre = '';

    public string $descripcion = '';

    public ?int $max_tipos_animal = null;

    public ?int $max_usuarios = null;

    public bool $incluye_dashboard = false;

    public bool $incluye_app = false;

    public string $caracteristicas = '';

    public string $estilo = Plan::ESTILO_NORMAL;

    public int $orden = 0;

    public bool $activo = true;

    /** @var list<array{id: int|null, meses: int|string, precio: string, activo: bool}> */
    public array $precios = [];

    public function boot(): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);
    }

    public function editar(int $id): void
    {
        $plan = Plan::query()->with('precios')->findOrFail($id);
        $this->resetValidation();
        $this->editandoId = $plan->id;
        $this->fill($plan->only(['codigo', 'nombre', 'max_tipos_animal', 'max_usuarios', 'incluye_dashboard', 'incluye_app', 'estilo', 'orden', 'activo']));
        $this->descripcion = (string) $plan->descripcion;
        $this->caracteristicas = implode("\n", $plan->caracteristicas ?? []);
        $this->precios = $plan->precios->map(fn (PlanPrecio $p) => [
            'id' => $p->id,
            'meses' => $p->meses,
            'precio' => (string) $p->precio,
            'activo' => (bool) $p->activo,
        ])->all();
    }

    public function nuevo(): void
    {
        $this->resetValidation();
        $this->reset('codigo', 'nombre', 'descripcion', 'max_tipos_animal', 'max_usuarios', 'incluye_dashboard', 'incluye_app', 'caracteristicas', 'estilo', 'activo');
        $this->editandoId = 0;
        $this->orden = (int) Plan::query()->max('orden') + 1;
        $this->precios = [['id' => null, 'meses' => 1, 'precio' => '', 'activo' => true]];
    }

    public function cancelar(): void
    {
        $this->editandoId = null;
    }

    public function agregarPrecio(): void
    {
        $this->precios[] = ['id' => null, 'meses' => '', 'precio' => '', 'activo' => true];
    }

    public function quitarPrecio(int $indice): void
    {
        unset($this->precios[$indice]);
        $this->precios = array_values($this->precios);
    }

    public function guardar(): void
    {
        abort_if($this->editandoId === null, 404);
        $this->codigo = strtolower(trim($this->codigo));

        $this->validate([
            'codigo' => ['required', 'alpha_dash', 'max:40', Rule::unique('planes', 'codigo')->ignore($this->editandoId ?: null)],
            'nombre' => ['required', 'string', 'max:80'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'max_tipos_animal' => ['nullable', 'integer', 'min:1', 'max:10'],
            'max_usuarios' => ['nullable', 'integer', 'min:1', 'max:500'],
            'caracteristicas' => ['nullable', 'string', 'max:2000'],
            'estilo' => ['required', Rule::in([Plan::ESTILO_NORMAL, Plan::ESTILO_DESTACADO, Plan::ESTILO_PREMIUM])],
            'orden' => ['required', 'integer', 'min:0', 'max:999'],
            'precios' => ['array', 'min:1'],
            'precios.*.meses' => ['required', 'integer', 'min:1', 'max:36', 'distinct'],
            'precios.*.precio' => ['required', 'numeric', 'min:0'],
        ], [
            'precios.min' => 'Cargá al menos un precio.',
            'precios.*.meses.distinct' => 'Hay dos precios con los mismos meses.',
        ]);

        $datos = [
            'codigo' => $this->codigo,
            'nombre' => trim($this->nombre),
            'descripcion' => trim($this->descripcion) ?: null,
            'max_tipos_animal' => $this->max_tipos_animal ?: null,
            'max_usuarios' => $this->max_usuarios ?: null,
            'incluye_dashboard' => $this->incluye_dashboard,
            'incluye_app' => $this->incluye_app,
            'caracteristicas' => array_values(array_filter(array_map('trim', preg_split('/\R/', $this->caracteristicas)))),
            'estilo' => $this->estilo,
            'orden' => $this->orden,
            'activo' => $this->activo,
        ];

        $plan = $this->editandoId ? Plan::query()->findOrFail($this->editandoId) : new Plan;
        $plan->fill($datos)->save();

        $guardados = [];
        foreach ($this->precios as $fila) {
            $precio = $plan->precios()->updateOrCreate(
                ['meses' => (int) $fila['meses']],
                ['precio' => $fila['precio'], 'moneda' => config('carnicos.moneda'), 'activo' => (bool) $fila['activo']],
            );
            $guardados[] = $precio->id;
        }
        $plan->precios()->whereNotIn('id', $guardados)->delete();

        $this->editandoId = null;
        session()->flash('success', "Plan {$plan->nombre} guardado.");
    }

    public function render()
    {
        return view('livewire.admin.planes', [
            'planes' => Plan::query()->with('precios')->orderBy('orden')->orderBy('id')->get(),
        ]);
    }
}
