<?php

namespace App\Livewire\Produccion;

use App\Http\Controllers\Api\ProduccionController;
use App\Models\Animal;
use App\Models\AnimalType;
use App\Models\CutCatalog;
use App\Models\Desposte;
use App\Models\PrecioCorte;
use App\Models\User;
use App\Services\Despostes;
use App\Services\PdfDesposte;
use App\Support\Numero;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Producción web de un tipo de animal, como la de la app: 1) elegir ingresos
 * disponibles (o seguir un desposte pendiente); 2) cargar los kg de cada corte
 * en una tabla; cada cambio queda guardado en el sistema; 3) terminar y ver
 * el resumen para imprimir o mandar por WhatsApp.
 *
 * Con piezas grandes habilitadas (vacuno) se elige el modo: por músculo, por
 * piezas grandes (las piezas quedan disponibles) o cuarteo (pieza → músculos,
 * mostrando primero los músculos de la pieza).
 */
class Index extends Component
{
    public int $tipoId;

    #[Url(as: 'desposte')]
    public ?int $desposteId = null;

    #[Url(as: 'modo')]
    public string $modo = Desposte::MODO_MUSCULO;

    /** En el cuarteo: solo los músculos de la pieza (o todos los músculos). */
    public bool $soloDeLaPieza = true;

    /** @var array<int, int> ids de medias elegidas en el paso 1 */
    public array $seleccion = [];

    /** @var array<int|string, string> corte id → kg (texto del input) */
    public array $pesos = [];

    /** @var array<int|string, string> corte id → precio por kg (texto del input) */
    public array $precios = [];

    public string $search = '';

    public bool $soloCargados = false;

    public ?int $terminadoId = null;

    public function mount(int $tipo): void
    {
        abort_unless(in_array($tipo, $this->tiposHabilitados(), true), 404);
        $this->tipoId = $tipo;
        if (! array_key_exists($this->modo, $this->modosDisponibles())) {
            $this->modo = Desposte::MODO_MUSCULO;
        }

        if ($this->desposteId !== null) {
            $this->cargarDesposte();
        }
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

    private function servicio(): Despostes
    {
        return app(Despostes::class);
    }

    /** Por músculo siempre; piezas grandes y cuarteo si el animal tiene piezas habilitadas o hay piezas para cuartear. */
    private function modosDisponibles(): array
    {
        $piezas = $this->servicio()->tienePiezasGrandes($this->usuario(), $this->tipoId);
        $hayPiezas = $piezas || Animal::query()->piezas()->disponibles()->where('animal_type_id', $this->tipoId)->exists();

        return array_filter(Desposte::MODOS, fn ($texto, $modo) => match ($modo) {
            Desposte::MODO_PRIMARIO => $piezas,
            Desposte::MODO_CUARTEO => $hayPiezas,
            default => true,
        }, ARRAY_FILTER_USE_BOTH);
    }

    public function elegirModo(string $modo): void
    {
        if (array_key_exists($modo, $this->modosDisponibles())) {
            $this->modo = $modo;
            $this->seleccion = [];
        }
    }

    private function desposte(): ?Desposte
    {
        if ($this->desposteId === null) {
            return null;
        }

        return Desposte::query()->with(['animales', 'pesadas'])
            ->where('animal_type_id', $this->tipoId)->where('estado', Desposte::PENDIENTE)
            ->find($this->desposteId);
    }

    /** Llena los inputs con lo que hay en el sistema (pesadas sumadas y precios vigentes). */
    private function cargarDesposte(): void
    {
        $desposte = $this->desposte();
        if ($desposte === null) {
            $this->desposteId = null;
            session()->flash('aviso', 'Ese desposte ya no está pendiente.');

            return;
        }

        $this->modo = $desposte->modo;
        $this->pesos = $desposte->pesadas->groupBy('cut_catalog_id')
            ->map(fn ($pesadas) => self::texto($pesadas->sum(fn ($p) => (float) $p->peso)))
            ->all();
        $this->precios = PrecioCorte::query()->pluck('precio_kg', 'cut_catalog_id')
            ->map(fn ($precio) => self::texto((float) $precio))
            ->all();
    }

    private static function numero(?string $texto): ?float
    {
        return Numero::leer($texto);
    }

    private static function texto(float $valor): string
    {
        return Numero::texto($valor);
    }

    public function alternarMedia(int $id): void
    {
        $this->seleccion = in_array($id, $this->seleccion, true)
            ? array_values(array_diff($this->seleccion, [$id]))
            : [...$this->seleccion, $id];
    }

    public function iniciar(): void
    {
        $desposte = $this->servicio()->iniciar($this->usuario(), $this->tipoId, $this->seleccion, $this->modo);
        $this->desposteId = $desposte->id;
        $this->seleccion = [];
        $this->cargarDesposte();
    }

    public function seguir(int $id): void
    {
        $this->desposteId = $id;
        $this->cargarDesposte();
    }

    public function descartar(int $id): void
    {
        $desposte = Desposte::query()->where('animal_type_id', $this->tipoId)->find($id);
        abort_if($desposte === null, 404);
        $this->servicio()->cancelar($desposte);
        if ($this->desposteId === $id) {
            $this->reset(['desposteId', 'pesos']);
        }
        session()->flash('aviso', 'Desposte descartado: las medias volvieron a disponibles.');
    }

    /** Cada kg que se carga queda guardado en el desposte pendiente. */
    public function updatedPesos(?string $valor, string $corteId): void
    {
        $desposte = $this->desposte();
        abort_if($desposte === null, 404);

        $kg = self::numero($valor);
        if ($kg === null) {
            throw ValidationException::withMessages(["pesos.$corteId" => 'Número inválido.']);
        }

        try {
            $this->servicio()->fijarPesoCorte($this->usuario(), $desposte, (int) $corteId, $kg);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(["pesos.$corteId" => collect($e->errors())->flatten()->first()]);
        }
        $this->pesos[$corteId] = $kg > 0 ? self::texto($kg) : '';
    }

    /** El dueño cambia el precio por kg vigente del corte. */
    public function updatedPrecios(?string $valor, string $corteId): void
    {
        $precio = self::numero($valor);
        if ($precio === null) {
            throw ValidationException::withMessages(["precios.$corteId" => 'Número inválido.']);
        }

        $this->servicio()->fijarPrecio($this->usuario(), (int) $corteId, $precio);
        $this->precios[$corteId] = self::texto($precio);
    }

    public function cancelar(): void
    {
        $desposte = $this->desposte();
        abort_if($desposte === null, 404);
        $this->servicio()->cancelar($desposte);
        $this->reset(['desposteId', 'pesos']);
        session()->flash('aviso', 'Desposte cancelado: las medias volvieron a disponibles.');
    }

    public function terminar(): void
    {
        $desposte = $this->desposte();
        abort_if($desposte === null, 404);

        try {
            $terminado = $this->servicio()->terminar($desposte);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['terminar' => collect($e->errors())->flatten()->first()]);
        }

        $this->terminadoId = $terminado->id;
        $this->reset(['desposteId', 'pesos']);
    }

    /** Termina el desposte y descarga el PDF del resumen en el mismo paso. */
    public function terminarConPdf()
    {
        $this->terminar();

        return $this->descargarPdf();
    }

    public function descargarPdf()
    {
        $desposte = Desposte::query()->where('estado', Desposte::TERMINADO)->findOrFail((int) $this->terminadoId);
        $pdf = app(PdfDesposte::class);

        return response()->streamDownload(fn () => print($pdf->generar($desposte)), $pdf->nombreArchivo($desposte), ['Content-Type' => 'application/pdf']);
    }

    public function nuevaProduccion(): void
    {
        $this->reset(['terminadoId', 'desposteId', 'pesos', 'seleccion', 'search', 'soloCargados', 'soloDeLaPieza']);
    }

    public function irACuarteo(): void
    {
        $this->nuevaProduccion();
        $this->elegirModo(Desposte::MODO_CUARTEO);
    }

    public function render()
    {
        $tipo = AnimalType::query()->findOrFail($this->tipoId);
        $datos = ['tipo' => $tipo, 'esDueno' => $this->usuario()->esDueno(), 'modos' => $this->modosDisponibles()];

        if ($this->terminadoId !== null) {
            $terminado = Desposte::query()->with(['animales', 'cortes', 'animalType', 'pesadas'])->findOrFail($this->terminadoId);

            return view('livewire.produccion.index', $datos + [
                'paso' => 'resumen',
                'resumen' => ProduccionController::detalle($terminado),
                'modoTerminado' => $terminado->modo,
            ]);
        }

        $desposte = $this->desposte();
        if ($desposte === null) {
            return view('livewire.produccion.index', $datos + [
                'paso' => 'medias',
                'disponibles' => Animal::query()->with('cutCatalog')->disponibles()->where('animal_type_id', $this->tipoId)
                    ->when($this->modo === Desposte::MODO_CUARTEO, fn ($q) => $q->piezas(), fn ($q) => $q->sinPiezas())
                    ->latest('fecha')->latest('id')->get(),
                'pendientes' => Desposte::query()->with(['animales.cutCatalog', 'pesadas'])->where('animal_type_id', $this->tipoId)
                    ->where('estado', Desposte::PENDIENTE)->latest('id')->get(),
            ]);
        }

        $buscar = mb_strtolower(trim($this->search));
        $nivel = $desposte->modo === Desposte::MODO_PRIMARIO ? CutCatalog::NIVEL_PRIMARIO : CutCatalog::NIVEL_MUSCULO;

        // Cuarteo: los músculos que contienen las piezas elegidas (los ya cargados siempre se ven).
        $deLaPieza = null;
        if ($desposte->modo === Desposte::MODO_CUARTEO) {
            $deLaPieza = DB::table('cut_catalog_partes')
                ->whereIn('primario_id', $desposte->animales->pluck('cut_catalog_id')->filter())
                ->pluck('musculo_id')->flip();
        }
        // Pieza sin músculos asociados (p. ej. una pieza propia): se muestran todos.
        $filtrarPieza = $deLaPieza !== null && $deLaPieza->isNotEmpty() && $this->soloDeLaPieza;

        $cortes = CutCatalog::catalogoPara((int) $this->usuario()->carniceria_id, $this->tipoId)
            ->filter(fn (CutCatalog $c) => $c->habilitado && $c->nivel === $nivel)
            ->filter(fn (CutCatalog $c) => ! $filtrarPieza || $deLaPieza->has($c->id)
                || (self::numero($this->pesos[$c->id] ?? '') ?? 0) > 0)
            ->filter(fn (CutCatalog $c) => $buscar === '' || str_contains(mb_strtolower($c->nombre_canonico), $buscar))
            ->filter(fn (CutCatalog $c) => ! $this->soloCargados || (self::numero($this->pesos[$c->id] ?? '') ?? 0) > 0)
            ->values();

        $kgMedias = (float) $desposte->peso_medias;
        $costo = (float) $desposte->costo_total;
        $kgCortes = 0.0;
        $venta = 0.0;
        $cargados = 0;
        foreach ($this->pesos as $id => $texto) {
            $kg = self::numero($texto) ?? 0;
            if ($kg > 0) {
                $kgCortes += $kg;
                $venta += $kg * (self::numero($this->precios[$id] ?? '') ?? 0);
                $cargados++;
            }
        }

        return view('livewire.produccion.index', $datos + [
            'paso' => 'cortes',
            'desposte' => $desposte,
            'cortes' => $cortes,
            'kgMedias' => $kgMedias,
            'costo' => $costo,
            'kgCortes' => $kgCortes,
            'venta' => $venta,
            'cargados' => $cargados,
            'esCuarteo' => $deLaPieza !== null,
            'numero' => fn (?string $t) => self::numero($t) ?? 0,
        ]);
    }
}
