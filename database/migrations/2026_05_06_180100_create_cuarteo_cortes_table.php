<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuarteo_partes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuarteo_id')->constrained('cuarteos')->cascadeOnDelete();
            $table->foreignId('cut_catalog_id')->nullable()->constrained('cut_catalogs')->nullOnDelete();
            $table->string('nombre_parte', 120);
            $table->unsignedSmallInteger('cantidad')->default(1);
            $table->decimal('peso', 10, 3);
            $table->decimal('precio_kg', 10, 2)->nullable();
            $table->decimal('porcentaje', 5, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('cuarteo_id');
            $table->index('cut_catalog_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuarteo_partes');
    }
};
