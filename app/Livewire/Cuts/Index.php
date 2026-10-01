<?php

namespace App\Livewire\Cuts;

use App\Models\AnimalType;
use App\Models\CutCatalog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Catálogo de cortes de la carnicería, por tipo de animal: habilitar o no los
 * cortes generales, y agregar, renombrar o borrar cortes propios. Solo el
 * dueño edita; el empleado lo ve.
 */
class Index extends Component
{
    #[Url(as: 'tipo')]
    public ?int $tipoId = null;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'ver')]
    public string $filtro = 'todos';

    public bool $agregando = false;

    public string $nuevoNombre = '';

    public string $nuevoNivel = CutCatalog::NIVEL_MUSCULO;

    public ?int $editandoId = null;

    public string $editandoNombre = '';

    public function mount(): void
    {
        $tipos = $this->tiposHabilitados();
        if ($this->tipoId === null || ! in_array($this->tipoId, $tipos, true)) {
            $this->tipoId = $tipos[0] ?? null;
        }
    }

    /** @return array<int, int> */
    private function tiposHabilitados(): array
    {
        $carniceria = $this->usuario()->carniceria;

        return $carniceria ? array_map('intval', $carniceria->tiposAnimalHabilitadosIds()) : [];
    }

    private function usuario(): User
    {
        /** @var User */
        return Auth::user();
    }

    private function carniceriaId(): int
    {
        return (int) $this->usuario()->carniceria_id;
    }

    private function puedeEditar(): bool
    {
        return $this->usuario()->carniceria_id !== null && ($this->usuario()->esDueno() || $this->usuario()->isAdmin());
    }

    private function exigirEdicion(): void
    {
        abort_unless($this->puedeEditar(), 403);
    }

    private function corte(int $id): CutCatalog
    {
        $corte = CutCatalog::catalogoPara($this->carniceriaId(), (int) $this->tipoId)->first(fn (CutCatalog $c) => $c->id === $id);
        abort_if($corte === null, 404);

        return $corte;
    }

    public function elegirTipo(int $tipoId): void
    {
        if (in_array($tipoId, $this->tiposHabilitados(), true)) {
            $this->tipoId = $tipoId;
            $this->reset(['search', 'agregando', 'nuevoNombre', 'editandoId', 'editandoNombre']);
        }
    }

    public function alternar(int $id): void
    {
        $this->exigirEdicion();
        $corte = $this->corte($id);
        $corte->habilitarPara($this->carniceriaId(), ! $corte->habilitado);
    }

    /** Habilita o deshabilita todos los cortes que se ven con la búsqueda y el filtro actuales. */
    public function habilitarTodos(bool $habilitar): void
    {
        $this->exigirEdicion();
        foreach ($this->filas() as $corte) {
            if ($corte->habilitado !== $habilitar) {
                $corte->habilitarPara($this->carniceriaId(), $habilitar);
            }
        }
    }

    public function agregar(): void
    {
        $this->exigirEdicion();
        $this->validate(['nuevoNombre' => ['required', 'string', 'max:120']], [], ['nuevoNombre' => 'nombre']);

        try {
            $corte = CutCatalog::agregarPropio($this->carniceriaId(), (int) $this->tipoId, $this->nuevoNombre, $this->usuario()->id, 1, $this->nuevoNivel);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['nuevoNombre' => $e->errors()['nombre'] ?? $e->getMessage()]);
        }

        session()->flash('success', $corte->wasRecentlyCreated
            ? "Agregaste \"{$corte->nombre_canonico}\"."
            : "\"{$corte->nombre_canonico}\" ya existía: quedó habilitado.");
        $this->reset(['nuevoNombre', 'nuevoNivel', 'agregando']);
    }

    public function editar(int $id): void
    {
        $this->exigirEdicion();
        $corte = $this->corte($id);
        abort_unless($corte->esPropio(), 403);
        $this->editandoId = $corte->id;
        $this->editandoNombre = $corte->nombre_canonico;
        $this->resetValidation();
    }

    public function guardarNombre(): void
    {
        $this->exigirEdicion();
        $corte = $this->corte((int) $this->editandoId);
        abort_unless($corte->esPropio(), 403);

        $nombre = trim(preg_replace('/\s+/', ' ', $this->editandoNombre));
        $this->editandoNombre = $nombre;
        $this->validate(['editandoNombre' => ['required', 'string', 'max:120']], [], ['editandoNombre' => 'nombre']);

        $repetido = CutCatalog::catalogoPara($this->carniceriaId(), (int) $this->tipoId)
            ->contains(fn (CutCatalog $c) => $c->id !== $corte->id && mb_strtolower($c->nombre_canonico) === mb_strtolower($nombre));
        if ($repetido) {
            throw ValidationException::withMessages(['editandoNombre' => 'Ya hay un corte con ese nombre.']);
        }

        $corte->update(['nombre_canonico' => $nombre]);
        $this->reset(['editandoId', 'editandoNombre']);
    }

    public function cancelarEdicion(): void
    {
        $this->reset(['editandoId', 'editandoNombre']);
        $this->resetValidation();
    }

    /** Borra un corte propio; si ya se usó en despostes solo se deshabilita. */
    public function eliminar(int $id): void
    {
        $this->exigirEdicion();
        $corte = $this->corte($id);
        abort_unless($corte->esPropio(), 403);

        $usado = DB::table('desposte_cortes')->where('cut_catalog_id', $corte->id)->exists()
            || DB::table('desposte_pesadas')->where('cut_catalog_id', $corte->id)->exists();

        if ($usado) {
            $corte->update(['activo' => false]);
            session()->flash('success', "\"{$corte->nombre_canonico}\" ya se usó en despostes: quedó deshabilitado.");

            return;
        }

        $corte->delete();
        session()->flash('success', "Borraste \"{$corte->nombre_canonico}\".");
    }

    /** @return \Illuminate\Support\Collection<int, CutCatalog> */
    private function filas()
    {
        if ($this->tipoId === null) {
            return collect();
        }

        $buscar = mb_strtolower(trim($this->search));

        return CutCatalog::catalogoPara($this->carniceriaId(), $this->tipoId)
            ->filter(fn (CutCatalog $c) => $buscar === '' || str_contains(mb_strtolower($c->nombre_canonico), $buscar))
            ->filter(fn (CutCatalog $c) => match ($this->filtro) {
                'habilitados' => $c->habilitado,
                'deshabilitados' => ! $c->habilitado,
                'propios' => $c->esPropio(),
                'piezas' => $c->esPrimario(),
                default => true,
            })
            ->values();
    }

    public function render()
    {
        $tipos = AnimalType::query()->whereIn('id', $this->tiposHabilitados())->orderBy('id')->get();
        $carniceriaId = $this->carniceriaId();

        $conteos = $tipos->mapWithKeys(function (AnimalType $tipo) use ($carniceriaId) {
            $catalogo = $carniceriaId ? CutCatalog::catalogoPara($carniceriaId, $tipo->id) : collect();

            return [$tipo->id => ['habilitados' => $catalogo->filter(fn (CutCatalog $c) => $c->habilitado)->count(), 'total' => $catalogo->count()]];
        });

        return view('livewire.cuts.index', [
            'tipos' => $tipos,
            'conteos' => $conteos,
            'cortes' => $this->filas()->each(fn (CutCatalog $c) => $c->esPrimario() ? $c->load('partes') : null),
            'puedeEditar' => $this->puedeEditar(),
        ]);
    }
}
