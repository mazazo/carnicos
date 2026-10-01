<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cortes del catálogo general que una carnicería no usa. El corte general no
 * se copia ni se modifica: mantiene su id (precios e historial de despostes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cortes_ocultos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carniceria_id')->constrained('carnicerias')->cascadeOnDelete();
            $table->foreignId('cut_catalog_id')->constrained('cut_catalogs')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['carniceria_id', 'cut_catalog_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cortes_ocultos');
    }
};
