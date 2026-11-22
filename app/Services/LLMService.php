<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class LLMService
{
    protected Client $client;
    protected ?string $endpoint;
    protected ?string $model;
    protected ?string $apiKey;
    protected float $temperature;
    protected int $maxTokens;
    protected ?string $systemPrompt;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => env('LLM_ENDPOINT', 'http://localhost:8000/v1'),
            'timeout' => env('LLM_TIMEOUT', 30.0),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->endpoint = env('LLM_ENDPOINT', 'http://localhost:8000/v1');
        $this->model = env('LLM_MODEL', 'gpt-3.5-turbo');
        $this->apiKey = env('LLM_API_KEY', null);
        $this->temperature = (float) env('LLM_TEMPERATURE', 0.7);
        $this->maxTokens = (int) env('LLM_MAX_TOKENS', 1024);
        $this->systemPrompt = env('LLM_SYSTEM_PROMPT', null);
    }

    /**
     * Send a chat message to the LLM and return the response.
     *
     * @param string $message The user's message
     * @param array $history Conversation history as array of ['role' => 'user'|'assistant', 'content' => '...']
     * @param array $context Additional context (e.g., knowledge base snippets)
     * @return array ['reply' => string, 'model' => string, 'usage' => array]
     */
    public function chat(string $message, array $history = [], array $context = []): array
    {
        $messages = [];

        // Add system prompt if configured
        if ($this->systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $this->systemPrompt];
        }

        // Add knowledge base context if provided
        if (!empty($context)) {
            $contextText = "Relevant information:\n" . implode("\n", $context);
            $messages[] = [
                'role' => 'system',
                'content' => $contextText . "\n\nUse the above information to answer the question. If the information doesn't help, rely on your general knowledge."
            ];
        }

        // Add conversation history
        $messages = array_merge($messages, $history);

        // Add current user message
        $messages[] = ['role' => 'user', 'content' => $message];

        return $this->sendRequest($messages);
    }

    /**
     * Generate a reply using the full chat completion API.
     */
    protected function sendRequest(array $messages): array
    {
        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ];

        // Add streaming if configured (disabled by default for simplicity)
        if (env('LLM_STREAM', false)) {
            $payload['stream'] = true;
        }

        // Add API key if configured
        if ($this->apiKey) {
            $this->client->setDefaultOptions([
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
            ]);
        }

        try {
            $response = $this->client->post('/chat/completions', [
                'json' => $payload,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['choices'][0]['message']['content'])) {
                return [
                    'reply' => trim($data['choices'][0]['message']['content']),
                    'model' => $data['model'] ?? $this->model,
                    'usage' => $data['usage'] ?? [],
                    'success' => true,
                ];
            }

            return [
                'reply' => "I'm sorry, I encountered an error processing your request.",
                'success' => false,
                'error' => 'Invalid response from LLM',
            ];
        } catch (GuzzleException $e) {
            Log::error('LLM Service Error: ' . $e->getMessage(), [
                'endpoint' => $this->endpoint,
                'model' => $this->model,
                'error' => $e->getMessage(),
            ]);

            return [
                'reply' => "I'm currently unavailable. Please try again later or contact our support team.",
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if the LLM is available and connected.
     */
    public function isAvailable(): bool
    {
        try {
            // Try a simple ping to the endpoint
            $response = $this->client->get('/models', [
                'timeout' => 5.0,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return isset($data['data']) && is_array($data['data']);
        } catch (GuzzleException $e) {
            return false;
        }
    }

    /**
     * Get available models from the LLM endpoint.
     */
    public function getAvailableModels(): array
    {
        try {
            $response = $this->client->get('/models');
            $data = json_decode($response->getBody()->getContents(), true);

            return collect($data['data'] ?? [])
                ->map(fn($model) => [
                    'id' => $model['id'],
                    'object' => $model['object'],
                    'owned_by' => $model['owned_by'] ?? 'unknown',
                ])
                ->values()
                ->toArray();
        } catch (GuzzleException $e) {
            return [];
        }
    }

    /**
     * Get current configuration.
     */
    public function getConfig(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'model' => $this->model,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'available' => $this->isAvailable(),
        ];
    }

    /**
     * Set configuration dynamically.
     */
    public function setConfig(array $config): void
    {
        if (isset($config['endpoint'])) {
            $this->endpoint = $config['endpoint'];
            $this->client = new Client([
                'base_uri' => $config['endpoint'],
                'timeout' => (float) (env('LLM_TIMEOUT', 30.0)),
                'headers' => ['Content-Type' => 'application/json'],
            ]);
        }

        if (isset($config['model'])) {
            $this->model = $config['model'];
        }

        if (isset($config['api_key'])) {
            $this->apiKey = $config['api_key'];
            if ($this->apiKey) {
                $this->client->setDefaultOptions([
                    'headers' => ['Authorization' => 'Bearer ' . $this->apiKey],
                ]);
            }
        }

        if (isset($config['temperature'])) {
            $this->temperature = (float) $config['temperature'];
        }

        if (isset($config['max_tokens'])) {
            $this->maxTokens = (int) $config['max_tokens'];
        }

        if (isset($config['system_prompt'])) {
            $this->systemPrompt = $config['system_prompt'];
        }
    }
}
