<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $token = session('auth_token');
        if (!$token) {
            return redirect()->route('login');
        }

        $status = $request->query('status');
        $department = $request->query('department');
        $page = $request->query('page', 1);

        $params = ['limit' => 20, 'sort' => 'updated_at', 'order' => 'desc', 'page' => $page];
        if ($status) $params['status'] = $status;
        if ($department) $params['department_id'] = $department;

        $conversations = [];
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get(env('APP_URL') . '/api/conversations', $params);
            
            $conversations = $response->json('data', []);
        } catch (\Exception $e) {
            // Return empty if API error
        }

        return view('conversations.index', compact('conversations', 'status', 'department'));
    }

    public function show($id, Request $request)
    {
        $token = session('auth_token');
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get(env('APP_URL') . '/api/conversations/' . $id);

            $conversation = $response->json('data');
            
            $messagesResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get(env('APP_URL') . '/api/conversations/' . $id . '/messages');
            
            $messages = $messagesResponse->json('data', []);

            return view('conversations.show', compact('conversation', 'messages'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to load conversation: ' . $e->getMessage()]);
        }
    }

    public function storeMessage($id, Request $request)
    {
        $token = session('auth_token');
        
        $request->validate([
            'message' => 'required|string',
        ]);

        try {
            Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->post(env('APP_URL') . '/api/conversations/' . $id . '/messages', [
                'message' => $request->message,
            ]);

            return redirect()->route('conversations.show', $id)->with('success', 'Message sent');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to send message: ' . $e->getMessage()]);
        }
    }

    public function close($id)
    {
        $token = session('auth_token');
        
        try {
            Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->post(env('APP_URL') . '/api/conversations/' . $id . '/close');

            return redirect()->route('conversations.index')->with('success', 'Conversation closed');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to close conversation: ' . $e->getMessage()]);
        }
    }
}
