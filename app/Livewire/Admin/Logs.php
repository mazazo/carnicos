<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Support\Logs\LogEntry;
use App\Support\Logs\LogReader;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Visor de logs de la aplicación (storage/logs) para ver los errores.
 * Solo para el administrador de la plataforma.
 */
#[Layout('layouts.app')]
class Logs extends Component
{
    use WithPagination;

    private const POR_PAGINA = 50;

    #[Url]
    public string $archivo = '';

    #[Url]
    public string $nivel = '';

    #[Url(as: 'q')]
    public string $busqueda = '';

    /** Se revisa en cada request (carga y acciones), no solo al entrar. */
    public function boot(): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);
    }

    public function mount(LogReader $reader): void
    {
        if (! $reader->existe($this->archivo)) {
            $this->archivo = $reader->archivos()->first()['nombre'] ?? '';
        }
    }

    public function updated(string $propiedad): void
    {
        if (in_array($propiedad, ['archivo', 'nivel', 'busqueda'], true)) {
            $this->resetPage();
        }
    }

    public function descargar(LogReader $reader)
    {
        return response()->download($reader->ruta($this->archivo));
    }

    public function vaciar(LogReader $reader): void
    {
        $reader->vaciar($this->archivo);
        $this->resetPage();
        session()->flash('success', "Se vació {$this->archivo}.");
    }

    public function render(LogReader $reader)
    {
        $archivos = $reader->archivos();
        $entradas = $reader->existe($this->archivo) ? $reader->entradas($this->archivo) : collect();
        $conteo = $entradas->countBy(fn (LogEntry $e) => $e->nivel);

        $filtradas = $entradas
            ->filter(fn (LogEntry $e) => ($this->nivel === '' || $e->nivel === $this->nivel) && $e->coincide(trim($this->busqueda)))
            ->values();

        $pagina = $this->getPage();

        return view('livewire.admin.logs', [
            'archivos' => $archivos,
            'conteo' => $conteo,
            'total' => $entradas->count(),
            'entradas' => new LengthAwarePaginator(
                $filtradas->forPage($pagina, self::POR_PAGINA)->values(),
                $filtradas->count(),
                self::POR_PAGINA,
                $pagina,
            ),
        ]);
    }
}
