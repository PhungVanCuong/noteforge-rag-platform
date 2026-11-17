<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversion_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('tool_type', [
                'pdf2word', 'word2pdf', 'merge_pdf', 'split_pdf', 
                'img_convert', 'img2pdf', 'vid2mp3', 'transcribe'
            ]);
            $table->json('input_files');
            $table->string('output_path', 500)->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversion_jobs');
    }
};
