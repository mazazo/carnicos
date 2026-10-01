<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suscripción y pagos pasan a ser de la CARNICERÍA:
 *  - subscriptions: carniceria_id, plan_id y el período contratado (meses, precio).
 *  - payments: carniceria_id, plan_id, meses y datos del proveedor (Mercado Pago o manual).
 *  - carniceria_tipos_animal: los tipos de animal que eligió según su plan.
 *  - suscripcion_movimientos: historial (prueba, pago, días sumados, plan habilitado…).
 *  - webhook_eventos: avisos recibidos de Mercado Pago (se procesan una sola vez).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('carniceria_id')->nullable()->after('id')->constrained('carnicerias')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->after('carniceria_id')->constrained('planes')->nullOnDelete();
            $table->unsignedTinyInteger('meses')->nullable()->after('plan');
            $table->decimal('precio', 12, 2)->nullable()->after('meses');
            $table->string('origen', 20)->nullable()->after('status'); // prueba | pago | manual
            $table->index(['carniceria_id', 'status', 'ends_at']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change(); // quién la contrató (opcional)
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('carniceria_id')->nullable()->after('id')->constrained('carnicerias')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->after('carniceria_id')->constrained('planes')->nullOnDelete();
            $table->unsignedTinyInteger('meses')->nullable()->after('plan_id');
            $table->string('proveedor', 20)->default('manual')->after('method'); // manual | mercadopago
            $table->string('proveedor_pago_id', 60)->nullable()->after('proveedor');
            $table->string('proveedor_preferencia_id', 80)->nullable()->after('proveedor_pago_id');
            $table->json('proveedor_respuesta')->nullable()->after('proveedor_preferencia_id');
            $table->foreignId('registrado_por')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->index(['carniceria_id', 'status']);
            $table->unique(['proveedor', 'proveedor_pago_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('subscription_id')->nullable()->change(); // se vincula al aprobarse
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::create('carniceria_tipos_animal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carniceria_id')->constrained('carnicerias')->cascadeOnDelete();
            $table->foreignId('animal_type_id')->constrained('animal_types')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['carniceria_id', 'animal_type_id']);
        });

        Schema::create('suscripcion_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carniceria_id')->constrained('carnicerias')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->string('tipo', 30); // prueba | pago | dias | plan_manual | suspension | reactivacion | tipos_animal
            $table->string('detalle', 255);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['carniceria_id', 'created_at']);
        });

        Schema::create('webhook_eventos', function (Blueprint $table) {
            $table->id();
            $table->string('proveedor', 20);
            $table->string('evento_id', 80);          // x-request-id / id del aviso
            $table->string('tipo', 40)->nullable();   // payment, merchant_order…
            $table->string('recurso_id', 80)->nullable();
            $table->json('payload');
            $table->string('estado', 20)->default('recibido'); // recibido | procesado | ignorado | error
            $table->string('error', 500)->nullable();
            $table->timestamp('procesado_at')->nullable();
            $table->timestamps();
            $table->unique(['proveedor', 'evento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_eventos');
        Schema::dropIfExists('suscripcion_movimientos');
        Schema::dropIfExists('carniceria_tipos_animal');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['proveedor', 'proveedor_pago_id']);
            $table->dropIndex(['carniceria_id', 'status']);
            $table->dropConstrainedForeignId('registrado_por');
            $table->dropConstrainedForeignId('plan_id');
            $table->dropConstrainedForeignId('carniceria_id');
            $table->dropColumn(['meses', 'proveedor', 'proveedor_pago_id', 'proveedor_preferencia_id', 'proveedor_respuesta']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['carniceria_id', 'status', 'ends_at']);
            $table->dropConstrainedForeignId('plan_id');
            $table->dropConstrainedForeignId('carniceria_id');
            $table->dropColumn(['meses', 'precio', 'origen']);
        });
    }
};
