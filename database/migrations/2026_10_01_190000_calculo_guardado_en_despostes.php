<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cálculo de cada desposte guardado al terminarlo (kg, rinde, costo, venta,
 * ganancia, margen), para que el listado muestre los valores de ese momento.
 * Los despostes ya terminados se completan con sus medias y cortes guardados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('despostes', function (Blueprint $table) {
            $table->decimal('kg_medias', 10, 3)->nullable()->after('estado');
            $table->decimal('kg_cortes', 10, 3)->nullable()->after('kg_medias');
            $table->decimal('kg_merma', 10, 3)->nullable()->after('kg_cortes');
            $table->decimal('rinde_pct', 6, 2)->nullable()->after('kg_merma');
            $table->decimal('costo', 14, 2)->nullable()->after('rinde_pct');
            $table->decimal('venta', 14, 2)->nullable()->after('costo');
            $table->decimal('ganancia', 14, 2)->nullable()->after('venta');
            $table->decimal('margen_pct', 8, 2)->nullable()->after('ganancia');
        });

        $terminados = DB::table('despostes')->where('estado', 'terminado')->pluck('id');
        foreach ($terminados as $id) {
            $medias = DB::table('desposte_animales')->where('desposte_id', $id)->get(['peso', 'precio_kg']);
            $cortes = DB::table('desposte_cortes')->where('desposte_id', $id)->get(['peso', 'precio_kg']);

            $kgMedias = (float) $medias->sum('peso');
            $kgCortes = (float) $cortes->sum('peso');
            $costo = round($medias->sum(fn ($m) => (float) $m->peso * (float) $m->precio_kg), 2);
            $venta = round($cortes->sum(fn ($c) => (float) $c->peso * (float) ($c->precio_kg ?? 0)), 2);

            DB::table('despostes')->where('id', $id)->update([
                'kg_medias' => $kgMedias,
                'kg_cortes' => $kgCortes,
                'kg_merma' => max(0, $kgMedias - $kgCortes),
                'rinde_pct' => $kgMedias > 0 ? round($kgCortes / $kgMedias * 100, 2) : null,
                'costo' => $costo,
                'venta' => $venta,
                'ganancia' => round($venta - $costo, 2),
                'margen_pct' => $costo > 0 ? round(($venta - $costo) / $costo * 100, 2) : null,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('despostes', function (Blueprint $table) {
            $table->dropColumn(['kg_medias', 'kg_cortes', 'kg_merma', 'rinde_pct', 'costo', 'venta', 'ganancia', 'margen_pct']);
        });
    }
};
