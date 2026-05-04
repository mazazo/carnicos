<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cut_reference_averages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_type_id')->constrained('animal_types')->restrictOnDelete();
            $table->foreignId('cut_catalog_id')->constrained('cut_catalogs')->restrictOnDelete();
            $table->decimal('peso_referencia', 8, 3);
            $table->decimal('kg_promedio', 8, 3);
            $table->timestamps();

            $table->unique(
                ['animal_type_id', 'peso_referencia', 'cut_catalog_id'],
                'cut_reference_avg_unique'
            );
            $table->index(['animal_type_id', 'peso_referencia'], 'cut_reference_avg_type_weight_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cut_reference_averages');
    }
};
