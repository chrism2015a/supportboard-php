<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'Support Board API is running']);
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});

// Installer (public, runs once) - web routes for the HTML frontend
Route::get('/installer', [App\Http\Controllers\InstallerController::class, 'index'])->name('installer.index');
Route::get('/installer/check', [App\Http\Controllers\InstallerController::class, 'check']);
Route::post('/installer/test-db', [App\Http\Controllers\InstallerController::class, 'testDb']);
Route::post('/installer/run', [App\Http\Controllers\InstallerController::class, 'run']);

// Admin Panel Routes

// Auth routes (no middleware)
Route::get('/login', [App\Http\Controllers\Admin\AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [App\Http\Controllers\Admin\AuthController::class, 'login']);
Route::get('/logout', [App\Http\Controllers\Admin\AuthController::class, 'logout'])->name('logout');
Route::get('/auth/token', [App\Http\Controllers\Admin\AuthController::class, 'getToken'])->name('auth.token');

// Protected admin routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Tickets
    Route::get('/tickets', [App\Http\Controllers\Admin\TicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [App\Http\Controllers\Admin\TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{id}', [App\Http\Controllers\Admin\TicketController::class, 'show'])->name('tickets.show');
    Route::put('/tickets/{id}', [App\Http\Controllers\Admin\TicketController::class, 'update'])->name('tickets.update');
    Route::post('/tickets/{id}/messages', [App\Http\Controllers\Admin\TicketController::class, 'addMessage'])->name('tickets.addMessage');

    // Conversations
    Route::get('/conversations', [App\Http\Controllers\Admin\ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/conversations/{id}', [App\Http\Controllers\Admin\ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/conversations/{id}/messages', [App\Http\Controllers\Admin\ConversationController::class, 'storeMessage'])->name('conversations.storeMessage');
    Route::post('/conversations/{id}/close', [App\Http\Controllers\Admin\ConversationController::class, 'close'])->name('conversations.close');

    // Chatbot Configuration
    Route::get('/chatbots', [App\Http\Controllers\Admin\ChatbotController::class, 'index'])->name('chatbots.index');

    // Settings
    Route::get('/settings', [App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
});
