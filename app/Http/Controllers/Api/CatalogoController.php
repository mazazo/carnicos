<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\AnimalType;
use App\Models\CutCatalog;
use App\Models\PrecioCorte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Tipos de animal de la carnicería y cortes del catálogo con su precio actual. */
class CatalogoController extends Controller
{
    public const ETIQUETAS_TIPO = ['vacuno' => 'Vacuno', 'porcino' => 'Cerdo', 'aviar' => 'Avícola'];

    public function tipos(Request $request): JsonResponse
    {
        $habilitados = $request->user()->carniceria->tiposAnimalHabilitadosIds();
        $carniceriaId = (int) $request->user()->carniceria_id;
        $disponibles = Animal::query()->disponibles()->sinPiezas()->selectRaw('animal_type_id, count(*) as total')->groupBy('animal_type_id')->pluck('total', 'animal_type_id');
        $piezasDisponibles = Animal::query()->disponibles()->piezas()->selectRaw('animal_type_id, count(*) as total')->groupBy('animal_type_id')->pluck('total', 'animal_type_id');

        $tipos = AnimalType::query()->whereIn('id', $habilitados)->orderBy('id')->get()
            ->map(fn (AnimalType $tipo) => [
                'id' => $tipo->id,
                'nombre' => $tipo->nombre,
                'etiqueta' => self::ETIQUETAS_TIPO[$tipo->nombre] ?? ucfirst($tipo->nombre),
                'formatos' => collect(Animal::formatosPara($tipo))->map(fn (string $f) => [
                    'valor' => $f,
                    'etiqueta' => (new Animal(['formato' => $f]))->etiquetaFormato(),
                ])->all(),
                'disponibles' => (int) ($disponibles[$tipo->id] ?? 0),
                // Piezas grandes habilitadas (cuartos, Mocho…) con sus músculos, y cuántas hay para cuartear.
                'piezas' => CutCatalog::catalogoPara($carniceriaId, $tipo->id)
                    ->filter(fn (CutCatalog $c) => $c->habilitado && $c->esPrimario())
                    ->values()
                    ->map(fn (CutCatalog $c) => [
                        'id' => $c->id,
                        'nombre' => $c->nombre_canonico,
                        'musculos' => $c->partes()->pluck('cut_catalogs.id')->all(),
                    ])->all(),
                'piezas_disponibles' => (int) ($piezasDisponibles[$tipo->id] ?? 0),
            ]);

        return response()->json(['data' => $tipos]);
    }

    public function cortes(Request $request): JsonResponse
    {
        $request->validate([
            'animal_type_id' => ['required', 'integer', Rule::in($request->user()->carniceria->tiposAnimalHabilitadosIds())],
            'nivel' => ['nullable', Rule::in([CutCatalog::NIVEL_MUSCULO, CutCatalog::NIVEL_PRIMARIO])],
        ]);

        $precios = PrecioCorte::query()->pluck('precio_kg', 'cut_catalog_id');

        $cortes = CutCatalog::query()
            ->where('animal_type_id', $request->integer('animal_type_id'))
            ->habilitadosPara((int) $request->user()->carniceria_id)
            ->where('nivel', $request->input('nivel', CutCatalog::NIVEL_MUSCULO)) // músculos por defecto (versiones viejas de la app)
            ->orderBy('nombre_canonico')
            ->get()
            ->map(fn (CutCatalog $corte) => [
                'id' => $corte->id,
                'nombre' => $corte->nombre_canonico,
                'cantidad_esperada' => $corte->cantidad_esperada,
                'precio_kg' => isset($precios[$corte->id]) ? (float) $precios[$corte->id] : null,
            ]);

        return response()->json(['data' => $cortes]);
    }
}
