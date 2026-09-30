<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuarteos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->foreignId('animal_type_id')->constrained('animal_types')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_cuarteo');
            $table->string('subtipo', 80)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['animal_id', 'fecha_cuarteo']);
            $table->index(['animal_type_id', 'fecha_cuarteo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuarteos');
    }
};
