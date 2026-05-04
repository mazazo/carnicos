<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cut_catalogs', function (Blueprint $table) {
            // Cantidad de piezas esperadas al despostar una res de este tipo
            $table->unsignedSmallInteger('cantidad_esperada')->default(1)->after('nombre_canonico');
        });
    }

    public function down(): void
    {
        Schema::table('cut_catalogs', function (Blueprint $table) {
            $table->dropColumn('cantidad_esperada');
        });
    }
};
