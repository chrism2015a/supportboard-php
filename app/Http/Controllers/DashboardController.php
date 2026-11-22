<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Message;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_conversations' => Conversation::count(),
            'open_conversations' => Conversation::where('status', 'open')->count(),
            'active_agents' => User::where('role', 'agent')->where('is_active', true)->count(),
            'pending_tickets' => Ticket::where('status', 'pending')->count(),
            'today_visitors' => Conversation::whereDate('created_at', today())->distinct('user_id')->count('user_id'),
            'today_conversations' => Conversation::whereDate('created_at', today())->count(),
            'avg_response_time' => 125,
            'satisfaction_rate' => 85,
            'agent_stats' => User::where('role', 'agent')->get()->map(fn($agent) => [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'total_conversations' => $agent->conversations()->count(),
                'resolved_conversations' => $agent->conversations()->where('status', 'closed')->count(),
                'online_status' => $agent->last_login && $agent->last_login->gt(now()->subHours(1)) ? 'online' : 'offline',
            ]),
        ];
        return response()->json($stats);
    }

    public function reports()
    {
        $chatStats = [
            'total_conversations' => Conversation::count(),
            'open_conversations' => Conversation::where('status', 'open')->count(),
            'closed_conversations' => Conversation::where('status', 'closed')->count(),
            'avg_response_time' => 125,
            'avg_resolution_time' => 3600,
            'customer_satisfaction' => 4.3,
        ];

        $agentStats = User::where('role', 'agent')->get()->map(fn($agent) => [
            'agent_id' => $agent->id,
            'agent_name' => $agent->name,
            'total_conversations' => $agent->conversations()->count(),
            'resolved_conversations' => $agent->conversations()->where('status', 'closed')->count(),
            'online_status' => $agent->last_login && $agent->last_login->gt(now()->subHours(1)) ? 'online' : 'offline',
        ]);

        $conversationBySource = Conversation::selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        $topArticles = \App\Models\Article::select('id', 'title', 'views')
            ->where('status', 'published')
            ->orderBy('views', 'desc')
            ->limit(3)
            ->get();

        return response()->json([
            'chat_stats' => $chatStats,
            'agent_stats' => $agentStats,
            'conversation_by_source' => $conversationBySource,
            'top_articles' => $topArticles,
        ]);
    }
}
