<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, string $conversationId)
    {
        $messages = Message::where('conversation_id', $conversationId)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        Message::where('conversation_id', $conversationId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    public function store(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'sender_id' => 'required|exists:users,id',
            'sender_type' => 'required|in:user,agent,bot,system',
            'content' => 'required|string',
            'type' => 'sometimes|in:text,image,file,voice,emoji,system',
            'attachments' => 'nullable|array',
        ]);

        $message = Message::create([
            'conversation_id' => $request->conversation_id,
            'user_id' => $request->sender_id,
            'sender_type' => $request->sender_type,
            'content' => $request->content,
            'type' => $request->type ?? 'text',
            'attachments' => $request->attachments ?? [],
        ]);

        Conversation::where('id', $request->conversation_id)
            ->update(['last_message_at' => now()]);

        return response()->json($message, 201);
    }
}
