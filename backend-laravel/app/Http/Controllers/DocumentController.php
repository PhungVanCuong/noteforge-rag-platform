<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\AiServiceBridge;
use Illuminate\Http\Request;
use Exception;

class DocumentController extends Controller
{
    protected AiServiceBridge $aiBridge;

    public function __construct(AiServiceBridge $aiBridge)
    {
        $this->aiBridge = $aiBridge;
    }

    public function download(Request $request, $id)
    {
        $doc = $request->user()->documents()->findOrFail($id);

        if (!file_exists($doc->file_path)) {
            return response()->json(['success' => false, 'message' => 'Tệp không tồn tại trên máy chủ.'], 404);
        }

        return response()->download($doc->file_path, $doc->file_name);
    }

    public function destroy(Request $request, $id)
    {
        $doc = $request->user()->documents()->findOrFail($id);

        $this->aiBridge->deleteDocumentVectors($doc->id);

        if (file_exists($doc->file_path)) {
            @unlink($doc->file_path);
        }

        $doc->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa tài liệu và loại bỏ khỏi Vector Index.'
        ]);
    }

    public function reindex(Request $request, $id)
    {
        $doc = $request->user()->documents()->findOrFail($id);

        if (!file_exists($doc->file_path)) {
            return response()->json(['success' => false, 'message' => 'Tệp không tồn tại trên máy chủ.'], 404);
        }

        $doc->update(['rag_status' => 'pending', 'error_message' => null]);

        try {
            $ingestResult = $this->aiBridge->ingestDocument(
                filePath: $doc->file_path,
                fileName: $doc->file_name,
                notebookId: $doc->notebook_id,
                documentId: $doc->id,
                userId: $request->user()->id,
                user: $request->user()
            );

            $ingestData = $ingestResult['data'] ?? [];
            $doc->update([
                'rag_status' => 'indexed',
                'total_pages' => $ingestData['total_pages'] ?? $doc->total_pages,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lập chỉ mục lại thành công.',
                'data' => $doc->fresh(),
            ]);
        } catch (Exception $e) {
            $doc->update([
                'rag_status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lập chỉ mục lại thất bại: ' . $e->getMessage()
            ], 500);
        }
    }
}
