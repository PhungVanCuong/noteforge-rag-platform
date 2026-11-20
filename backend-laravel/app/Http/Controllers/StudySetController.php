<?php

namespace App\Http\Controllers;

use App\Models\StudySet;
use App\Models\Document;
use App\Models\QuestionProgress;
use App\Models\ExamAttempt;
use App\Services\AiServiceBridge;
use App\Jobs\GenerateStudySetJob;
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
     * Generate a new study set asynchronously via Queue to eliminate timeouts.
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
                $title = "Bộ đề ôn tập: " . pathinfo($doc->file_name, PATHINFO_FILENAME);
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
                $title = "Bộ đề: " . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
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
            // Create draft StudySet in 'processing' state with initial 5% progress
            $studySet = StudySet::create([
                'notebook_id' => $request->notebook_id,
                'user_id' => $request->user()->id,
                'share_slug' => (string) Str::uuid(),
                'title' => $title,
                'categories_json' => ['Đang khởi tạo...'],
                'questions_json' => [],
                'code_exercises_json' => [],
                'is_public' => true,
                'status' => 'processing',
                'progress' => 5,
                'status_message' => 'Đang đưa tác vụ vào hàng đợi xử lý...',
                'error_message' => null,
            ]);

            // Dispatch background queue job (timeout: 1200s)
            GenerateStudySetJob::dispatch(
                $studySet->id,
                $filePath,
                $request->raw_text,
                $request->user()->id
            );

            // Immediate 202 Accepted response for Vue polling
            return response()->json([
                'success' => true,
                'message' => 'Yêu cầu sinh bộ đề đã được đưa vào hàng đợi xử lý nền.',
                'data' => [
                    'study_set_id' => $studySet->id,
                    'id' => $studySet->id,
                    'title' => $studySet->title,
                    'status' => 'processing',
                    'progress' => 5,
                    'status_message' => 'Đang đưa tác vụ vào hàng đợi xử lý...',
                ],
            ], 202);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi khởi tạo tác vụ sinh đề: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Poll real-time status and progress of a study set generation job.
     */
    public function status(Request $request, $id)
    {
        $studySet = StudySet::where('id', $id)
            ->where(function ($q) use ($request) {
                if ($request->user()) {
                    $q->where('user_id', $request->user()->id)->orWhere('is_public', true);
                } else {
                    $q->where('is_public', true);
                }
            })
            ->first();

        if (!$studySet) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bộ đề'
            ], 404);
        }

        $questions = $studySet->questions_json ?? [];
        $code = $studySet->code_exercises_json ?? [];
        $shorts = $studySet->shorts_json ?? [];
        $essays = $studySet->essays_json ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $studySet->id,
                'title' => $studySet->title,
                'share_slug' => $studySet->share_slug,
                'status' => $studySet->status ?? 'completed',
                'progress' => (int) ($studySet->progress ?? 100),
                'status_message' => $studySet->status_message ?? '',
                'error_message' => $studySet->error_message,
                'questions_count' => count($questions),
                'code_count' => count($code),
                'shorts_count' => count($shorts),
                'essays_count' => count($essays),
                'categories' => $studySet->categories_json ?? [],
                'created_at' => $studySet->created_at,
                'updated_at' => $studySet->updated_at,
            ]
        ]);
    }

    /**
     * Update study set (rename title, update categories).
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'categories' => 'nullable|array',
        ]);

        $studySet = StudySet::where('id', $id)
            ->where(function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)
                  ->orWhereNull('user_id');
            })
            ->firstOrFail();

        $studySet->title = trim($validated['title']);
        if (isset($validated['categories'])) {
            $studySet->categories_json = $validated['categories'];
        }
        $studySet->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật tên bộ đề thành công.',
            'data' => $studySet
        ]);
    }

    /**
     * Internal webhook for Python AI Service to report batch progress (0% - 100%).
     */
    public function updateProgressInternal(Request $request, $id)
    {
        $validated = $request->validate([
            'progress' => 'required|numeric|min:0|max:100',
            'status_message' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:processing,completed,failed',
            'error_message' => 'nullable|string',
        ]);

        $studySet = StudySet::find($id);
        if (!$studySet) {
            // Return 200 with notice instead of 404 to avoid throwing exception in AI client
            return response()->json([
                'success' => false,
                'message' => "StudySet with ID {$id} not found or not initialized yet."
            ], 200);
        }

        $updateData = [
            'progress' => (int) $validated['progress'],
        ];

        if (!empty($validated['status_message'])) {
            $updateData['status_message'] = $validated['status_message'];
        }

        if (!empty($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }

        if (isset($validated['error_message'])) {
            $updateData['error_message'] = $validated['error_message'];
        }

        $studySet->update($updateData);

        return response()->json(['success' => true]);
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
