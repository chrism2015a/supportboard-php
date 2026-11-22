<?php

namespace App\Http\Controllers;

use App\Models\ChatbotConfig;
use App\Models\ChatbotQnA;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function index()
    {
        $configs = ChatbotConfig::withCount(['qnas', 'flows'])->get();
        return response()->json($configs);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'sometimes|boolean',
            'welcome_message' => 'nullable|string',
            'fallback_message' => 'nullable|string',
        ]);

        $config = ChatbotConfig::create($request->all());
        return response()->json($config, 201);
    }

    public function listQnAs(Request $request)
    {
        $qnas = ChatbotQnA::where('chatbot_id', $request->chatbot_id)
            ->orderBy('usage_count', 'desc')
            ->get();
        return response()->json($qnas);
    }

    public function storeQnA(Request $request)
    {
        $request->validate([
            'chatbot_id' => 'required|exists:chatbot_configs,id',
            'question' => 'required|string',
            'answer' => 'required|string',
            'category' => 'nullable|string',
        ]);

        $qna = ChatbotQnA::create($request->all());
        return response()->json($qna, 201);
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'conversation_id' => 'required|exists:conversations,id',
            'chatbot_id' => 'nullable|exists:chatbot_configs,id',
        ]);

        $config = ChatbotConfig::where('is_active', true)->first();
        $message = strtolower($request->message);

        $qna = ChatbotQnA::where('chatbot_id', $config?->id)
            ->whereRaw('LOWER(question) LIKE ?', ['%' . $message . '%'])
            ->orderBy('usage_count', 'desc')
            ->first();

        if ($qna) {
            $qna->increment('usage_count');
            return response()->json(['reply' => $qna->answer]);
        }

        return response()->json([
            'reply' => $config?->fallback_message ?? "I'm not sure about that. Let me connect you with an agent.",
            'transferred' => $config?->human_takeover_on_fallback,
        ]);
    }
}
