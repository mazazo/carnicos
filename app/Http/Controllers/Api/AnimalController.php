<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\AnimalType;
use App\Models\CutCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Ingreso de medias, reses o cajones, y el listado de las disponibles (sin despostar). */
class AnimalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'animal_type_id' => ['nullable', 'integer'],
            'estado' => ['nullable', Rule::in([Animal::DISPONIBLE, Animal::EN_DESPOSTE, Animal::DESPOSTADA, 'todas'])],
            'piezas' => ['nullable', 'boolean'],
        ]);

        $estado = $request->input('estado', Animal::DISPONIBLE);

        $animales = Animal::query()
            ->with('cutCatalog')
            // Sin "piezas": medias, reses y cajones (como siempre). Con piezas=1: cuartos y piezas grandes para el cuarteo.
            ->when($request->boolean('piezas'), fn ($q) => $q->piezas(), fn ($q) => $q->sinPiezas())
            ->when($request->filled('animal_type_id'), fn ($q) => $q->where('animal_type_id', $request->integer('animal_type_id')))
            ->when($estado !== 'todas', fn ($q) => $q->where('estado', $estado))
            ->latest('fecha')
            ->latest('id')
            ->limit(200)
            ->get();

        return response()->json(['data' => $animales->map(fn (Animal $a) => self::recurso($a))]);
    }

    public function store(Request $request): JsonResponse
    {
        $habilitados = $request->user()->carniceria->tiposAnimalHabilitadosIds();
        $request->validate(['animal_type_id' => ['required', 'integer', Rule::in($habilitados)]]);
        $tipo = AnimalType::query()->findOrFail($request->integer('animal_type_id'));

        $datos = $request->validate([
            'formato' => ['required', Rule::in([...Animal::formatosPara($tipo), Animal::FORMATO_PIEZA])],
            'cut_catalog_id' => ['nullable', 'required_if:formato,'.Animal::FORMATO_PIEZA, 'integer'],
            'cantidad' => ['nullable', 'integer', 'min:1', 'max:999'],
            'peso_total' => ['required', 'numeric', 'gt:0', 'max:99999'],
            'precio_kg' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'fecha' => ['nullable', 'date'],
            'proveedor' => ['nullable', 'string', 'max:150'],
            'frigorifico' => ['nullable', 'string', 'max:150'],
        ]);

        // Pieza grande: tiene que ser una pieza habilitada de ese animal.
        if ($datos['formato'] === Animal::FORMATO_PIEZA) {
            $pieza = CutCatalog::query()->whereKey($datos['cut_catalog_id'])->where('animal_type_id', $tipo->id)
                ->where('nivel', CutCatalog::NIVEL_PRIMARIO)->habilitadosPara((int) $request->user()->carniceria_id)->exists();
            if (! $pieza) {
                throw ValidationException::withMessages(['cut_catalog_id' => 'Elegí una pieza grande de este animal.']);
            }
        } else {
            $datos['cut_catalog_id'] = null;
        }

        $animal = Animal::query()->create(array_merge($datos, [
            'animal_type_id' => $tipo->id,
            'user_id' => $request->user()->id,
            'cantidad' => $datos['cantidad'] ?? 1,
            'fecha' => $datos['fecha'] ?? today()->toDateString(),
            'estado' => Animal::DISPONIBLE,
        ]));

        return response()->json(['data' => self::recurso($animal->refresh())], 201);
    }

    /** @return array<string, mixed> */
    public static function recurso(Animal $animal): array
    {
        return [
            'id' => $animal->id,
            'animal_type_id' => $animal->animal_type_id,
            'formato' => $animal->formato,
            'formato_etiqueta' => $animal->etiquetaFormato(),
            'cut_catalog_id' => $animal->cut_catalog_id,
            'cantidad' => $animal->cantidad,
            'peso_total' => (float) $animal->peso_total,
            'precio_kg' => (float) $animal->precio_kg,
            'costo_total' => $animal->costoTotal(),
            'fecha' => $animal->fecha?->toDateString(),
            'proveedor' => $animal->proveedor,
            'frigorifico' => $animal->frigorifico,
            'estado' => $animal->estado,
        ];
    }
}
