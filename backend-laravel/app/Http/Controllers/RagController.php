<?php

namespace App\Http\Controllers;

use App\Models\Notebook;
use App\Services\AiServiceBridge;
use Illuminate\Http\Request;
use Exception;

class RagController extends Controller
{
    protected AiServiceBridge $aiBridge;

    public function __construct(AiServiceBridge $aiBridge)
    {
        $this->aiBridge = $aiBridge;
    }

    /**
     * Ask question in a notebook context with strict grounding and citation.
     */
    public function chat(Request $request)
    {
        $validated = $request->validate([
            'notebook_id' => 'required|integer',
            'question' => 'required|string|max:2000',
            'top_k' => 'nullable|integer|min:1|max:10',
            'similarity_threshold' => 'nullable|numeric|min:0.1|max:1.0',
        ]);

        $notebook = $request->user()->notebooks()->findOrFail($validated['notebook_id']);

        try {
            $result = $this->aiBridge->chatRag(
                question: $validated['question'],
                notebookId: $notebook->id,
                user: $request->user(),
                topK: $validated['top_k'] ?? 5,
                similarityThreshold: $validated['similarity_threshold'] ?? 0.65
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi trò chuyện với trợ lý RAG: ' . $e->getMessage()
            ], 500);
        }
    }
}
