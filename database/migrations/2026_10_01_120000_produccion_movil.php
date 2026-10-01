<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Producción desde la app:
 *  - animals: formato del ingreso (res, media res, cajón), cantidad y estado
 *    (disponible hasta que se desposta).
 *  - despostes: de la carnicería y de una o varias medias (desposte_animales,
 *    con el peso y precio de cada una al momento de despostar).
 *  - precios_corte: precio de venta por kg de cada corte del catálogo, por carnicería.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->string('formato', 20)->nullable()->after('animal_type_id');
            $table->unsignedSmallInteger('cantidad')->default(1)->after('formato');
            $table->string('estado', 20)->default('disponible')->after('cantidad');
            $table->index(['carniceria_id', 'estado']);
        });

        Schema::table('despostes', function (Blueprint $table) {
            $table->foreignId('carniceria_id')->nullable()->after('id')->constrained('carnicerias')->cascadeOnDelete();
            $table->foreignId('animal_type_id')->nullable()->after('carniceria_id')->constrained('animal_types')->nullOnDelete();
            $table->foreignId('animal_id')->nullable()->change(); // con varias medias, quedan en desposte_animales
        });

        Schema::create('desposte_animales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desposte_id')->constrained('despostes')->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->decimal('peso', 10, 3);
            $table->decimal('precio_kg', 10, 2);
            $table->timestamps();

            $table->unique(['desposte_id', 'animal_id']);
        });

        Schema::create('precios_corte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carniceria_id')->constrained('carnicerias')->cascadeOnDelete();
            $table->foreignId('cut_catalog_id')->constrained('cut_catalogs')->cascadeOnDelete();
            $table->decimal('precio_kg', 10, 2);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['carniceria_id', 'cut_catalog_id']);
        });

        // Formato según el tipo de animal; despostes existentes → su carnicería y su media.
        DB::table('animals')
            ->join('animal_types', 'animal_types.id', '=', 'animals.animal_type_id')
            ->whereNull('animals.formato')
            ->update(['animals.formato' => DB::raw("CASE WHEN animal_types.modalidad = 'cajon' THEN 'cajon' ELSE 'media_res' END")]);

        DB::table('despostes')
            ->join('animals', 'animals.id', '=', 'despostes.animal_id')
            ->update([
                'despostes.carniceria_id' => DB::raw('animals.carniceria_id'),
                'despostes.animal_type_id' => DB::raw('animals.animal_type_id'),
            ]);

        $now = now();
        DB::table('despostes')->join('animals', 'animals.id', '=', 'despostes.animal_id')
            ->select('despostes.id as desposte_id', 'animals.id as animal_id', 'animals.peso_total', 'animals.precio_kg')
            ->orderBy('despostes.id')
            ->each(function ($fila) use ($now) {
                DB::table('desposte_animales')->insertOrIgnore([
                    'desposte_id' => $fila->desposte_id,
                    'animal_id' => $fila->animal_id,
                    'peso' => $fila->peso_total,
                    'precio_kg' => $fila->precio_kg,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        DB::table('animals')->whereIn('id', DB::table('desposte_animales')->select('animal_id'))->update(['estado' => 'despostada']);
    }

    public function down(): void
    {
        Schema::dropIfExists('precios_corte');
        Schema::dropIfExists('desposte_animales');

        Schema::table('despostes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('animal_type_id');
            $table->dropConstrainedForeignId('carniceria_id');
        });

        Schema::table('animals', function (Blueprint $table) {
            $table->dropIndex(['carniceria_id', 'estado']);
            $table->dropColumn(['formato', 'cantidad', 'estado']);
        });
    }
};
