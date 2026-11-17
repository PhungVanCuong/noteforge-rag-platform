<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('study_set_id')->constrained('study_sets')->onDelete('cascade');
            $table->decimal('score_10', 4, 2);
            $table->unsignedInteger('correct_count');
            $table->unsignedInteger('total_questions');
            $table->unsignedInteger('duration_seconds');
            $table->json('answers_detail_json');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
