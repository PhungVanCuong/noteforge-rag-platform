<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notebook_id')->nullable()->constrained('notebooks')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('share_slug', 64)->unique();
            $table->string('title');
            $table->json('categories_json');
            $table->json('questions_json');
            $table->json('code_exercises_json');
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_sets');
    }
};
