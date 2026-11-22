<?php

namespace App\Http\Controllers;

use App\Models\SavedReply;
use App\Services\AIService;
use Illuminate\Http\Request;

class SavedReplyController extends Controller
{
    protected AIService $ai;

    public function __construct(AIService $ai)
    {
        $this->ai = $ai;
    }

    /**
     * List saved replies with optional filters.
     */
    public function index(Request $request)
    {
        $query = SavedReply::with('user')->latest();

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('chatbot_id')) {
            $query->where('chatbot_id', $request->chatbot_id);
        }

        $replies = $query->paginate(20);

        return response()->json($replies);
    }

    /**
     * Create a new saved reply.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'nullable|string|max:100',
            'chatbot_id' => 'nullable|integer',
        ]);

        $reply = SavedReply::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'content' => $validated['content'],
            'category' => $validated['category'] ?? 'general',
            'chatbot_id' => $validated['chatbot_id'] ?? null,
        ]);

        return response()->json($reply, 201);
    }

    /**
     * Show a single saved reply.
     */
    public function show(string $id)
    {
        $reply = SavedReply::with('user')->findOrFail($id);

        return response()->json($reply);
    }

    /**
     * Update a saved reply.
     */
    public function update(Request $request, string $id)
    {
        $reply = SavedReply::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'category' => 'nullable|string|max:100',
        ]);

        $reply->update($validated);

        return response()->json($reply);
    }

    /**
     * Delete a saved reply.
     */
    public function destroy(string $id)
    {
        $reply = SavedReply::findOrFail($id);
        $reply->delete();

        return response()->json(['message' => 'Saved reply deleted.']);
    }

    /**
     * AI-generated saved reply.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'required|string|min:5',
            'category' => 'nullable|string|max:100',
        ]);

        $content = $this->ai->generateSavedReply($validated['prompt']);

        $reply = SavedReply::create([
            'user_id' => auth()->id(),
            'title' => 'AI Generated: ' . substr($validated['prompt'], 0, 50),
            'content' => $content,
            'category' => $validated['category'] ?? 'ai-generated',
        ]);

        return response()->json($reply, 201);
    }

    /**
     * Mark a reply as used (increment usage count).
     */
    public function use(string $id)
    {
        $reply = SavedReply::findOrFail($id);
        $reply->incrementUsage();

        return response()->json([
            'reply' => $reply,
            'message' => 'Reply usage tracked.',
        ]);
    }
}
