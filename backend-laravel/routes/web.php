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

// Fallback Named Route for unauthenticated API/Web calls to prevent RouteNotFoundException
Route::get('/login', function () {
    return response()->json([
        'success' => false,
        'message' => 'Unauthenticated. Vui lòng đăng nhập để tiếp tục.'
    ], 401);
})->name('login');

// Google OAuth 2.0 Web Routes (matching GOOGLE_REDIRECT_URI)
Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect']);
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback']);
