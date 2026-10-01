<?php

use App\Support\CortesPrimarios;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cortes primarios (piezas grandes) y cuarteo:
 * - cut_catalogs.nivel: "musculo" o "primario"; cut_catalog_partes: músculos de cada pieza.
 * - despostes.modo: "musculo", "primario" (media → piezas) o "cuarteo" (pieza → músculos).
 * - animals.cut_catalog_id: el ingreso es una pieza grande (formato "pieza"), comprada
 *   o salida de un desposte por piezas (desposte_origen_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cut_catalogs', function (Blueprint $table) {
            $table->string('nivel', 20)->default('musculo')->after('nombre_canonico');
        });

        Schema::create('cut_catalog_partes', function (Blueprint $table) {
            $table->foreignId('primario_id')->constrained('cut_catalogs')->cascadeOnDelete();
            $table->foreignId('musculo_id')->constrained('cut_catalogs')->cascadeOnDelete();
            $table->primary(['primario_id', 'musculo_id']);
        });

        Schema::table('despostes', function (Blueprint $table) {
            $table->string('modo', 20)->default('musculo')->after('estado');
        });

        Schema::table('animals', function (Blueprint $table) {
            $table->foreignId('cut_catalog_id')->nullable()->after('formato')->constrained('cut_catalogs')->nullOnDelete();
            $table->foreignId('desposte_origen_id')->nullable()->after('cut_catalog_id')->constrained('despostes')->nullOnDelete();
        });

        CortesPrimarios::instalar();
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('desposte_origen_id');
            $table->dropConstrainedForeignId('cut_catalog_id');
        });
        Schema::table('despostes', fn (Blueprint $table) => $table->dropColumn('modo'));
        Schema::dropIfExists('cut_catalog_partes');
        \Illuminate\Support\Facades\DB::table('cut_catalogs')->where('nivel', 'primario')->delete();
        Schema::table('cut_catalogs', fn (Blueprint $table) => $table->dropColumn('nivel'));
    }
};
