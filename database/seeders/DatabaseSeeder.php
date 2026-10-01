<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\CortesPrimarios;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            AnimalTypeSeeder::class,
            CutCatalogSeeder::class,
            VacunoReferenceAverageSeeder::class,
        ]);

        // Piezas grandes del vacuno y sus músculos (necesita el catálogo cargado).
        CortesPrimarios::instalar();
    }
}
