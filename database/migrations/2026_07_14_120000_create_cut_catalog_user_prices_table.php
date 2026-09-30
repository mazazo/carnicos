<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cut_user_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cut_id')->constrained('cuts')->cascadeOnDelete();
            $table->decimal('precio_kg', 10, 2);
            $table->char('currency', 3)->default('ARS');
            $table->timestamps();

            $table->unique(['user_id', 'cut_id'], 'cut_user_prices_unique');
            $table->index(['cut_id', 'user_id'], 'cut_user_prices_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cut_user_prices');
    }
};
