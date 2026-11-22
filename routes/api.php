<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CrawlSourceController;
use App\Http\Controllers\InstallerController;

// Installer (public, runs once)
Route::get('/installer', [InstallerController::class, 'index']);
Route::get('/installer/check', [InstallerController::class, 'check']);
Route::post('/installer/test-db', [InstallerController::class, 'testDb']);
Route::post('/installer/run', [InstallerController::class, 'run']);

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/reports', [DashboardController::class, 'reports']);

    // Conversations
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{id}', [ConversationController::class, 'show']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::put('/conversations/{id}', [ConversationController::class, 'update']);
    Route::post('/conversations/{id}/close', [ConversationController::class, 'close']);

    // Messages
    Route::get('/conversations/{conversationId}/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);

    // Tickets
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::get('/tickets/{id}', [TicketController::class, 'show']);
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::put('/tickets/{id}', [TicketController::class, 'update']);
    Route::get('/tickets/{id}/messages', [TicketController::class, 'messages']);
    Route::post('/tickets/{id}/messages', [TicketController::class, 'addMessage']);

    // Articles (public and protected)
    Route::get('/articles', [ArticleController::class, 'index']);
    Route::get('/articles/slug/{slug}', [ArticleController::class, 'getBySlug']);
    Route::get('/articles/{id}', [ArticleController::class, 'show']);
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::put('/articles/{id}', [ArticleController::class, 'update']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);

    // Departments
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::get('/departments/slug/{slug}', [DepartmentController::class, 'getBySlug']);
    Route::get('/departments/{id}', [DepartmentController::class, 'show']);
    Route::post('/departments', [DepartmentController::class, 'store']);
    Route::put('/departments/{id}', [DepartmentController::class, 'update']);
    Route::delete('/departments/{id}', [DepartmentController::class, 'destroy']);

    // Chatbot
    Route::get('/chatbots', [ChatbotController::class, 'index']);
    Route::post('/chatbots', [ChatbotController::class, 'store']);
    Route::get('/chatbots/qnas', [ChatbotController::class, 'listQnAs']);
    Route::post('/chatbots/qnas', [ChatbotController::class, 'storeQnA']);
    Route::post('/chatbots/chat', [ChatbotController::class, 'chat']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Settings
    Route::get('/settings', [SettingController::class, 'index']);
    Route::get('/settings/{key}', [SettingController::class, 'getByKey']);
    Route::post('/settings', [SettingController::class, 'store']);
    Route::post('/settings/bulk', [SettingController::class, 'bulkUpdate']);

    // Users
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/agents', [UserController::class, 'listAgents']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::put('/users/{id}/password', [UserController::class, 'updatePassword']);
});

// Crawl Sources
Route::get('/crawl-sources', [CrawlSourceController::class, 'index']);
Route::post('/crawl-sources', [CrawlSourceController::class, 'store']);
Route::put('/crawl-sources/{id}', [CrawlSourceController::class, 'update']);
Route::delete('/crawl-sources/{id}', [CrawlSourceController::class, 'destroy']);
Route::post('/crawl-sources/crawl-url', [CrawlSourceController::class, 'crawlUrl']);
Route::post('/crawl-sources/crawl-all', [CrawlSourceController::class, 'crawlSources']);
Route::get('/crawl-sources/{id}/history', [CrawlSourceController::class, 'history']);
Route::get('/crawl-sources/stats', [CrawlSourceController::class, 'stats']);
Route::post('/crawl-sources/search', [CrawlSourceController::class, 'search']);
