<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $token = session('auth_token');
        if (!$token) {
            return redirect()->route('login');
        }

        $status = $request->query('status');
        $priority = $request->query('priority');
        $search = $request->query('search');
        $page = $request->query('page', 1);

        $params = ['limit' => 20, 'sort' => 'created_at', 'order' => 'desc'];
        if ($status) $params['status'] = $status;
        if ($priority) $params['priority'] = $priority;
        if ($search) $params['search'] = $search;
        $params['page'] = $page;

        $tickets = [];
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get(env('APP_URL') . '/api/tickets', $params);
            
            $tickets = $response->json('data', []);
        } catch (\Exception $e) {
            // Return empty if API error
        }

        return view('tickets.index', compact('tickets', 'status', 'priority', 'search'));
    }

    public function store(Request $request)
    {
        $token = session('auth_token');
        
        $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:open,in_progress,waiting_customer,closed',
        ]);

        try {
            Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->post(env('APP_URL') . '/api/tickets', [
                'subject' => $request->subject,
                'description' => $request->description,
                'priority' => $request->priority,
                'status' => $request->status,
            ]);

            return redirect()->route('tickets.index')->with('success', 'Ticket created successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create ticket: ' . $e->getMessage()]);
        }
    }

    public function show($id, Request $request)
    {
        $token = session('auth_token');
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get(env('APP_URL') . '/api/tickets/' . $id);

            $ticket = $response->json('data');
            
            $messagesResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get(env('APP_URL') . '/api/tickets/' . $id . '/messages');
            
            $messages = $messagesResponse->json('data', []);

            return view('tickets.show', compact('ticket', 'messages'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to load ticket: ' . $e->getMessage()]);
        }
    }

    public function update($id, Request $request)
    {
        $token = session('auth_token');
        
        $request->validate([
            'status' => 'required|in:open,in_progress,waiting_customer,closed',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        try {
            Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->put(env('APP_URL') . '/api/tickets/' . $id, [
                'status' => $request->status,
                'priority' => $request->priority,
            ]);

            return redirect()->route('tickets.show', $id)->with('success', 'Ticket updated successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update ticket: ' . $e->getMessage()]);
        }
    }

    public function addMessage($id, Request $request)
    {
        $token = session('auth_token');
        
        $request->validate([
            'message' => 'required|string',
        ]);

        try {
            Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->post(env('APP_URL') . '/api/tickets/' . $id . '/messages', [
                'message' => $request->message,
            ]);

            return redirect()->route('tickets.show', $id)->with('success', 'Message added');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to add message: ' . $e->getMessage()]);
        }
    }
}
