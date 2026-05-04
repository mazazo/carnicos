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
        Schema::table('animal_types', function (Blueprint $table) {
            $table->enum('modalidad', ['media_res', 'cajon'])->default('media_res')->after('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('animal_types', function (Blueprint $table) {
            $table->dropColumn('modalidad');
        });
    }
};
