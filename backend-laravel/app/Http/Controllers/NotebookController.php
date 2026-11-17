<?php

namespace App\Http\Controllers;

use App\Models\Notebook;
use App\Models\Document;
use App\Services\AiServiceBridge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class NotebookController extends Controller
{
    protected AiServiceBridge $aiBridge;

    public function __construct(AiServiceBridge $aiBridge)
    {
        $this->aiBridge = $aiBridge;
    }

    public function index(Request $request)
    {
        $notebooks = $request->user()->notebooks()
            ->withCount(['documents', 'studySets'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notebooks
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject_code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $notebook = $request->user()->notebooks()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Không gian môn học đã được tạo thành công.',
            'data' => $notebook
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $notebook = $request->user()->notebooks()
            ->with(['documents', 'studySets'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $notebook
        ]);
    }

    public function update(Request $request, $id)
    {
        $notebook = $request->user()->notebooks()->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject_code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $notebook->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật môn học thành công.',
            'data' => $notebook
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $notebook = $request->user()->notebooks()->findOrFail($id);

        // Remove vector indexes for associated documents
        foreach ($notebook->documents as $doc) {
            $this->aiBridge->deleteDocumentVectors($doc->id);
            if (file_exists($doc->file_path)) {
                @unlink($doc->file_path);
            }
        }

        $notebook->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa không gian môn học thành công.'
        ]);
    }

    /**
     * Upload document into a notebook and ingest into RAG Vector DB.
     */
    public function uploadDocument(Request $request, $id)
    {
        $notebook = $request->user()->notebooks()->findOrFail($id);

        $request->validate([
            'file' => 'required|file|mimes:pdf,docx,doc,pptx,ppt,txt,md|max:51200', // max 50MB
        ]);

        $uploadedFile = $request->file('file');
        $originalName = $uploadedFile->getClientOriginalName();
        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        $storedName = Str::uuid() . '.' . $extension;

        // Store file in storage/app/public/documents
        $destinationPath = storage_path('app/public/documents');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $uploadedFile->move($destinationPath, $storedName);
        $fullPath = $destinationPath . DIRECTORY_SEPARATOR . $storedName;

        // Create pending document record
        $document = Document::create([
            'notebook_id' => $notebook->id,
            'user_id' => $request->user()->id,
            'file_name' => $originalName,
            'file_path' => $fullPath,
            'file_type' => $extension,
            'total_pages' => 0,
            'rag_status' => 'pending',
        ]);

        // Dispatch RAG Ingest to Python Microservice
        try {
            $ingestResult = $this->aiBridge->ingestDocument(
                filePath: $fullPath,
                fileName: $originalName,
                notebookId: $notebook->id,
                documentId: $document->id,
                userId: $request->user()->id,
                user: $request->user()
            );

            $ingestData = $ingestResult['data'] ?? [];
            $document->update([
                'rag_status' => 'indexed',
                'total_pages' => $ingestData['total_pages'] ?? 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tài liệu đã được tải lên và lập chỉ mục Vector RAG thành công!',
                'data' => $document->fresh(),
            ]);
        } catch (Exception $e) {
            $document->update([
                'rag_status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Tải tệp thành công nhưng lập chỉ mục RAG thất bại: ' . $e->getMessage(),
                'data' => $document->fresh(),
            ], 500);
        }
    }
}
