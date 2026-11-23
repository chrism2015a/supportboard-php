<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function index()
    {
        $token = session('auth_token');
        
        if (!$token) {
            return redirect()->route('login');
        }

        $stats = $this->getStats($token);
        $recentTickets = $this->getRecentTickets($token);
        $recentConversations = $this->getRecentConversations($token);

        return view('dashboard.index', compact('stats', 'recentTickets', 'recentConversations'));
    }

    private function getStats($token)
    {
        try {
            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ]);

            $dashboard = $http->get(env('APP_URL') . '/api/dashboard');
            $tickets = $http->get(env('APP_URL') . '/api/tickets');
            $conversations = $http->get(env('APP_URL') . '/api/conversations');

            return [
                'total_tickets' => $tickets->json('total', 0),
                'open_tickets' => collect($tickets->json('data', []))->where('status', 'open')->count(),
                'total_conversations' => $conversations->json('total', 0),
                'unread_messages' => collect($conversations->json('data', []))->sum('unread_count'),
            ];
        } catch (\Exception $e) {
            return [
                'total_tickets' => 0,
                'open_tickets' => 0,
                'total_conversations' => 0,
                'unread_messages' => 0,
            ];
        }
    }

    private function getRecentTickets($token, $limit = 5)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get(env('APP_URL') . '/api/tickets', [
                'limit' => $limit,
                'sort' => 'created_at',
                'order' => 'desc',
            ]);

            return $response->json('data', []);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getRecentConversations($token, $limit = 5)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get(env('APP_URL') . '/api/conversations', [
                'limit' => $limit,
                'sort' => 'updated_at',
                'order' => 'desc',
            ]);

            return $response->json('data', []);
        } catch (\Exception $e) {
            return [];
        }
    }
}
