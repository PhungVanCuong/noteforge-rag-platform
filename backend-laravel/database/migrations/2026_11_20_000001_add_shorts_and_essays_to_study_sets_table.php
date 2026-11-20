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
        Schema::table('study_sets', function (Blueprint $table) {
            $table->json('shorts_json')->nullable()->after('code_exercises_json');
            $table->json('essays_json')->nullable()->after('shorts_json');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_sets', function (Blueprint $table) {
            $table->dropColumn(['shorts_json', 'essays_json']);
        });
    }
};
