<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Planes por FUNCIONES (tipos de animal, usuarios, dashboard, app Android) y
 * por TIEMPO (precio por período en plan_precios). Editables desde el panel
 * del administrador. Se cargan los 3 planes iniciales (solo si no hay planes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->unsignedTinyInteger('max_tipos_animal')->nullable(); // NULL = todos
            $table->unsignedSmallInteger('max_usuarios')->nullable();    // NULL = ilimitados
            $table->boolean('incluye_dashboard')->default(false);
            $table->boolean('incluye_app')->default(false);             // app Android (API)
            $table->json('caracteristicas')->nullable();                // textos extra para la página de planes
            $table->string('estilo', 20)->default('normal');            // normal | destacado | premium
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('plan_precios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('planes')->cascadeOnDelete();
            $table->unsignedTinyInteger('meses');                       // 1, 3, 6, 12…
            $table->decimal('precio', 12, 2);
            $table->string('moneda', 3)->default('ARS');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['plan_id', 'meses']);
        });

        $this->cargarPlanesIniciales();
    }

    private function cargarPlanesIniciales(): void
    {
        if (DB::table('planes')->exists()) {
            return;
        }

        $now = now();
        $planes = [
            ['plan-1', 'Básico', 'Para trabajar un tipo de animal.', 1, 1, false, false, 'normal', 1, 15000],
            ['plan-2', 'Profesional', 'Para trabajar dos tipos de animal con tu equipo.', 2, 2, false, false, 'destacado', 2, 20000],
            ['plan-3', 'Completo', 'Los tres tipos de animal, dashboard y app Android.', 3, 4, true, true, 'premium', 3, 25000],
        ];

        foreach ($planes as [$codigo, $nombre, $descripcion, $tipos, $usuarios, $dashboard, $app, $estilo, $orden, $precio]) {
            $planId = DB::table('planes')->insertGetId([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'max_tipos_animal' => $tipos,
                'max_usuarios' => $usuarios,
                'incluye_dashboard' => $dashboard,
                'incluye_app' => $app,
                'caracteristicas' => json_encode(['Soporte por email']),
                'estilo' => $estilo,
                'orden' => $orden,
                'activo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('plan_precios')->insert([
                'plan_id' => $planId, 'meses' => 1, 'precio' => $precio, 'moneda' => 'ARS',
                'activo' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_precios');
        Schema::dropIfExists('planes');
    }
};
