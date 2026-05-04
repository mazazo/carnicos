<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despostes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // despostador interno
            $table->string('despostador_nombre', 150)->nullable(); // despostador externo o nombre libre
            $table->date('fecha_desposte');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('animal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despostes');
    }
};
