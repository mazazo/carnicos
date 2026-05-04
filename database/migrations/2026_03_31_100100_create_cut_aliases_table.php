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
        Schema::create('cut_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cut_catalog_id')->constrained('cut_catalogs')->cascadeOnDelete();
            $table->string('alias', 120);
            $table->timestamps();

            $table->unique(['cut_catalog_id', 'alias'], 'cut_aliases_catalog_alias_unique');
            $table->index('alias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cut_aliases');
    }
};
