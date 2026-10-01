<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permisos de los empleados (ingresos, producciones, cortes, app). El dueño
 * los tiene todos siempre. Los empleados que ya existían conservan acceso a todo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('permisos')->nullable()->after('rol');
        });

        DB::table('users')->where('rol', User::ROL_EMPLEADO)
            ->update(['permisos' => json_encode(array_keys(User::PERMISOS))]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('permisos');
        });
    }
};
