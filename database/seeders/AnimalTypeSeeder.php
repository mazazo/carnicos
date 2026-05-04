<?php

namespace Database\Seeders;

use App\Models\AnimalType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnimalTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipos = [
            ['nombre' => 'vacuno', 'modalidad' => 'media_res'],
            ['nombre' => 'porcino', 'modalidad' => 'media_res'],
            ['nombre' => 'aviar', 'modalidad' => 'cajon'],
        ];

        foreach ($tipos as $tipo) {
            AnimalType::query()->updateOrCreate(
                ['nombre' => $tipo['nombre']],
                ['activo' => true, 'modalidad' => $tipo['modalidad']],
            );
        }
    }
}
