<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('study_set_id')->constrained('study_sets')->onDelete('cascade');
            $table->string('question_uid', 64);
            $table->enum('status', ['right', 'wrong']);
            $table->unsignedInteger('wrong_count')->default(0);
            $table->timestamp('last_practiced_at')->nullable()->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'study_set_id', 'question_uid']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_progress');
    }
};
