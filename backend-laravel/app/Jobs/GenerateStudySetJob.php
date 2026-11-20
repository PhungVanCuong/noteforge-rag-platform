<?php

namespace App\Jobs;

use App\Models\StudySet;
use App\Models\User;
use App\Services\AiServiceBridge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerateStudySetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     * 3000 seconds = 50 minutes to eliminate any timeout on heavy multi-batch documents.
     */
    public int $timeout = 3000;

    /**
     * Number of times to attempt this job.
     */
    public int $tries = 2;

    public int $studySetId;
    public ?string $filePath;
    public ?string $rawText;
    public int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $studySetId, ?string $filePath, ?string $rawText, int $userId)
    {
        $this->studySetId = $studySetId;
        $this->filePath = $filePath;
        $this->rawText = $rawText;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(AiServiceBridge $aiBridge): void
    {
        // Unblock PHP execution time limits in CLI worker
        if (function_exists('set_time_limit')) {
            @set_time_limit(1200);
        }
        @ini_set('max_execution_time', 1200);

        $studySet = StudySet::find($this->studySetId);
        if (!$studySet) {
            Log::error("GenerateStudySetJob: StudySet {$this->studySetId} not found in database.");
            return;
        }

        $user = User::find($this->userId);

        try {
            // Step 1: Initialize
            $studySet->update([
                'status' => 'processing',
                'progress' => 10,
                'status_message' => 'Đang phân tích cấu trúc tài liệu và chuyển giao tới AI Engine...',
                'error_message' => null,
            ]);

            // Construct local webhook URL for live batch progress updates from Python
            $internalBaseUrl = rtrim(config('app.url', 'http://127.0.0.1:8000'), '/');
            $callbackUrl = "{$internalBaseUrl}/api/internal/study-sets/{$this->studySetId}/progress";

            // Step 2: Call Python AI Microservice with 10-minute timeout
            $quizData = $aiBridge->generateQuiz(
                filePath: $this->filePath,
                rawText: $this->rawText,
                user: $user,
                studySetId: $this->studySetId,
                callbackUrl: $callbackUrl
            );

            // Step 3: Validate generated output across all 4 components
            $questions = $quizData['questions'] ?? [];
            $codeExercises = $quizData['code_exercises'] ?? [];
            $shorts = $quizData['shorts'] ?? [];
            $essays = $quizData['essays'] ?? [];
            $categories = $quizData['categories'] ?? ['Tổng hợp kiến thức'];

            if (empty($questions) && empty($codeExercises) && empty($shorts) && empty($essays)) {
                throw new Exception('AI không trích xuất được nội dung câu hỏi, bài tập code hay tự luận nào từ tài liệu.');
            }

            // Step 4: Save completed study set
            $studySet->update([
                'categories_json' => $categories,
                'questions_json' => $questions,
                'code_exercises_json' => $codeExercises,
                'shorts_json' => $shorts,
                'essays_json' => $essays,
                'status' => 'completed',
                'progress' => 100,
                'status_message' => 'Hoàn tất! Bộ đề đã sẵn sàng.',
                'error_message' => null,
            ]);

            Log::info("GenerateStudySetJob SUCCESS for StudySet {$this->studySetId}: " . count($questions) . " questions, " . count($codeExercises) . " code exercises, " . count($shorts) . " shorts, " . count($essays) . " essays.");

        } catch (\Throwable $e) {
            Log::error("GenerateStudySetJob FAILED for StudySet {$this->studySetId}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $studySet->update([
                'status' => 'failed',
                'progress' => 0,
                'status_message' => 'Sinh bộ đề bằng AI thất bại.',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            // Clean up temporary upload file if it was saved in study_temp
            if ($this->filePath && file_exists($this->filePath) && str_contains($this->filePath, 'study_temp')) {
                try {
                    @unlink($this->filePath);
                } catch (\Throwable $t) {
                    // Ignore deletion warning
                }
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        $studySet = StudySet::find($this->studySetId);
        if ($studySet && $studySet->status !== 'completed') {
            $studySet->update([
                'status' => 'failed',
                'progress' => 0,
                'status_message' => 'Sinh bộ đề bằng AI thất bại do quá thời gian xử lý.',
                'error_message' => $exception ? $exception->getMessage() : 'Hàng đợi xử lý gặp sự cố quá tải.',
            ]);
        }
    }
}
