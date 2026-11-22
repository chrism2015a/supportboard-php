<?php

namespace App\Http\Controllers;

use App\Models\SmartNote;
use App\Services\AIService;
use Illuminate\Http\Request;

class SmartNoteController extends Controller
{
    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * List smart notes for a conversation.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|integer',
        ]);

        $notes = SmartNote::where('conversation_id', $validated['conversation_id'])
            ->with('user')
            ->latest()
            ->get();

        return response()->json($notes);
    }

    /**
     * Create a manual smart note.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|integer',
            'summary' => 'required|string',
            'action_items' => 'nullable|array',
            'sentiment' => 'nullable|string',
            'sentiment_score' => 'nullable|string',
        ]);

        $note = SmartNote::create([
            'conversation_id' => $validated['conversation_id'],
            'user_id' => auth()->id(),
            'summary' => $validated['summary'],
            'action_items' => $validated['action_items'] ?? [],
            'sentiment' => $validated['sentiment'] ?? null,
            'sentiment_score' => $validated['sentiment_score'] ?? null,
        ]);

        return response()->json($note, 201);
    }

    /**
     * Show a single smart note.
     */
    public function show(string $id)
    {
        $note = SmartNote::with(['user', 'conversation.messages.user'])->findOrFail($id);

        return response()->json($note);
    }

    /**
     * Delete a smart note.
     */
    public function destroy(string $id)
    {
        $note = SmartNote::findOrFail($id);
        $note->delete();

        return response()->json(['message' => 'Smart note deleted.']);
    }

    /**
     * AI-generate a smart note for a conversation.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|integer',
        ]);

        $note = SmartNote::generate($validated['conversation_id'], $this->aiService);

        return response()->json($note, 201);
    }
}
