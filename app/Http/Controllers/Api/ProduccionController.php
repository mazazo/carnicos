<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Desposte;
use App\Models\DesposteCorte;
use App\Models\DespostePesada;
use App\Services\Despostes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Producciones (despostes) de una o varias medias disponibles. Los cortes se
 * guardan con el precio por kg vigente de la carnicería; las medias quedan
 * despostadas y salen del listado de disponibles.
 *
 * Desde la app el desposte se arma pendiente: se inicia con las medias (que
 * quedan en_desposte), se agregan pesadas de a una y al terminarlo se suman
 * por corte. Mientras está pendiente cualquier usuario de la carnicería lo
 * puede seguir o cancelar. La lógica está en App\Services\Despostes, que
 * comparte con la pantalla web de producción.
 */
class ProduccionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'animal_type_id' => ['nullable', 'integer'],
            'limite' => ['nullable', 'integer', 'min:1', 'max:100'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'estado' => ['nullable', Rule::in([Desposte::PENDIENTE, Desposte::TERMINADO])],
        ]);

        $producciones = Desposte::query()
            ->with(['animales', 'cortes', 'animalType', 'pesadas'])
            ->where('estado', $request->input('estado', Desposte::TERMINADO))
            ->when($request->filled('animal_type_id'), fn ($q) => $q->where('animal_type_id', $request->integer('animal_type_id')))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha_desposte', '>=', $request->input('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha_desposte', '<=', $request->input('hasta')))
            ->whereNotNull('animal_type_id')
            ->latest('fecha_desposte')
            ->latest('id')
            ->limit($request->integer('limite', 30))
            ->get();

        return response()->json(['data' => $producciones->map(fn (Desposte $d) => self::resumen($d))]);
    }

    public function show(Desposte $produccion): JsonResponse
    {
        return self::respuesta($produccion);
    }

    /** Arranca un desposte pendiente con las medias elegidas. */
    public function iniciar(Request $request, Despostes $despostes): JsonResponse
    {
        $user = $request->user();
        $datos = $request->validate([
            'animal_type_id' => ['required', 'integer', Rule::in($user->carniceria->tiposAnimalHabilitadosIds())],
            'animal_ids' => ['required', 'array', 'min:1', 'max:50'],
            'animal_ids.*' => ['integer', 'distinct'],
            'modo' => ['nullable', Rule::in(array_keys(Desposte::MODOS))],
        ], ['animal_ids.required' => 'Elegí al menos una media.']);

        $desposte = $despostes->iniciar($user, (int) $datos['animal_type_id'], array_map('intval', $datos['animal_ids']), $datos['modo'] ?? Desposte::MODO_MUSCULO);

        return self::respuesta($desposte, 201);
    }

    public function agregarPesada(Request $request, Desposte $produccion, Despostes $despostes): JsonResponse
    {
        $datos = $request->validate([
            'cut_catalog_id' => ['required', 'integer'],
            'peso' => ['required', 'numeric', 'gt:0', 'max:99999'],
        ], ['peso.gt' => 'El peso tiene que ser mayor a 0.']);

        $despostes->agregarPesada($request->user(), $produccion, (int) $datos['cut_catalog_id'], (float) $datos['peso']);

        return self::respuesta($produccion, 201);
    }

    public function quitarPesada(Desposte $produccion, int $pesada, Despostes $despostes): JsonResponse
    {
        $despostes->quitarPesada($produccion, $pesada);

        return self::respuesta($produccion);
    }

    /** Suma las pesadas por corte, guarda los cortes con el precio vigente y desposta las medias. */
    public function terminar(Desposte $produccion, Despostes $despostes): JsonResponse
    {
        return self::respuesta($despostes->terminar($produccion));
    }

    /** Cancela un desposte pendiente: las medias vuelven a disponibles. */
    public function cancelar(Desposte $produccion, Despostes $despostes): JsonResponse
    {
        $despostes->cancelar($produccion);

        return response()->json(['data' => null]);
    }

    private static function respuesta(Desposte $desposte, int $status = 200): JsonResponse
    {
        $desposte->load(['animales', 'cortes', 'animalType', 'pesadas.cutCatalog']);

        return response()->json(['data' => self::detalle($desposte)], $status);
    }

    public function store(Request $request, Despostes $despostes): JsonResponse
    {
        $user = $request->user();
        $datos = $request->validate([
            'animal_type_id' => ['required', 'integer', Rule::in($user->carniceria->tiposAnimalHabilitadosIds())],
            'animal_ids' => ['required', 'array', 'min:1', 'max:50'],
            'animal_ids.*' => ['integer', 'distinct'],
            'cortes' => ['required', 'array', 'min:1', 'max:200'],
            'cortes.*.cut_catalog_id' => ['required', 'integer', 'distinct'],
            'cortes.*.peso' => ['required', 'numeric', 'gt:0', 'max:99999'],
            'cortes.*.cantidad' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'fecha_desposte' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ], [
            'animal_ids.required' => 'Elegí al menos una media.',
            'cortes.required' => 'Cargá al menos un corte.',
            'cortes.*.peso.gt' => 'El peso de cada corte tiene que ser mayor a 0.',
        ]);

        $desposte = $despostes->registrarTerminado(
            $user,
            (int) $datos['animal_type_id'],
            array_map('intval', $datos['animal_ids']),
            $datos['cortes'],
            $datos['fecha_desposte'] ?? null,
            $datos['observaciones'] ?? null,
        );

        return self::respuesta($desposte, 201);
    }

    /** @return array<string, mixed> */
    public static function resumen(Desposte $d): array
    {
        // Terminado: el cálculo guardado al terminarlo. Pendiente: lo cargado hasta ahora con precios actuales.
        if ($d->tieneCalculo()) {
            return [
                'id' => $d->id,
                'estado' => $d->estado,
                'modo' => $d->modo,
                'piezas' => $d->modo === 'cuarteo' ? $d->animales->map(fn ($a) => $a->etiquetaFormato())->values()->all() : [],
                'fecha' => $d->fecha_desposte?->toDateString(),
                'animal_type_id' => $d->animal_type_id,
                'tipo' => CatalogoController::ETIQUETAS_TIPO[$d->animalType?->nombre] ?? $d->animalType?->nombre,
                'medias' => $d->animales->count(),
                'peso_medias' => round($d->kg_medias, 3),
                'peso_cortes' => round($d->kg_cortes, 3),
                'rendimiento' => $d->rinde_pct !== null ? round($d->rinde_pct, 1) : null,
                'costo_total' => round($d->costo, 2),
                'valor_total' => round($d->venta, 2),
                'diferencia' => round($d->ganancia, 2),
                'despostador' => $d->despostador_nombre,
            ];
        }

        $pesoMedias = $d->peso_medias;
        $pendiente = $d->estaPendiente();
        $pesoCortes = $pendiente ? (float) $d->pesadas->sum('peso') : $d->peso_total_cortes;
        $valor = $pendiente ? app(Despostes::class)->valorPesadas($d) : $d->valor_total;

        return [
            'id' => $d->id,
            'estado' => $d->estado,
            'modo' => $d->modo,
            'piezas' => $d->modo === 'cuarteo' ? $d->animales->map(fn ($a) => $a->etiquetaFormato())->values()->all() : [],
            'fecha' => $d->fecha_desposte?->toDateString(),
            'animal_type_id' => $d->animal_type_id,
            'tipo' => CatalogoController::ETIQUETAS_TIPO[$d->animalType?->nombre] ?? $d->animalType?->nombre,
            'medias' => $d->animales->count(),
            'peso_medias' => round($pesoMedias, 3),
            'peso_cortes' => round($pesoCortes, 3),
            'rendimiento' => $pesoMedias > 0 ? round($pesoCortes / $pesoMedias * 100, 1) : null,
            'costo_total' => $d->costo_total,
            'valor_total' => round($valor, 2),
            'diferencia' => round($valor - $d->costo_total, 2),
            'despostador' => $d->despostador_nombre,
        ];
    }

    /** @return array<string, mixed> */
    public static function detalle(Desposte $d): array
    {
        return self::resumen($d) + [
            'observaciones' => $d->observaciones,
            'pesadas' => $d->pesadas->map(fn (DespostePesada $p) => [
                'id' => $p->id,
                'cut_catalog_id' => $p->cut_catalog_id,
                'nombre' => $p->cutCatalog?->nombre_canonico,
                'peso' => (float) $p->peso,
            ])->values(),
            'medias_detalle' => $d->animales->map(fn (Animal $a) => [
                'id' => $a->id,
                'formato_etiqueta' => $a->etiquetaFormato(),
                'cut_catalog_id' => $a->cut_catalog_id,
                'peso' => (float) $a->pivot->peso,
                'precio_kg' => (float) $a->pivot->precio_kg,
                'costo' => round((float) $a->pivot->peso * (float) $a->pivot->precio_kg, 2),
                'fecha' => $a->fecha?->toDateString(),
                'proveedor' => $a->proveedor,
            ])->values(),
            'cortes' => $d->cortes->map(fn (DesposteCorte $c) => [
                'cut_catalog_id' => $c->cut_catalog_id,
                'nombre' => $c->nombre,
                'cantidad' => $c->cantidad,
                'peso' => (float) $c->peso,
                'precio_kg' => $c->precio_kg !== null ? (float) $c->precio_kg : null,
                'total' => round($c->valor_total, 2),
            ])->values(),
        ];
    }
}
