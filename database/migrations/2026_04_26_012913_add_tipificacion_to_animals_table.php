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
        Schema::table('animals', function (Blueprint $table) {
            // Origen / trazabilidad
            $table->string('frigorifico')->nullable()->after('fecha');
            $table->string('proveedor')->nullable()->after('frigorifico');
            $table->date('fecha_faena')->nullable()->after('proveedor');

            // Tipificación normalizada (SENASA - vacunos)
            $table->string('categoria')->nullable()->after('fecha_faena');        // novillo, novillito, vaquillona, vaca, toro, buey, ternero, ternera
            $table->unsignedTinyInteger('conformacion')->nullable()->after('categoria');  // 1-5 (muscularidad)
            $table->unsignedTinyInteger('terminacion')->nullable()->after('conformacion');// 0-4 (cobertura grasa)
            $table->unsignedTinyInteger('denticion')->nullable()->after('terminacion');   // 0, 2, 4, 6, 8 (dientes permanentes)
            $table->unsignedTinyInteger('color_grasa')->nullable()->after('denticion');   // 1=Blanca, 2=Amarilla pálida, 3=Amarilla
            $table->unsignedTinyInteger('color_carne')->nullable()->after('color_grasa'); // 1=Rosado, 2=Rojo claro, 3=Rojo, 4=Rojo oscuro
            $table->decimal('ph', 4, 2)->nullable()->after('color_carne');
            $table->decimal('temperatura', 5, 2)->nullable()->after('ph');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn([
                'frigorifico', 'proveedor', 'fecha_faena',
                'categoria', 'conformacion', 'terminacion', 'denticion',
                'color_grasa', 'color_carne', 'ph', 'temperatura',
            ]);
        });
    }
};
