<?php

namespace Database\Seeders;

use App\Models\AnimalType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VacunoReferenceAverageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vacuno = AnimalType::query()->where('nombre', 'vacuno')->first();

        if (! $vacuno) {
            return;
        }

        $referenceWeight = 118.000;

        $cuts = [
            ['name' => 'Asado', 'kg' => 10.000],
            ['name' => 'Bife ancho', 'kg' => 7.700],
            ['name' => 'Bife angosto', 'kg' => 4.500],
            ['name' => 'Bola lomo', 'kg' => 3.800],
            ['name' => 'Colita cuadril', 'kg' => 1.250],
            ['name' => 'Cuadrada', 'kg' => 5.500],
            ['name' => 'Cuadril', 'kg' => 2.900],
            ['name' => 'Entrana', 'kg' => 0.660],
            ['name' => 'Espinazo', 'kg' => 2.500],
            ['name' => 'Falda', 'kg' => 1.300],
            ['name' => 'Lomo', 'kg' => 2.150],
            ['name' => 'Matambre', 'kg' => 1.640],
            ['name' => 'Nalga', 'kg' => 5.200],
            ['name' => 'Osobuco', 'kg' => 8.900],
            ['name' => 'Paleta', 'kg' => 6.900],
            ['name' => 'Palomita', 'kg' => 1.100],
            ['name' => 'Peceto', 'kg' => 2.200],
            ['name' => 'Roast beef', 'kg' => 7.000],
            ['name' => 'Tapa asado', 'kg' => 3.430],
            ['name' => 'Tapa nalga', 'kg' => 1.900],
            ['name' => 'Tortiguita', 'kg' => 1.600],
            ['name' => 'Vacio', 'kg' => 6.500],
            ['name' => 'Picada', 'kg' => 8.000],
            ['name' => 'Grasas', 'kg' => 6.000],
        ];

        foreach ($cuts as $cut) {
            DB::table('cut_catalogs')->updateOrInsert(
                [
                    'animal_type_id' => $vacuno->id,
                    'nombre_canonico' => $cut['name'],
                ],
                [
                    'activo' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $catalog = DB::table('cut_catalogs')
                ->where('animal_type_id', $vacuno->id)
                ->where('nombre_canonico', $cut['name'])
                ->first();

            if (! $catalog) {
                continue;
            }

            DB::table('cut_aliases')->updateOrInsert(
                [
                    'cut_catalog_id' => $catalog->id,
                    'alias' => $cut['name'],
                ],
                [
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::table('cut_reference_averages')->updateOrInsert(
                [
                    'animal_type_id' => $vacuno->id,
                    'cut_catalog_id' => $catalog->id,
                    'peso_referencia' => $referenceWeight,
                ],
                [
                    'kg_promedio' => $cut['kg'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
