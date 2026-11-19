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
            $table->string('status', 30)->default('completed')->after('is_public');
            $table->unsignedTinyInteger('progress')->default(100)->after('status');
            $table->string('status_message', 255)->nullable()->after('progress');
            $table->text('error_message')->nullable()->after('status_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_sets', function (Blueprint $table) {
            $table->dropColumn(['status', 'progress', 'status_message', 'error_message']);
        });
    }
};
