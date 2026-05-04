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
        Schema::create('cut_catalogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_type_id')->constrained('animal_types')->restrictOnDelete();
            $table->string('nombre_canonico', 120);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['animal_type_id', 'nombre_canonico'], 'cut_catalogs_type_name_unique');
            $table->index('animal_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cut_catalogs');
    }
};
