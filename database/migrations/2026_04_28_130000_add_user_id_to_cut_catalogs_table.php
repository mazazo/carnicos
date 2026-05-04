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
        Schema::table('cut_catalogs', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->dropUnique('cut_catalogs_type_name_unique');

            $table->unique(
                ['user_id', 'animal_type_id', 'nombre_canonico'],
                'cut_catalogs_user_type_name_unique'
            );
            $table->index(['user_id', 'animal_type_id'], 'cut_catalogs_user_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cut_catalogs', function (Blueprint $table) {
            $table->dropIndex('cut_catalogs_user_type_index');
            $table->dropUnique('cut_catalogs_user_type_name_unique');
            $table->dropConstrainedForeignId('user_id');

            $table->unique(['animal_type_id', 'nombre_canonico'], 'cut_catalogs_type_name_unique');
        });
    }
};
