<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cuts', function (Blueprint $table) {
            $table->foreignId('animal_type_id')
                ->nullable()
                ->after('animal_id')
                ->constrained('animal_types')
                ->restrictOnDelete();

            $table->index('animal_type_id');
        });

        DB::statement('UPDATE cuts c INNER JOIN animals a ON a.id = c.animal_id SET c.animal_type_id = a.animal_type_id WHERE c.animal_type_id IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('animal_type_id');
        });
    }
};
