<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AiServiceBridge
{
    protected string $baseUrl;
    protected string $systemApiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.ai_service.url', env('AI_SERVICE_URL', 'http://127.0.0.1:8001')), '/');
        $this->systemApiKey = env('GEMINI_API_KEY', env('SYSTEM_GEMINI_API_KEY', ''));
    }

    public function resolveApiKey(?User $user): ?string
    {
        if ($user && $user->has_custom_key) {
            $userKey = $user->getDecryptedGeminiKey();
            if (!empty($userKey)) {
                return $userKey;
            }
        }
        return !empty($this->systemApiKey) ? $this->systemApiKey : config('services.gemini.key', env('GEMINI_API_KEY'));
    }

    public function ingestDocument(
        string $filePath,
        string $fileName,
        int $notebookId,
        int $documentId,
        int $userId,
        ?User $user = null
    ): array {
        $apiKey = $this->resolveApiKey($user);

        $headers = [];
        if ($apiKey) {
            $headers['X-Gemini-Api-Key'] = $apiKey;
        }

        $payload = [
            'file_path' => $filePath,
            'file_name' => $fileName,
            'notebook_id' => $notebookId,
            'document_id' => $documentId,
            'user_id' => $userId,
            'api_key' => $apiKey,
        ];

        $response = Http::withHeaders($headers)->timeout(180)->post("{$this->baseUrl}/api/rag/ingest", $payload);

        // Fallback to system key if user key failed
        if ($response->failed() && !empty($user) && $apiKey !== $this->systemApiKey && !empty($this->systemApiKey)) {
            Log::warning('AI Bridge Ingest failed with user custom key, falling back to system key...');
            $payload['api_key'] = $this->systemApiKey;
            $response = Http::withHeaders(['X-Gemini-Api-Key' => $this->systemApiKey])->timeout(180)->post("{$this->baseUrl}/api/rag/ingest", $payload);
        }

        if ($response->failed()) {
            Log::error('AI Bridge Ingest Failed', ['response' => $response->body()]);
            throw new Exception("AI Ingestion failed: " . ($response->json('detail') ?? $response->body()));
        }

        return $response->json();
    }

    public function chatRag(
        string $question,
        int $notebookId,
        ?User $user = null,
        int $topK = 5,
        float $similarityThreshold = 0.65
    ): array {
        $apiKey = $this->resolveApiKey($user);

        $headers = [];
        if ($apiKey) {
            $headers['X-Gemini-Api-Key'] = $apiKey;
        }

        $payload = [
            'question' => $question,
            'notebook_id' => $notebookId,
            'api_key' => $apiKey,
            'top_k' => $topK,
            'similarity_threshold' => $similarityThreshold,
        ];

        $response = Http::withHeaders($headers)->timeout(60)->post("{$this->baseUrl}/api/rag/chat", $payload);

        // Fallback to system key if user key failed
        if ($response->failed() && !empty($user) && $apiKey !== $this->systemApiKey && !empty($this->systemApiKey)) {
            Log::warning('AI Bridge Chat failed with user custom key, falling back to system key...');
            $payload['api_key'] = $this->systemApiKey;
            $response = Http::withHeaders(['X-Gemini-Api-Key' => $this->systemApiKey])->timeout(60)->post("{$this->baseUrl}/api/rag/chat", $payload);
        }

        if ($response->failed()) {
            Log::error('AI Bridge Chat Failed', ['response' => $response->body()]);
            throw new Exception("RAG Chat failed: " . ($response->json('detail') ?? $response->body()));
        }

        return $response->json()['data'] ?? [];
    }

    public function deleteDocumentVectors(int $documentId): bool
    {
        try {
            $response = Http::timeout(30)->delete("{$this->baseUrl}/api/rag/document/{$documentId}");
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('AI Bridge Delete Document Vectors Error: ' . $e->getMessage());
            return false;
        }
    }

    public function generateQuiz(
        ?string $filePath = null,
        ?string $rawText = null,
        ?User $user = null,
        ?int $studySetId = null,
        ?string $callbackUrl = null
    ): array {
        $apiKey = $this->resolveApiKey($user);

        $headers = [];
        if ($apiKey) {
            $headers['X-Gemini-Api-Key'] = $apiKey;
        }

        $payload = [
            'file_path' => $filePath,
            'raw_text' => $rawText,
            'api_key' => $apiKey,
            'study_set_id' => $studySetId,
            'callback_url' => $callbackUrl,
        ];

        // 1800s (30 minutes) timeout to eliminate Guzzle execution timeout for heavy documents
        $response = Http::withHeaders($headers)
            ->connectTimeout(30)
            ->timeout(1800)
            ->post("{$this->baseUrl}/api/quiz/generate", $payload);

        // Fallback to system key if user key failed
        if ($response->failed() && !empty($user) && $apiKey !== $this->systemApiKey && !empty($this->systemApiKey)) {
            Log::warning('AI Bridge Generate Quiz failed with user custom key, falling back to system key...');
            $payload['api_key'] = $this->systemApiKey;
            $response = Http::withHeaders(['X-Gemini-Api-Key' => $this->systemApiKey])
                ->connectTimeout(30)
                ->timeout(1800)
                ->post("{$this->baseUrl}/api/quiz/generate", $payload);
        }

        if ($response->failed()) {
            Log::error('AI Bridge Quiz Generation Failed', ['response' => $response->body()]);
            throw new Exception("Quiz generation failed: " . ($response->json('detail') ?? $response->body()));
        }

        return $response->json()['data'] ?? [];
    }

    public function convertPdfToWord(string $inputPath): array
    {
        $response = Http::asForm()->timeout(120)->post("{$this->baseUrl}/api/convert/pdf-to-word", [
            'input_file' => $inputPath,
        ]);

        if ($response->failed()) {
            throw new Exception("PDF to Word conversion failed: " . ($response->json('detail') ?? $response->body()));
        }

        return $response->json();
    }

    public function convertWordToPdf(string $inputPath): array
    {
        $response = Http::asForm()->timeout(120)->post("{$this->baseUrl}/api/convert/word-to-pdf", [
            'input_file' => $inputPath,
        ]);

        if ($response->failed()) {
            throw new Exception("Word to PDF conversion failed: " . ($response->json('detail') ?? $response->body()));
        }

        return $response->json();
    }

    public function mergePdfs(array $inputFiles): array
    {
        $response = Http::timeout(120)->post("{$this->baseUrl}/api/convert/merge-pdf", [
            'input_files' => $inputFiles,
        ]);

        if ($response->failed()) {
            throw new Exception("Merge PDF failed: " . ($response->json('detail') ?? $response->body()));
        }

        return $response->json();
    }

    public function splitPdf(string $inputFile, string $pageRanges): array
    {
        $response = Http::timeout(120)->post("{$this->baseUrl}/api/convert/split-pdf", [
            'input_file' => $inputFile,
            'page_ranges' => $pageRanges,
        ]);

        if ($response->failed()) {
            throw new Exception("Split PDF failed: " . ($response->json('detail') ?? $response->body()));
        }

        return $response->json();
    }

    public function convertImage(string $inputFile, string $format = 'PNG', int $quality = 90): array
    {
        $response = Http::timeout(60)->post("{$this->baseUrl}/api/convert/image", [
            'input_file' => $inputFile,
            'output_format' => $format,
            'quality' => $quality,
        ]);

        if ($response->failed()) {
            throw new Exception("Image conversion failed: " . ($response->json('detail') ?? $response->body()));
        }

        return $response->json();
    }

    public function imagesToPdf(array $inputFiles): array
    {
        $response = Http::timeout(120)->post("{$this->baseUrl}/api/convert/images-to-pdf", [
            'input_files' => $inputFiles,
        ]);

        if ($response->failed()) {
            throw new Exception("Images to PDF failed: " . ($response->json('detail') ?? $response->body()));
        }

        return $response->json();
    }

    public function transcribeLecture(string $mediaPath, ?User $user = null): array
    {
        $apiKey = $this->resolveApiKey($user);

        $headers = [];
        if ($apiKey) {
            $headers['X-Gemini-Api-Key'] = $apiKey;
        }

        $response = Http::withHeaders($headers)->timeout(300)->post("{$this->baseUrl}/api/convert/transcribe-lecture", [
            'media_path' => $mediaPath,
            'api_key' => $apiKey,
        ]);

        if ($response->failed()) {
            throw new Exception("Lecture transcription failed: " . ($response->json('detail') ?? $response->body()));
        }

        return $response->json();
    }

    public function synthesizeTts(string $text, string $lang = 'auto', ?string $voice = null, float $rate = 1.0): array
    {
        $response = Http::timeout(180)->post("{$this->baseUrl}/api/tts/synthesize", [
            'text' => $text,
            'lang' => $lang,
            'voice' => $voice,
            'rate' => $rate,
        ]);

        if ($response->failed()) {
            throw new Exception("TTS Synthesis failed: " . ($response->json('detail') ?? $response->body()));
        }

        return $response->json('data') ?? [];
    }

    public function getTtsAudioStream(string $fileName)
    {
        return Http::timeout(60)->get("{$this->baseUrl}/api/tts/audio/{$fileName}");
    }

    public function extractTextFromFile(string $filePath): string
    {
        $response = Http::timeout(120)->attach(
            'file', file_get_contents($filePath), basename($filePath)
        )->post("{$this->baseUrl}/api/extract-text");

        if ($response->failed()) {
            throw new Exception("Trích xuất văn bản thất bại: " . ($response->json('detail') ?? $response->body()));
        }

        return $response->json('text') ?? '';
    }
}
