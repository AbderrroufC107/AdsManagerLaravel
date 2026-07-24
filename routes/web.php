<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;

Route::get('/', fn() => redirect('/login'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/setup', [AuthController::class, 'setup']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout']);

Route::get('/profiles', [ProfileController::class, 'index']);
Route::get('/profiles/create', [ProfileController::class, 'create']);
Route::post('/profiles', [ProfileController::class, 'store']);
Route::post('/profiles/{id}/select', [ProfileController::class, 'select']);
Route::delete('/profiles/{id}', [ProfileController::class, 'destroy']);

Route::middleware('web')->group(function () {
    Route::get('/console', fn() => view('console'));
    Route::get('/audit', fn() => view('audit'));
});

Route::post('/api/credentials', [AuthController::class, 'saveCredentials']);
Route::get('/api/credentials', [AuthController::class, 'getCredentialsStatus']);
Route::post('/api/chat', [ChatController::class, 'chat']);
Route::post('/api/approve', [ChatController::class, 'approve']);
Route::get('/api/status', [ChatController::class, 'status']);
Route::get('/api/audit', [ChatController::class, 'audit']);
Route::get('/api/pending', [ChatController::class, 'pending']);
Route::get('/api/conversations', [ChatController::class, 'conversations']);
Route::post('/api/conversations', [ChatController::class, 'getMessages']);
Route::get('/api/health', [ChatController::class, 'health']);
