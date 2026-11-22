<?php

namespace App\Http\Controllers;

use App\Models\ChatbotConfig;
use App\Models\ChatbotQnA;
use App\Models\ChatbotConversation;
use App\Models\Conversation;
use App\Models\Article;
use App\Models\WebCrawl;
use App\Services\LLMService;
use App\Services\WebCrawler;
use App\Services\AIService;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    protected LLMService $llmService;
    protected WebCrawler $webCrawler;
    protected AIService $aiService;

    public function __construct(LLMService $llmService, WebCrawler $webCrawler, AIService $aiService)
    {
        $this->llmService = $llmService;
        $this->webCrawler = $webCrawler;
        $this->aiService = $aiService;
    }

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
            'llm_endpoint' => 'nullable|url',
            'llm_model' => 'nullable|string',
            'llm_api_key' => 'nullable|string',
            'llm_temperature' => 'nullable|numeric|min:0|max:2',
            'llm_max_tokens' => 'nullable|integer|min:1',
            'llm_system_prompt' => 'nullable|string',
            'use_llm' => 'sometimes|boolean',
            'use_crawled_content' => 'sometimes|boolean',
            'rag_enabled' => 'sometimes|boolean',
        ]);

        $config = ChatbotConfig::create($request->all());
        return response()->json($config, 201);
    }

    public function listQnAs(Request $request)
    {
        $request->validate(['chatbot_id' => 'required|exists:chatbot_configs,id']);
        $qnas = ChatbotQnA::where('chatbot_id', $request->chatbot_id)
            ->orderBy('usage_count', 'desc')->get();
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
     * Chat with the bot - uses smart chain:
     * 1. Q&A matching (instant for known questions)
     * 2. Web crawl search (RAG - search crawled web pages)
     * 3. Knowledge base search (articles)
     * 4. LLM (vLLM/OpenAI) with combined context
     * 5. Human handoff fallback
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
                'source' => 'no_config',
            ]);
        }

        // Source 1: Q&A matching (fastest)
        $qna = ChatbotQnA::where('chatbot_id', $config->id)
            ->whereRaw('LOWER(question) LIKE ?', ['%' . strtolower($message) . '%'])
            ->orderBy('usage_count', 'desc')
            ->first();

        if ($qna) {
            $qna->increment('usage_count');
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

        // Source 2 & 3: RAG - search crawled web pages + knowledge base
        $context = [];

        // Search crawled web content if RAG is enabled
        if (($config->settings['rag_enabled'] ?? $config->rag_enabled ?? false) || ($config->settings['use_crawled_content'] ?? $config->use_crawled_content ?? false)) {
            $webResults = WebCrawl::where('status', 1)
                ->whereNotNull('content')
                ->where(function ($q) use ($message) {
                    $q->where('title', 'like', "%{$message}%")
                      ->orWhere('content', 'like', "%{$message}%");
                })
                ->limit(5)
                ->get(['url', 'title', 'content']);

            foreach ($webResults as $web) {
                $context[] = "[Web Page: {$web->title}]\n[URL: {$web->url}]\n{$this->extractContext($web->content, $message)}";
            }
        }

        // Search knowledge base articles
        $articles = Article::where('status', 'published')
            ->where(function ($q) use ($message) {
                $q->where('title', 'like', "%{$message}%")
                  ->orWhere('content', 'like', "%{$message}%");
            })
            ->limit(3)
            ->get(['title', 'content']);

        foreach ($articles as $article) {
            $context[] = "[Knowledge Base: {$article->title}]\n" . strip_tags($this->extractContext($article->content, $message));
        }

        // Source 4: LLM if configured and has context or is enabled
        $useLLM = $config->settings['use_llm'] ?? $config->use_llm ?? false;
        $llmAvailable = $this->llmService->isAvailable();

        if (($useLLM || !empty($context)) && $llmAvailable) {
            $history = ChatbotConversation::getHistory($sessionId, 10);
            $history[] = ['role' => 'assistant', 'content' => 'How can I help you?'];

            $result = $this->llmService->chat(
                message: $message,
                history: $history,
                context: $context
            );

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
                'context_sources' => count($context),
            ]);
        }

        // Source 5: Final fallback to human
        return response()->json([
            'reply' => $config->fallback_message ?? "I'm not sure I understand. Let me connect you with a human agent.",
            'transferred' => $config->human_takeover_on_fallback ?? true,
            'source' => 'fallback',
        ]);
    }

    public function analytics(Request $request)
    {
        $request->validate(['chatbot_id' => 'required|exists:chatbot_configs,id']);
        $total = ChatbotConversation::where('chatbot_id', $request->chatbot_id)->count();
        $qa = ChatbotConversation::where('chatbot_id', $request->chatbot_id)->where('model', 'qa-match')->count();
        $llm = ChatbotConversation::where('chatbot_id', $request->chatbot_id)->whereNotNull('model')->where('model', '!=', 'qa-match')->count();

        return response()->json([
            'total_conversations' => $total,
            'qa_responses' => $qa,
            'llm_responses' => $llm,
            'qa_percentage' => $total > 0 ? round(($qa / $total) * 100, 2) : 0,
            'llm_percentage' => $total > 0 ? round(($llm / $total) * 100, 2) : 0,
        ]);
    }

    /**
     * AI Smart Reply - suggest 3 reply options based on conversation context.
     */
    public function smartReply(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|integer',
        ]);

        $conversation = Conversation::with('messages.user')->findOrFail($validated['conversation_id']);

        // Build conversation history (last 10 messages)
        $messages = $conversation->messages()->with('user')->latest()->take(10)->get();
        $historyText = '';
        foreach ($messages as $msg) {
            $role = $msg->user && $msg->user->role === 'user' ? 'Customer' : 'Agent';
            $historyText .= "{$role}: {$msg->text}\n";
        }

        // Get the last customer message to suggest a reply for
        $lastCustomerMessage = $messages->reverse()->firstWhere(function ($msg) {
            return $msg->user && ($msg->user->role === 'user' || $msg->user->role === 'customer');
        });

        $customerMessage = $lastCustomerMessage ? $lastCustomerMessage->text : 'Hello, I need help.';

        $suggestions = $this->aiService->smartReply($customerMessage, $historyText);

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * AI Message Rewriting - rewrite a message in a specific tone.
     */
    public function rewriteMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'tone' => 'required|in:professional,friendly,concise,empathetic,firm',
        ]);

        $rewritten = $this->aiService->rewriteMessage($validated['message'], $validated['tone']);

        return response()->json(['rewritten' => $rewritten]);
    }

    /**
     * AI Translation - translate a message between languages.
     */
    public function translateMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'source_lang' => 'nullable|string|min:2',
            'target_lang' => 'required|string|min:2',
        ]);

        $translated = $this->aiService->translateMessage(
            $validated['message'],
            $validated['source_lang'] ?? 'auto',
            $validated['target_lang']
        );

        return response()->json([
            'translated' => $translated,
            'source_lang' => $validated['source_lang'] ?? 'auto',
            'target_lang' => $validated['target_lang'],
        ]);
    }

    protected function extractContext(string $content, string $query, int $window = 200): string
    {
        $pos = stripos($content, $query);
        if ($pos === false) return mb_substr($content, 0, $window);
        $start = max(0, $pos - $window);
        $end = min(mb_strlen($content), $pos + mb_strlen($query) + $window);
        return mb_substr($content, $start, $end - $start);
    }
}
