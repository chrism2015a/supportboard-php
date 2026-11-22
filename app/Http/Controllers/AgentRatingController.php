<?php

namespace App\Http\Controllers;

use App\Models\AgentRating;
use Illuminate\Http\Request;

class AgentRatingController extends Controller
{
    /**
     * List agent ratings with optional filters.
     */
    public function index(Request $request)
    {
        $query = AgentRating::with(['user', 'chatbotConversation.chatbot'])
            ->latest();

        if ($request->filled('chatbot_id')) {
            $query->whereHas('chatbotConversation', function ($q) use ($request) {
                $q->where('chatbot_id', $request->chatbot_id);
            });
        }

        if ($request->filled('conversation_id')) {
            $query->where('conversation_id', $request->conversation_id);
        }

        $ratings = $query->paginate(20);

        return response()->json($ratings);
    }

    /**
     * Store a new agent rating.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'conversation_id' => 'required|string',
            'chatbot_conversation_id' => 'nullable|integer|exists:chatbot_conversations,id',
            'feedback' => 'nullable|string|max:1000',
            'response' => 'nullable|string',
        ]);

        $rating = AgentRating::create($validated);

        return response()->json($rating, 201);
    }

    /**
     * Delete a rating.
     */
    public function destroy(string $id)
    {
        $rating = AgentRating::findOrFail($id);
        $rating->delete();

        return response()->json(['message' => 'Rating deleted.']);
    }

    /**
     * Get average rating statistics.
     */
    public function avgRating(Request $request)
    {
        $validated = $request->validate([
            'chatbot_id' => 'nullable|integer',
        ]);

        $stats = AgentRating::getAverageRating($validated['chatbot_id'] ?? null);

        return response()->json($stats);
    }
}
