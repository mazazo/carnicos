<?php

use App\Support\CortesPrimarios;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Piezas grandes del cerdo y del pollo (las del vacuno ya estaban; instalar() no duplica). */
return new class extends Migration
{
    public function up(): void
    {
        CortesPrimarios::instalar();
    }

    public function down(): void
    {
        $nombres = array_merge(array_keys(CortesPrimarios::PIEZAS['porcino']), array_keys(CortesPrimarios::PIEZAS['aviar']));
        DB::table('cut_catalogs')->whereNull('carniceria_id')->where('nivel', 'primario')->whereIn('nombre_canonico', $nombres)->delete();
    }
};
