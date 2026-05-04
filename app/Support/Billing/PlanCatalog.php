<?php

namespace App\Support\Billing;

class PlanCatalog
{
    public static function all(): array
    {
        return [
            [
                'id'          => 'starter',
                'nombre'      => 'Starter',
                'precio'      => 0,
                'periodo'     => 'mes',
                'descripcion' => 'Para empezar a gestionar tu produccion.',
                'color'       => 'slate',
                'features'    => [
                    'Hasta 20 animales / mes',
                    'Hasta 50 cortes registrados',
                    '1 usuario',
                    'Soporte por email',
                ],
                'limitado'    => [
                    'Reportes avanzados',
                    'Exportacion a Excel/PDF',
                    'Multi-usuario',
                ],
            ],
            [
                'id'          => 'pro',
                'nombre'      => 'Pro',
                'precio'      => 4900,
                'periodo'     => 'mes',
                'descripcion' => 'Para carnicerias en crecimiento.',
                'color'       => 'emerald',
                'features'    => [
                    'Animales y cortes ilimitados',
                    'Hasta 5 usuarios',
                    'Reportes y estadisticas',
                    'Exportacion a Excel/PDF',
                    'Soporte prioritario',
                ],
                'limitado'    => [
                    'API externa',
                    'Multi-sucursal',
                ],
            ],
            [
                'id'          => 'enterprise',
                'nombre'      => 'Enterprise',
                'precio'      => 9900,
                'periodo'     => 'mes',
                'descripcion' => 'Para operaciones grandes y cadenas.',
                'color'       => 'sky',
                'features'    => [
                    'Todo lo de Pro',
                    'Usuarios ilimitados',
                    'Multi-sucursal',
                    'API externa',
                    'Integraciones a medida',
                    'Soporte 24/7',
                ],
                'limitado'    => [],
            ],
        ];
    }

    public static function find(string $planId): ?array
    {
        foreach (self::all() as $plan) {
            if ($plan['id'] === $planId) {
                return $plan;
            }
        }

        return null;
    }
}