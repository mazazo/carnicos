<?php

namespace App\Support\Billing;

use App\Models\Plan;

/**
 * Planes para mostrar (página de planes y checkout). Ya no están escritos en
 * el código: se leen de la tabla planes, editable por el administrador.
 * Se mantiene la forma de array que usan las vistas.
 */
class PlanCatalog
{
    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        return Plan::query()
            ->activos()
            ->with('precioMensual')
            ->get()
            ->map(fn (Plan $plan) => self::toArray($plan))
            ->all();
    }

    public static function find(string $planId): ?array
    {
        $plan = Plan::query()->where('activo', true)->where('codigo', $planId)->with('precioMensual')->first();

        return $plan ? self::toArray($plan) : null;
    }

    /** @return array<string, mixed> */
    private static function toArray(Plan $plan): array
    {
        return [
            'id' => $plan->codigo,
            'nombre' => $plan->nombre,
            'precio' => (int) round((float) ($plan->precioMensual?->precio ?? 0)),
            'periodo' => 'mes',
            'descripcion' => (string) $plan->descripcion,
            'estilo' => $plan->estilo,
            'features' => $plan->incluye(),
            'limitado' => $plan->noIncluye(),
        ];
    }
}
