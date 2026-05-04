<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desposte_cortes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desposte_id')->constrained('despostes')->cascadeOnDelete();
            $table->foreignId('cut_catalog_id')->nullable()->constrained('cut_catalogs')->nullOnDelete(); // enlace opcional al catálogo
            $table->string('nombre', 120);         // nombre del corte (se puede copiar del catálogo)
            $table->unsignedSmallInteger('cantidad')->default(1); // cantidad de piezas
            $table->decimal('peso', 10, 3);        // peso total de ese corte en kg
            $table->decimal('precio_kg', 10, 2)->nullable();
            $table->timestamps();

            $table->index('desposte_id');
            $table->index('cut_catalog_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desposte_cortes');
    }
};
