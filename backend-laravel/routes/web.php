<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'platform' => 'NoteForge AI - RAG Study & Document Conversion Platform',
        'status' => 'online',
        'version' => '1.0.0',
        'api_docs' => '/api/auth/me',
    ]);
});
