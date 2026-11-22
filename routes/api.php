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
use App\Http\Controllers\SavedReplyController;
use App\Http\Controllers\AgentRatingController;
use App\Http\Controllers\SmartNoteController;
use App\Http\Controllers\OfflineMessageController;
use App\Http\Controllers\RoutingRuleController;

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
    Route::post('/chatbots/smart-reply', [ChatbotController::class, 'smartReply']);
    Route::post('/chatbots/rewrite-message', [ChatbotController::class, 'rewriteMessage']);
    Route::post('/chatbots/translate-message', [ChatbotController::class, 'translateMessage']);
    Route::post('/chatbots/config/llm', [ChatbotController::class, 'getLLMConfig']);
    Route::post('/chatbots/config/llm/update', [ChatbotController::class, 'updateLLMConfig']);
    Route::get('/chatbots/analytics', [ChatbotController::class, 'analytics']);

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

    // Saved Replies (AI-powered canned responses)
    Route::get('/saved-replies', [SavedReplyController::class, 'index']);
    Route::post('/saved-replies', [SavedReplyController::class, 'store']);
    Route::get('/saved-replies/{id}', [SavedReplyController::class, 'show']);
    Route::put('/saved-replies/{id}', [SavedReplyController::class, 'update']);
    Route::delete('/saved-replies/{id}', [SavedReplyController::class, 'destroy']);
    Route::post('/saved-replies/generate', [SavedReplyController::class, 'generate']);
    Route::get('/saved-replies/{id}/use', [SavedReplyController::class, 'use']);

    // Agent Ratings
    Route::get('/agent-ratings', [AgentRatingController::class, 'index']);
    Route::post('/agent-ratings', [AgentRatingController::class, 'store']);
    Route::delete('/agent-ratings/{id}', [AgentRatingController::class, 'destroy']);
    Route::get('/agent-ratings/average', [AgentRatingController::class, 'avgRating']);

    // Smart Notes (AI-generated conversation summaries)
    Route::get('/smart-notes', [SmartNoteController::class, 'index']);
    Route::post('/smart-notes', [SmartNoteController::class, 'store']);
    Route::get('/smart-notes/{id}', [SmartNoteController::class, 'show']);
    Route::delete('/smart-notes/{id}', [SmartNoteController::class, 'destroy']);
    Route::post('/smart-notes/generate', [SmartNoteController::class, 'generate']);

    // Offline Messages (email capture when agents are offline)
    Route::get('/offline-messages', [OfflineMessageController::class, 'index']);
    Route::post('/offline-messages', [OfflineMessageController::class, 'store']);
    Route::get('/offline-messages/{id}', [OfflineMessageController::class, 'show']);
    Route::put('/offline-messages/{id}', [OfflineMessageController::class, 'update']);
    Route::delete('/offline-messages/{id}', [OfflineMessageController::class, 'destroy']);
    Route::post('/offline-messages/{id}/notify', [OfflineMessageController::class, 'sendEmailNotification']);

    // Routing Rules (auto-assign tickets based on conditions)
    Route::get('/routing-rules', [RoutingRuleController::class, 'index']);
    Route::post('/routing-rules', [RoutingRuleController::class, 'store']);
    Route::get('/routing-rules/{id}', [RoutingRuleController::class, 'show']);
    Route::put('/routing-rules/{id}', [RoutingRuleController::class, 'update']);
    Route::delete('/routing-rules/{id}', [RoutingRuleController::class, 'destroy']);
    Route::put('/routing-rules/{id}/toggle', [RoutingRuleController::class, 'activate']);
    Route::post('/routing-rules/test-match', [RoutingRuleController::class, 'testMatch']);

    // Ticket Queue (priority-sorted queue + auto-assign)
    Route::get('/tickets/queue', [TicketController::class, 'queue']);
    Route::post('/tickets/assign-queue', [TicketController::class, 'assignQueue']);
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
