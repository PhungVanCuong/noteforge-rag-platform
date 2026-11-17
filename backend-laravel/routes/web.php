<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return response()->json([
        'platform' => 'NoteForge AI - RAG Study & Document Conversion Platform',
        'status' => 'online',
        'version' => '1.0.0',
        'api_docs' => '/api/auth/me',
    ]);
});

// Google OAuth 2.0 Web Routes (matching GOOGLE_REDIRECT_URI)
Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect']);
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback']);
