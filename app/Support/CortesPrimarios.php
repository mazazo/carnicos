<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Cortes primarios (piezas grandes) del catálogo general por tipo de animal,
 * con los músculos que contiene cada uno (nomenclatura de carnicería
 * argentina). Se puede correr más de una vez: no duplica piezas ni relaciones.
 */
class CortesPrimarios
{
    /** Tipo de animal → pieza → músculos que contiene (se buscan por nombre, sin importar mayúsculas). */
    public const PIEZAS = [
        'vacuno' => [
            'Cuarto delantero' => ['paleta', 'punta de paleta', 'marucha', 'palomita', 'roast beef', 'cogote', 'pecho', 'bife ancho', 'osobuco'],
            'Cuarto trasero' => ['nalga', 'tapa de nalga', 'tapa nalga', 'bola de lomo', 'bola lomo', 'cuadrada', 'peceto', 'cuadril', 'colita de cuadril', 'colita cuadril', 'tortuguita', 'tortiguita', 'bife angosto', 'bife de chorizo', 'lomo', 'osobuco'],
            'Cuarto pistola' => ['nalga', 'tapa de nalga', 'tapa nalga', 'bola de lomo', 'bola lomo', 'cuadrada', 'peceto', 'cuadril', 'colita de cuadril', 'colita cuadril', 'tortuguita', 'tortiguita', 'bife angosto', 'bife de chorizo', 'lomo', 'osobuco'],
            'Mocho' => ['nalga', 'tapa de nalga', 'tapa nalga', 'bola de lomo', 'bola lomo', 'cuadrada', 'peceto', 'cuadril', 'colita de cuadril', 'colita cuadril', 'tortuguita', 'tortiguita', 'osobuco'],
            'Tren de bifes' => ['bife angosto', 'bife de chorizo', 'lomo'],
            'Rump & Loin (bife y cuadril)' => ['cuadril', 'colita de cuadril', 'colita cuadril', 'lomo', 'bife angosto', 'bife de chorizo'],
            'Paleta entera' => ['paleta', 'punta de paleta', 'marucha', 'palomita'],
            'Mocho delantero (aguja con cogote)' => ['roast beef', 'cogote', 'pecho'],
            'Parrillero completo' => ['asado', 'tapa de asado', 'tapa asado', 'vacío', 'matambre', 'falda'],
        ],
        'porcino' => [
            'Pierna de cerdo' => ['pernil', 'osobuco de cerdo', 'patas de cerdo'],
            'Paleta entera de cerdo' => ['paleta de cerdo', 'bondiola', 'osobuco de cerdo'],
            'Carré entero' => ['carré', 'lomo de cerdo'],
            'Costillar de cerdo' => ['costillas de cerdo', 'panceta', 'matambre de cerdo'],
        ],
        'aviar' => [
            'Cuarto trasero de pollo' => ['pata', 'muslo', 'pata muslo'],
            'Cuarto delantero de pollo' => ['pechuga entera', 'pechuga sin hueso', 'suprema', 'ala', 'media ala'],
        ],
    ];

    public static function instalar(): void
    {
        foreach (self::PIEZAS as $tipo => $piezas) {
            $tipoId = DB::table('animal_types')->where('nombre', $tipo)->value('id');
            if ($tipoId === null) {
                continue;
            }

            $musculos = DB::table('cut_catalogs')->whereNull('carniceria_id')->where('animal_type_id', $tipoId)
                ->where('nivel', 'musculo')->pluck('id', 'nombre_canonico')
                ->mapWithKeys(fn ($id, $nombre) => [mb_strtolower($nombre) => $id]);

            foreach ($piezas as $pieza => $contiene) {
                $id = DB::table('cut_catalogs')->whereNull('carniceria_id')->where('animal_type_id', $tipoId)
                    ->where('nombre_canonico', $pieza)->value('id');

                if ($id === null) {
                    $id = DB::table('cut_catalogs')->insertGetId([
                        'carniceria_id' => null,
                        'user_id' => null,
                        'animal_type_id' => $tipoId,
                        'nombre_canonico' => $pieza,
                        'nivel' => 'primario',
                        'cantidad_esperada' => 1,
                        'activo' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                foreach ($contiene as $nombre) {
                    if (isset($musculos[$nombre])) {
                        DB::table('cut_catalog_partes')->insertOrIgnore([
                            'primario_id' => $id,
                            'musculo_id' => $musculos[$nombre],
                        ]);
                    }
                }
            }
        }
    }
}
