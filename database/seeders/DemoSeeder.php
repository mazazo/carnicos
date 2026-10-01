<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\AnimalType;
use App\Models\Carniceria;
use App\Models\CutCatalog;
use App\Models\Plan;
use App\Models\PrecioCorte;
use App\Models\User;
use App\Services\Suscripciones;
use Illuminate\Database\Seeder;

/**
 * Carnicería demo para probar la web y la app: dueño con Plan Completo por un
 * año, medias disponibles y precios de venta. Se puede correr varias veces:
 * no duplica y renueva el plan si venció.
 *
 *   php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public const EMAIL = 'demo@carniox.com';

    public const PASSWORD = 'Demo-1234';

    public function run(Suscripciones $suscripciones): void
    {
        $carniceria = Carniceria::query()->firstOrCreate(
            ['email' => self::EMAIL],
            ['nombre' => 'Carnicería Demo', 'telefono' => '1100000000'],
        );
        $carniceria->update(['estado' => Carniceria::ESTADO_ACTIVA]);

        $dueno = User::query()->firstOrNew(['email' => self::EMAIL]);
        $dueno->fill([
            'carniceria_id' => $carniceria->id,
            'rol' => User::ROL_DUENO,
            'name' => 'Demo',
            'last_name' => 'Carnico',
            'movil' => '1100000000',
            'password' => self::PASSWORD,
        ]);
        $dueno->email_verified_at ??= now();
        $dueno->save();

        $completo = Plan::query()->where('codigo', 'plan-3')->firstOrFail();
        if ($carniceria->planVigente()?->id !== $completo->id) {
            $suscripciones->activarPlan($carniceria, $completo, meses: 12, origen: 'manual');
        }

        $tipos = AnimalType::query()->pluck('id', 'nombre');

        // Medias disponibles (solo si no tiene ninguna).
        if (! Animal::withoutGlobalScopes()->where('carniceria_id', $carniceria->id)->exists()) {
            $ingresos = [
                ['vacuno', Animal::FORMATO_MEDIA_RES, 1, 112.5, 4200, 'Frigorífico Norte'],
                ['vacuno', Animal::FORMATO_MEDIA_RES, 1, 108.0, 4200, 'Frigorífico Norte'],
                ['vacuno', Animal::FORMATO_RES, 1, 228.0, 4100, 'Frigorífico Sur'],
                ['porcino', Animal::FORMATO_MEDIA_RES, 1, 45.0, 3100, 'Granja La Esperanza'],
                ['aviar', Animal::FORMATO_CAJON, 4, 80.0, 2300, 'Avícola Oeste'],
            ];

            foreach ($ingresos as [$tipo, $formato, $cantidad, $peso, $precio, $proveedor]) {
                if (! isset($tipos[$tipo])) {
                    continue;
                }
                Animal::withoutGlobalScopes()->create([
                    'carniceria_id' => $carniceria->id,
                    'user_id' => $dueno->id,
                    'animal_type_id' => $tipos[$tipo],
                    'formato' => $formato,
                    'cantidad' => $cantidad,
                    'estado' => Animal::DISPONIBLE,
                    'peso_total' => $peso,
                    'precio_kg' => $precio,
                    'fecha' => today()->toDateString(),
                    'proveedor' => $proveedor,
                ]);
            }
        }

        // Precios de venta de los cortes más comunes (los que ya tenga, no se tocan).
        $precios = [
            'asado' => 9800, 'vacío' => 11500, 'matambre' => 10500, 'bife ancho' => 11000, 'bife angosto' => 11800,
            'bife de chorizo' => 12500, 'lomo' => 16500, 'cuadril' => 12800, 'colita de cuadril' => 13500,
            'nalga' => 12900, 'peceto' => 13900, 'bola de lomo' => 11200, 'paleta' => 9500, 'roast beef' => 8900,
            'falda' => 6500, 'osobuco' => 6200, 'tapa de asado' => 9200, 'entraña' => 14500,
            'bondiola' => 8200, 'pechito de cerdo' => 7500, 'costillas de cerdo' => 7600, 'carré' => 7900,
            'matambre de cerdo' => 9800, 'pernil' => 6900, 'paleta de cerdo' => 6400,
            'pechuga sin hueso' => 7800, 'pata muslo' => 4200, 'suprema' => 8200, 'ala' => 3200,
        ];

        $cortes = CutCatalog::withoutGlobalScopes()->whereNull('carniceria_id')->whereIn('nombre_canonico', array_keys($precios))->get();
        foreach ($cortes as $corte) {
            PrecioCorte::withoutGlobalScopes()->firstOrCreate(
                ['carniceria_id' => $carniceria->id, 'cut_catalog_id' => $corte->id],
                ['precio_kg' => $precios[$corte->nombre_canonico], 'user_id' => $dueno->id],
            );
        }

        $this->command?->info('Demo: '.self::EMAIL.' / '.self::PASSWORD.' (Plan Completo hasta '.$carniceria->suscripcionVigente()->ends_at->format('d/m/Y').')');
    }
}
