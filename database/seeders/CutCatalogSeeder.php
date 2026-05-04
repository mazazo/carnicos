<?php

namespace Database\Seeders;

use App\Models\AnimalType;
use App\Models\CutCatalog;
use App\Models\CutAlias;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CutCatalogSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // -------------------------------------------------------
        // VACUNO
        // -------------------------------------------------------
        $vacuno = AnimalType::where('nombre', 'vacuno')->firstOrFail();

        $cortesVacuno = [
            // [nombre_canonico, cantidad_esperada, [aliases regionales]]
            ['asado',               2,  ['costillar', 'tira de asado', 'asado con hueso']],
            ['bife ancho',          2,  ['bife de costilla', 'entrecot', 'ojo de bife', 'bife angosto ancho']],
            ['bife angosto',        2,  ['bife de lomo', 'New York strip', 'bife de chorizo angosto']],
            ['bife de chorizo',     2,  ['sirloin', 'bife de cuadril', 'bife de chorizo sin tapa']],
            ['lomo',                1,  ['filet mignon', 'solomillo', 'lomito']],
            ['cuadril',             2,  ['picaña', 'rabadilla', 'punta trasera']],
            ['colita de cuadril',   2,  ['picanha', 'colita', 'rump tail']],
            ['nalga',               2,  ['top round', 'posta rosada', 'nalga sin tapa']],
            ['tapa de nalga',       2,  ['peceto tapa', 'top round cap']],
            ['peceto',              2,  ['eye of round', 'posta de res', 'redondo']],
            ['bola de lomo',        2,  ['posta de cuarto', 'top sirloin', 'tortuguita', 'pollo de cuarto']],
            ['cuadrada',            2,  ['carnaza', 'bottom round', 'posta negra']],
            ['paleta',              2,  ['espalda', 'paleta delantera', 'shoulder clod']],
            ['marucha',             2,  ['aguja', 'cogote-aguja', 'chuck roll', 'rollo de aguja']],
            ['cogote',              1,  ['pescuezo', 'neck', 'cogote de res']],
            ['vacío',               2,  ['flank steak', 'falda alta', 'vacio completo']],
            ['falda',               2,  ['pecho bajo', 'brisket', 'pechito de tira']],
            ['pecho',               1,  ['brisket completo', 'pecho de res']],
            ['matambre',            2,  ['flap meat', 'matambre vacuno', 'malaya']],
            ['tapa de asado',       2,  ['tapa de costilla', 'cap of ribs', 'tapa costillar']],
            ['osobuco',             4,  ['osobuco trasero', 'osobuco delantero', 'jarrete', 'ossobuco']],
            ['entraña',             2,  ['entraña fina', 'entraña gruesa', 'skirt steak', 'diafragma']],
            ['roast beef',          1,  ['lomo de aguja', 'chuck tender', 'lomo delantero']],
            ['punta de paleta',     2,  ['top blade', 'palomita', 'punta de espalda']],
            ['tortuguita',          2,  ['carnaza redonda', 'eye of round pequeño']],
            // Menudencias
            ['hígado',              1,  ['liver', 'higadillo']],
            ['riñón',               2,  ['kidney', 'riñones']],
            ['lengua',              1,  ['tongue', 'lengua de res']],
            ['rabo',                1,  ['cola', 'oxtail', 'rabada']],
            ['mondongo',            1,  ['tripas', 'tripa gorda', 'menudo', 'panza']],
            ['molleja',             1,  ['sweetbread', 'mollejita', 'molleja de corazón', 'molleja de garganta']],
            ['corazón',             1,  ['heart']],
            ['sesos',               2,  ['brains', 'seso vacuno']],
            ['patas',               4,  ['manos', 'pata de res', 'pie de vaca']],
        ];

        foreach ($cortesVacuno as [$canonico, $cantidad, $aliases]) {
            $catalog = CutCatalog::updateOrCreate(
                ['user_id' => null, 'animal_type_id' => $vacuno->id, 'nombre_canonico' => $canonico],
                ['cantidad_esperada' => $cantidad, 'activo' => true]
            );
            foreach ($aliases as $alias) {
                CutAlias::updateOrCreate(
                    ['cut_catalog_id' => $catalog->id, 'alias' => $alias]
                );
            }
        }

        // -------------------------------------------------------
        // PORCINO
        // -------------------------------------------------------
        $porcino = AnimalType::where('nombre', 'porcino')->firstOrFail();

        $cortesPorcino = [
            ['bondiola',            1,  ['paleta alta', 'coppa', 'papada alta', 'collar de cerdo']],
            ['carré',               1,  ['lomo con hueso', 'carre de cerdo', 'pork rack']],
            ['lomo de cerdo',       1,  ['solomillo de cerdo', 'tenderloin', 'lomo porcino']],
            ['paleta de cerdo',     2,  ['espalda de cerdo', 'shoulder', 'paleta delantera cerdo']],
            ['pernil',              2,  ['pierna', 'jamón fresco', 'ham', 'pata trasera']],
            ['costillas de cerdo',  2,  ['ribs', 'costeletas', 'costillar de cerdo', 'spare ribs', 'baby back ribs']],
            ['panceta',             1,  ['panza de cerdo', 'tocino', 'bacon', 'barriga de cerdo']],
            ['matambre de cerdo',   1,  ['malaya de cerdo', 'cuerito relleno']],
            ['osobuco de cerdo',    4,  ['jarrete de cerdo', 'osobuco porcino']],
            ['cabeza de cerdo',     1,  ['cabeza de chancho', 'queso de cerdo']],
            ['patas de cerdo',      4,  ['manos de cerdo', 'codillo', 'pie de cerdo']],
            ['hígado de cerdo',     1,  ['liver de cerdo', 'higado porcino']],
            ['riñón de cerdo',      2,  ['kidney cerdo']],
            ['corazón de cerdo',    1,  []],
            ['sesos de cerdo',      2,  ['meollo']],
        ];

        foreach ($cortesPorcino as [$canonico, $cantidad, $aliases]) {
            $catalog = CutCatalog::updateOrCreate(
                ['user_id' => null, 'animal_type_id' => $porcino->id, 'nombre_canonico' => $canonico],
                ['cantidad_esperada' => $cantidad, 'activo' => true]
            );
            foreach ($aliases as $alias) {
                CutAlias::updateOrCreate(
                    ['cut_catalog_id' => $catalog->id, 'alias' => $alias]
                );
            }
        }

        // -------------------------------------------------------
        // AVIAR (pollo)
        // -------------------------------------------------------
        $aviar = AnimalType::where('nombre', 'aviar')->firstOrFail();

        $cortesAviar = [
            ['pechuga entera',      2,  ['doble pechuga', 'pechuga completa', 'suprema entera']],
            ['suprema',             2,  ['pechuga con piel', 'suprema de pollo', 'chicken breast bone-in']],
            ['pechuga sin hueso',   2,  ['filet de pechuga', 'pechuga deshuesada', 'chicken breast boneless']],
            ['muslo',               2,  ['muslo de pollo', 'thigh', 'muslo con piel']],
            ['pata muslo',          2,  ['cuarto trasero', 'leg quarter', 'pata entera', 'pata-muslo']],
            ['pata',                2,  ['pierna', 'drumstick', 'pata de pollo']],
            ['ala',                 2,  ['alita', 'wing', 'ala de pollo', 'alita de pollo']],
            ['media ala',           4,  ['flat wing', 'drummette']],
            ['cogote de pollo',     1,  ['pescuezo de pollo', 'cuello de pollo']],
            ['espinazo',            1,  ['carcaza', 'carcasa', 'hueso de pollo']],
            ['hígado de pollo',     1,  ['liver de pollo', 'higadillo de pollo']],
            ['corazón de pollo',    1,  ['heart de pollo']],
            ['molleja de pollo',    1,  ['ventrículo', 'gizzard', 'mollejas de pollo']],
            ['menudos',             1,  ['menudencias', 'giblets']],
        ];

        foreach ($cortesAviar as [$canonico, $cantidad, $aliases]) {
            $catalog = CutCatalog::updateOrCreate(
                ['user_id' => null, 'animal_type_id' => $aviar->id, 'nombre_canonico' => $canonico],
                ['cantidad_esperada' => $cantidad, 'activo' => true]
            );
            foreach ($aliases as $alias) {
                CutAlias::updateOrCreate(
                    ['cut_catalog_id' => $catalog->id, 'alias' => $alias]
                );
            }
        }
    }
}
