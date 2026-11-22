<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $query = Conversation::with(['user', 'agent', 'department', 'messages']);

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->agent_id) {
            $query->where('agent_id', $request->agent_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->source) {
            $query->where('source', $request->source);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('subject', 'like', "%{$request->search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%"));
            });
        }

        $conversations = $query->orderBy('last_message_at', 'desc')->paginate(20);

        return response()->json($conversations);
    }

    public function show(Request $request, string $id)
    {
        $conversation = Conversation::with(['user', 'agent', 'department', 'messages.user'])
            ->findOrFail($id);

        return response()->json($conversation);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'source' => 'sometimes|in:chat,email,whatsapp,telegram,messenger',
            'subject' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
        ]);

        $conversation = Conversation::create([
            'user_id' => $request->user_id,
            'agent_id' => $request->agent_id ?? auth()->id(),
            'department_id' => $request->department_id,
            'status' => 'open',
            'source' => $request->source ?? 'chat',
            'subject' => $request->subject,
            'tags' => $request->tags ?? [],
            'started_at' => now(),
            'last_message_at' => now(),
        ]);

        return response()->json($conversation, 201);
    }

    public function update(Request $request, string $id)
    {
        $conversation = Conversation::findOrFail($id);

        $request->validate([
            'agent_id' => 'nullable|exists:users,id',
            'status' => 'sometimes|in:open,closed,pending,archived',
            'tags' => 'nullable|array',
            'subject' => 'nullable|string|max:255',
        ]);

        $conversation->update($request->only(['agent_id', 'status', 'tags', 'subject']));

        return response()->json($conversation);
    }

    public function close(string $id)
    {
        $conversation = Conversation::findOrFail($id);
        $conversation->update(['status' => 'closed', 'closed_at' => now()]);

        return response()->json($conversation);
    }
}
