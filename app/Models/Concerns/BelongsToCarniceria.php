<?php

namespace App\Models\Concerns;

use App\Models\Carniceria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Aislamiento por carnicería. Con un usuario logueado, todas las consultas se
 * filtran por su carnicería (un registro de otra "no existe": 404) y lo nuevo
 * toma su carniceria_id. Un usuario sin carnicería no ve nada (falla cerrado).
 * Sin usuario (consola, tareas programadas) no filtra.
 *
 * Los modelos con registros compartidos (p. ej. el catálogo general, con
 * carniceria_id NULL) definen $incluyeCompartidos = true.
 *
 * @mixin Model
 */
trait BelongsToCarniceria
{
    public static function bootBelongsToCarniceria(): void
    {
        static::addGlobalScope('carniceria', function (Builder $query): void {
            if (! Auth::hasUser()) {
                return;
            }

            $carniceriaId = Auth::user()->carniceria_id;
            $columna = $query->getModel()->qualifyColumn('carniceria_id');

            if ($carniceriaId === null) {
                $query->whereRaw('1 = 0');

                return;
            }

            if (property_exists(static::class, 'incluyeCompartidos') && static::$incluyeCompartidos) {
                $query->where(fn (Builder $q) => $q->where($columna, $carniceriaId)->orWhereNull($columna));

                return;
            }

            $query->where($columna, $carniceriaId);
        });

        static::creating(function (Model $model): void {
            // Un carniceria_id puesto explícitamente (aunque sea NULL, p. ej. un
            // corte del catálogo general) se respeta.
            if (Auth::hasUser() && ! array_key_exists('carniceria_id', $model->getAttributes())) {
                $model->carniceria_id = Auth::user()->carniceria_id;
            }
        });
    }

    public function carniceria(): BelongsTo
    {
        return $this->belongsTo(Carniceria::class);
    }
}
