<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El cliente pasa a ser la CARNICERÍA (con varios usuarios), no el usuario.
 *  - carnicerias: el cliente.
 *  - users: carniceria_id (NULL = administrador de la plataforma sin carnicería) y rol.
 *  - animals, cut_catalogs (NULL = catálogo general), cut_user_prices: carniceria_id.
 *    user_id se conserva como "quién lo cargó".
 * Los usuarios existentes quedan como dueños de una carnicería propia con sus datos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carnicerias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('razon_social', 150)->nullable();
            $table->string('cuit', 11)->nullable()->unique();
            $table->string('email', 150)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('estado', 20)->default('activa'); // activa | suspendida
            $table->timestamps();

            $table->index('estado');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('carniceria_id')->nullable()->after('id')->constrained('carnicerias')->nullOnDelete();
            $table->string('rol', 20)->default('empleado')->after('carniceria_id'); // dueno | empleado
        });

        Schema::table('animals', function (Blueprint $table) {
            $table->foreignId('carniceria_id')->nullable()->after('id')->constrained('carnicerias')->cascadeOnDelete();
            $table->index(['carniceria_id', 'fecha']);
        });

        Schema::table('cut_catalogs', function (Blueprint $table) {
            $table->foreignId('carniceria_id')->nullable()->after('id')->constrained('carnicerias')->cascadeOnDelete();
            $table->unique(['carniceria_id', 'animal_type_id', 'nombre_canonico'], 'cut_catalogs_carniceria_type_name_unique');
        });

        Schema::table('cut_user_prices', function (Blueprint $table) {
            $table->foreignId('carniceria_id')->nullable()->after('id')->constrained('carnicerias')->cascadeOnDelete();
            $table->unique(['carniceria_id', 'cut_id'], 'cut_prices_carniceria_cut_unique');
        });

        $this->pasarDatosExistentes();
    }

    /** Cada usuario existente pasa a ser dueño de su carnicería, con sus datos. */
    private function pasarDatosExistentes(): void
    {
        $now = now();

        foreach (DB::table('users')->whereNull('carniceria_id')->get() as $user) {
            $carniceriaId = DB::table('carnicerias')->insertGetId([
                'nombre' => trim('Carnicería de '.$user->name),
                'email' => $user->email,
                'telefono' => $user->movil ?? null,
                'estado' => 'activa',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('users')->where('id', $user->id)->update(['carniceria_id' => $carniceriaId, 'rol' => 'dueno']);
            DB::table('animals')->where('user_id', $user->id)->update(['carniceria_id' => $carniceriaId]);
            DB::table('cut_catalogs')->where('user_id', $user->id)->update(['carniceria_id' => $carniceriaId]);
            DB::table('cut_user_prices')->where('user_id', $user->id)->update(['carniceria_id' => $carniceriaId]);
        }
    }

    public function down(): void
    {
        Schema::table('cut_user_prices', function (Blueprint $table) {
            $table->dropUnique('cut_prices_carniceria_cut_unique');
            $table->dropConstrainedForeignId('carniceria_id');
        });

        Schema::table('cut_catalogs', function (Blueprint $table) {
            $table->dropUnique('cut_catalogs_carniceria_type_name_unique');
            $table->dropConstrainedForeignId('carniceria_id');
        });

        Schema::table('animals', function (Blueprint $table) {
            $table->dropIndex(['carniceria_id', 'fecha']);
            $table->dropConstrainedForeignId('carniceria_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('carniceria_id');
            $table->dropColumn('rol');
        });

        Schema::dropIfExists('carnicerias');
    }
};
