<?php

namespace App\Http\Controllers;

use App\Models\ChatbotConfig;
use App\Models\ChatbotQnA;
use App\Models\ChatbotConversation;
use App\Models\Article;
use App\Services\LLMService;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    protected LLMService $llmService;

    public function __construct(LLMService $llmService)
    {
        $this->llmService = $llmService;
    }

    /**
     * List all chatbot configurations.
     */
    public function index()
    {
        $configs = ChatbotConfig::withCount(['qnas', 'flows'])->get();
        return response()->json($configs);
    }

    /**
     * Create a new chatbot configuration.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'sometimes|boolean',
            'welcome_message' => 'nullable|string',
            'fallback_message' => 'nullable|string',
            'llm_endpoint' => 'nullable|url',
            'llm_model' => 'nullable|string',
            'llm_api_key' => 'nullable|string',
            'llm_temperature' => 'nullable|numeric|min:0|max:2',
            'llm_max_tokens' => 'nullable|integer|min:1',
            'llm_system_prompt' => 'nullable|string',
            'use_llm' => 'sometimes|boolean',
        ]);

        $config = ChatbotConfig::create($request->all());
        return response()->json($config, 201);
    }

    /**
     * List Q&A pairs for a chatbot.
     */
    public function listQnAs(Request $request)
    {
        $request->validate([
            'chatbot_id' => 'required|exists:chatbot_configs,id',
        ]);

        $qnas = ChatbotQnA::where('chatbot_id', $request->chatbot_id)
            ->orderBy('usage_count', 'desc')
            ->get();

        return response()->json($qnas);
    }

    /**
     * Create a new Q&A pair.
     */
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

    /**
     * Get LLM configuration.
     */
    public function getLLMConfig()
    {
        $config = $this->llmService->getConfig();
        $available = $this->llmService->isAvailable();

        return response()->json([
            'config' => $config,
            'available' => $available,
            'models' => $available ? $this->llmService->getAvailableModels() : [],
        ]);
    }

    /**
     * Update LLM configuration.
     */
    public function updateLLMConfig(Request $request)
    {
        $request->validate([
            'endpoint' => 'nullable|url',
            'model' => 'nullable|string',
            'api_key' => 'nullable|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1',
            'system_prompt' => 'nullable|string',
        ]);

        $this->llmService->setConfig($request->all());

        return response()->json([
            'message' => 'LLM configuration updated',
            'config' => $this->llmService->getConfig(),
        ]);
    }

    /**
     * Chat with the bot - uses LLM if configured, falls back to Q&A matching.
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'conversation_id' => 'required|exists:conversations,id',
            'chatbot_id' => 'nullable|exists:chatbot_configs,id',
            'session_id' => 'nullable|string',
        ]);

        $message = $request->message;
        $sessionId = $request->session_id ?? session()->getId() ?? 'anonymous';
        $chatbotConfig = ChatbotConfig::where('is_active', true);

        if ($request->chatbot_id) {
            $chatbotConfig = $chatbotConfig->where('id', $request->chatbot_id);
        }

        $config = $chatbotConfig->first();

        if (!$config) {
            return response()->json([
                'reply' => "I'm not sure I understand. Let me connect you with a human agent.",
                'transferred' => true,
                'source' => 'fallback',
            ]);
        }

        // Get conversation history for context
        $history = ChatbotConversation::getHistory($sessionId, 10);
        $history[] = ['role' => 'assistant', 'content' => 'How can I help you?'];

        // Try Q&A matching first (for quick responses on known questions)
        $messageLower = strtolower($message);
        $qna = ChatbotQnA::where('chatbot_id', $config->id)
            ->whereRaw('LOWER(question) LIKE ?', ['%' . $messageLower . '%'])
            ->orderBy('usage_count', 'desc')
            ->first();

        if ($qna) {
            $qna->increment('usage_count');

            // Log to conversation history
            ChatbotConversation::create([
                'chatbot_id' => $config->id,
                'conversation_id' => $request->conversation_id,
                'session_id' => $sessionId,
                'prompt' => $message,
                'response' => $qna->answer,
                'model' => 'qa-match',
            ]);

            return response()->json([
                'reply' => $qna->answer,
                'transferred' => false,
                'source' => 'qna',
            ]);
        }

        // Fall back to LLM if configured and available
        $useLLM = $config->settings['use_llm'] ?? $config->use_llm ?? false;
        $llmAvailable = $this->llmService->isAvailable();

        if ($useLLM && $llmAvailable) {
            // Search knowledge base for relevant context
            $context = $this->searchKnowledgeBase($message);

            $result = $this->llmService->chat(
                message: $message,
                history: $history,
                context: $context
            );

            // Log to conversation history
            ChatbotConversation::create([
                'chatbot_id' => $config->id,
                'conversation_id' => $request->conversation_id,
                'session_id' => $sessionId,
                'prompt' => $message,
                'response' => $result['reply'],
                'context' => $context,
                'model' => $result['model'] ?? null,
                'prompt_tokens' => $result['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $result['usage']['completion_tokens'] ?? 0,
            ]);

            return response()->json([
                'reply' => $result['reply'],
                'transferred' => !$result['success'],
                'source' => 'llm',
                'model' => $result['model'] ?? null,
            ]);
        }

        // Final fallback
        return response()->json([
            'reply' => $config->fallback_message ?? "I'm not sure I understand. Let me connect you with a human agent.",
            'transferred' => $config->human_takeover_on_fallback ?? true,
            'source' => 'fallback',
        ]);
    }

    /**
     * Search knowledge base for relevant context.
     */
    protected function searchKnowledgeBase(string $query): array
    {
        $articles = Article::where('status', 'published')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get(['title', 'content']);

        return $articles->map(fn($article) =>
            "Title: {$article->title}\nContent: " . strip_tags($article->content)
        )->toArray();
    }

    /**
     * Get chatbot analytics.
     */
    public function analytics(Request $request)
    {
        $request->validate([
            'chatbot_id' => 'required|exists:chatbot_configs,id',
        ]);

        $totalConversations = ChatbotConversation::where('chatbot_id', $request->chatbot_id)->count();
        $qaResponses = ChatbotConversation::where('chatbot_id', $request->chatbot_id)
            ->where('model', 'qa-match')
            ->count();
        $llmResponses = ChatbotConversation::where('chatbot_id', $request->chatbot_id)
            ->whereNotNull('model')
            ->where('model', '!=', 'qa-match')
            ->count();

        return response()->json([
            'total_conversations' => $totalConversations,
            'qa_responses' => $qaResponses,
            'llm_responses' => $llmResponses,
            'qa_percentage' => $totalConversations > 0 ? round(($qaResponses / $totalConversations) * 100, 2) : 0,
            'llm_percentage' => $totalConversations > 0 ? round(($llmResponses / $totalConversations) * 100, 2) : 0,
        ]);
    }
}
