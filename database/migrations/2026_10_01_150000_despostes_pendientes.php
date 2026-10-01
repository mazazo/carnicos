<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Despostes pendientes: se arman pesada por pesada desde la app y quedan en el
 * sistema hasta terminarlos. Las medias elegidas quedan "en_desposte".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('despostes', function (Blueprint $table) {
            $table->string('estado', 20)->default('terminado')->after('animal_type_id');
            $table->index(['carniceria_id', 'estado']);
        });

        Schema::create('desposte_pesadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desposte_id')->constrained('despostes')->cascadeOnDelete();
            $table->foreignId('cut_catalog_id')->constrained('cut_catalogs')->cascadeOnDelete();
            $table->decimal('peso', 10, 3);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desposte_pesadas');

        Schema::table('despostes', function (Blueprint $table) {
            $table->dropIndex(['carniceria_id', 'estado']);
            $table->dropColumn('estado');
        });
    }
};
