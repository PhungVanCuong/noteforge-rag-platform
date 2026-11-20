<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotebookController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\RagController;
use App\Http\Controllers\ConverterController;
use App\Http\Controllers\StudySetController;

/*
|--------------------------------------------------------------------------
| API Routes - NoteForge AI Platform
|--------------------------------------------------------------------------
*/

// Public Authentication & OTP
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/login', [AuthController::class, 'login']);

    // Google OAuth 2.0
    Route::get('/google/redirect', [AuthController::class, 'googleRedirect']);
    Route::get('/google/callback', [AuthController::class, 'googleCallback']);
});

// Public Study Set Access & Standalone Offline HTML Export
Route::get('/study-sets/public/{slug}', [StudySetController::class, 'show']);
Route::get('/study-sets/{idOrSlug}/export-html', [StudySetController::class, 'exportOfflineHtml']);

// Public Document Stream & Download (with Token support in query for iframes)
Route::get('/documents/{id}/download', [DocumentController::class, 'download']);

// Advanced Text-to-Speech (TTS) Studio Audio Stream & Synthesis
Route::post('/tts/synthesize', [ConverterController::class, 'synthesizeTts']);
Route::get('/tts/audio/{fileName}', [ConverterController::class, 'getTtsAudio']);
Route::get('/tts/voices', [ConverterController::class, 'getTtsVoices']);
Route::post('/extract-text', [ConverterController::class, 'extractText']);

// Protected Routes (Sanctum Authenticated)
Route::middleware('auth:sanctum')->group(function () {

    // User Profile, Security & Gemini Key
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::prefix('user')->group(function () {
        Route::get('/profile', [AuthController::class, 'me']);
        Route::match(['put', 'post'], '/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
        Route::put('/gemini-key', [AuthController::class, 'updateGeminiKey']);
        Route::post('/gemini-key/test', [AuthController::class, 'testGeminiKey']);
    });

    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::match(['put', 'post'], '/profile', [AuthController::class, 'updateProfile']);
        Route::post('/gemini-key', [AuthController::class, 'updateGeminiKey']);
        Route::put('/gemini-key', [AuthController::class, 'updateGeminiKey']);
        Route::post('/gemini-key/test', [AuthController::class, 'testGeminiKey']);
    });

    // Notebooks (Workspaces)
    Route::prefix('notebooks')->group(function () {
        Route::get('/', [NotebookController::class, 'index']);
        Route::post('/', [NotebookController::class, 'store']);
        Route::get('/{id}', [NotebookController::class, 'show']);
        Route::put('/{id}', [NotebookController::class, 'update']);
        Route::delete('/{id}', [NotebookController::class, 'destroy']);
        Route::post('/{id}/upload', [NotebookController::class, 'uploadDocument']);
    });

    // Documents
    Route::prefix('documents')->group(function () {
        Route::delete('/{id}', [DocumentController::class, 'destroy']);
        Route::post('/{id}/reindex', [DocumentController::class, 'reindex']);
    });

    // RAG Assistant
    Route::post('/rag/chat', [RagController::class, 'chat']);

    // Universal Converter Hub
    Route::prefix('converters')->group(function () {
        Route::get('/jobs', [ConverterController::class, 'index']);
        Route::post('/pdf-to-word', [ConverterController::class, 'pdfToWord']);
        Route::post('/word-to-pdf', [ConverterController::class, 'wordToPdf']);
        Route::post('/merge-pdf', [ConverterController::class, 'mergePdf']);
        Route::post('/split-pdf', [ConverterController::class, 'splitPdf']);
        Route::post('/image', [ConverterController::class, 'convertImage']);
        Route::post('/images-to-pdf', [ConverterController::class, 'imagesToPdf']);
        Route::post('/transcribe', [ConverterController::class, 'transcribeLecture']);
    });

    // Study Sets & Interactive Modes
    Route::prefix('study-sets')->group(function () {
        Route::get('/', [StudySetController::class, 'index']);
        Route::post('/generate', [StudySetController::class, 'generate']);
        Route::get('/{id}/status', [StudySetController::class, 'status']);
        Route::get('/{id}', [StudySetController::class, 'show']);
        Route::post('/{id}/progress', [StudySetController::class, 'updateProgress']);
        Route::get('/{id}/progress', [StudySetController::class, 'getProgress']);
        Route::post('/{id}/exam-attempt', [StudySetController::class, 'submitExamAttempt']);
        Route::get('/{id}/attempts', [StudySetController::class, 'listAttempts']);
        Route::put('/{id}', [StudySetController::class, 'update']);
        Route::delete('/{id}', [StudySetController::class, 'destroy']);
    });
});

// Internal Webhooks (Callable by Python AI Microservice on localhost)
Route::post('/internal/study-sets/{id}/progress', [StudySetController::class, 'updateProgressInternal']);

