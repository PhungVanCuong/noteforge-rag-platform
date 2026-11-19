<?php

namespace App\Http\Controllers;

use App\Models\StudySet;
use App\Models\Document;
use App\Models\QuestionProgress;
use App\Models\ExamAttempt;
use App\Services\AiServiceBridge;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class StudySetController extends Controller
{
    protected AiServiceBridge $aiBridge;

    public function __construct(AiServiceBridge $aiBridge)
    {
        $this->aiBridge = $aiBridge;
    }

    /**
     * List user's study sets.
     */
    public function index(Request $request)
    {
        $sets = $request->user()->studySets()
            ->with('notebook:id,title,subject_code')
            ->withCount(['examAttempts'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sets
        ]);
    }

    /**
     * Generate a new study set using Gemini 2.5 Flash Structured Output.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'notebook_id' => 'nullable|integer',
            'document_id' => 'nullable|integer',
            'file' => 'nullable|file|mimes:pdf,docx,doc,txt|max:51200',
            'raw_text' => 'nullable|string',
            'title' => 'nullable|string|max:255',
        ]);

        $filePath = null;
        $title = $request->title;

        if ($request->has('document_id')) {
            $doc = $request->user()->documents()->findOrFail($request->document_id);
            $filePath = $doc->file_path;
            if (!$title) {
                $title = "Bộ đề ôn tập: " . Pathinfo($doc->file_name, PATHINFO_FILENAME);
            }
        } elseif ($request->hasFile('file')) {
            $file = $request->file('file');
            $dest = storage_path('app/public/study_temp');
            if (!file_exists($dest)) {
                mkdir($dest, 0755, true);
            }
            $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move($dest, $name);
            $filePath = $dest . DIRECTORY_SEPARATOR . $name;
            if (!$title) {
                $title = "Bộ đề: " . Pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            }
        } elseif (!$request->filled('raw_text')) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn tài liệu hoặc nhập nội dung văn bản để sinh bộ đề.'
            ], 422);
        }

        if (!$title) {
            $title = "Bộ đề ôn tập AI - " . date('d/m/Y H:i');
        }

        try {
            $quizData = $this->aiBridge->generateQuiz(
                filePath: $filePath,
                rawText: $request->raw_text,
                user: $request->user()
            );

            $questions = $quizData['questions'] ?? [];
            $codeExercises = $quizData['code_exercises'] ?? [];

            // Chặn tuyệt đối lưu bộ đề rỗng 0 câu • 0 code
            if (empty($questions) && empty($codeExercises)) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI không tìm thấy hoặc không trích xuất được câu hỏi nào từ nội dung tài liệu. Vui lòng kiểm tra lại tệp tải lên.'
                ], 422);
            }

            $studySet = StudySet::create([
                'notebook_id' => $request->notebook_id,
                'user_id' => $request->user()->id,
                'share_slug' => (string) Str::uuid(),
                'title' => $title,
                'categories_json' => $quizData['categories'] ?? ['Tổng hợp kiến thức'],
                'questions_json' => $questions,
                'code_exercises_json' => $codeExercises,
                'is_public' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Hệ thống đã chuyển hóa thành công bộ đề với " . count($questions) . " câu trắc nghiệm và " . count($codeExercises) . " bài tập code!",
                'data' => $studySet,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi sinh bộ đề AI: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show study set by ID or share_slug.
     */
    public function show(Request $request, $idOrSlug)
    {
        $studySet = StudySet::where('id', $idOrSlug)
            ->orWhere('share_slug', $idOrSlug)
            ->with('notebook:id,title,subject_code')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $studySet
        ]);
    }

    /**
     * Update progress of a question into MySQL database.
     */
    public function updateProgress(Request $request, $id)
    {
        $validated = $request->validate([
            'question_uid' => 'required|string',
            'status' => 'required|in:right,wrong',
        ]);

        $studySet = StudySet::findOrFail($id);

        $progress = QuestionProgress::where('user_id', $request->user()->id)
            ->where('study_set_id', $studySet->id)
            ->where('question_uid', $validated['question_uid'])
            ->first();

        if (!$progress) {
            $progress = QuestionProgress::create([
                'user_id' => $request->user()->id,
                'study_set_id' => $studySet->id,
                'question_uid' => $validated['question_uid'],
                'status' => $validated['status'],
                'wrong_count' => $validated['status'] === 'wrong' ? 1 : 0,
                'last_practiced_at' => Carbon::now(),
            ]);
        } else {
            $isWrong = $validated['status'] === 'wrong';
            $progress->update([
                'status' => $validated['status'],
                'wrong_count' => $isWrong ? ($progress->wrong_count + 1) : $progress->wrong_count,
                'last_practiced_at' => Carbon::now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $progress
        ]);
    }

    /**
     * Get user progress for this study set.
     */
    public function getProgress(Request $request, $id)
    {
        $studySet = StudySet::findOrFail($id);

        $progressList = QuestionProgress::where('user_id', $request->user()->id)
            ->where('study_set_id', $studySet->id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $progressList
        ]);
    }

    /**
     * Submit timed mock exam attempt.
     */
    public function submitExamAttempt(Request $request, $id)
    {
        $validated = $request->validate([
            'score_10' => 'required|numeric|min:0|max:10',
            'correct_count' => 'required|integer|min:0',
            'total_questions' => 'required|integer|min:1',
            'duration_seconds' => 'required|integer|min:0',
            'answers_detail' => 'required|array',
        ]);

        $studySet = StudySet::findOrFail($id);

        $attempt = ExamAttempt::create([
            'user_id' => $request->user()->id,
            'study_set_id' => $studySet->id,
            'score_10' => $validated['score_10'],
            'correct_count' => $validated['correct_count'],
            'total_questions' => $validated['total_questions'],
            'duration_seconds' => $validated['duration_seconds'],
            'answers_detail_json' => $validated['answers_detail'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu kết quả thi thử thành công!',
            'data' => $attempt
        ]);
    }

    /**
     * List user exam attempts for this study set.
     */
    public function listAttempts(Request $request, $id)
    {
        $studySet = StudySet::findOrFail($id);

        $attempts = ExamAttempt::where('user_id', $request->user()->id)
            ->where('study_set_id', $studySet->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $attempts
        ]);
    }

    /**
     * Export standalone offline single-file HTML.
     */
    public function exportOfflineHtml($idOrSlug)
    {
        $studySet = StudySet::where('id', $idOrSlug)
            ->orWhere('share_slug', $idOrSlug)
            ->firstOrFail();

        $html = view('study_export', ['studySet' => $studySet])->render();
        $safeSlug = Str::slug($studySet->title) ?: 'noteforge_study';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$safeSlug}_offline.html\"",
        ]);
    }

    /**
     * Delete study set.
     */
    public function destroy(Request $request, $id)
    {
        $studySet = StudySet::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $studySet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa bộ đề ôn tập thành công.'
        ]);
    }
}
