<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CutCatalog;
use App\Models\PrecioCorte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** "Promediar valores": el dueño guarda los precios por kg nuevos de los cortes. */
class PrecioController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->esDueno(), 403, 'Solo el dueño puede cambiar los precios.');

        $datos = $request->validate([
            'precios' => ['required', 'array', 'min:1', 'max:200'],
            'precios.*.cut_catalog_id' => ['required', 'integer', 'distinct'],
            'precios.*.precio_kg' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $ids = array_column($datos['precios'], 'cut_catalog_id');
        $visibles = CutCatalog::query()->whereIn('id', $ids)->whereIn('animal_type_id', $user->carniceria->tiposAnimalHabilitadosIds())->count();

        if ($visibles !== count($ids)) {
            throw ValidationException::withMessages(['precios' => 'Hay cortes que no existen en tu catálogo.']);
        }

        foreach ($datos['precios'] as $fila) {
            PrecioCorte::query()->updateOrCreate(
                ['carniceria_id' => $user->carniceria_id, 'cut_catalog_id' => $fila['cut_catalog_id']],
                ['precio_kg' => $fila['precio_kg'], 'user_id' => $user->id],
            );
        }

        return response()->json([
            'data' => PrecioCorte::query()->whereIn('cut_catalog_id', $ids)->get()
                ->map(fn (PrecioCorte $p) => ['cut_catalog_id' => $p->cut_catalog_id, 'precio_kg' => (float) $p->precio_kg]),
        ]);
    }
}
