<?php

namespace App\Services;

use GuzzleException;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected LLMService $llm;

    public function __construct(LLMService $llm)
    {
        $this->llm = $llm;
    }

    /**
     * Generate smart reply suggestions based on conversation context.
     */
    public function smartReply(string $message, string $conversationHistory): array
    {
        $prompt = "You are a customer support assistant. Given the conversation history and the latest customer message, provide 3 different reply options with different tones.

Conversation History:
{$conversationHistory}

Latest Customer Message:
{$message}

Return exactly 3 suggestions in this JSON format:
[
  {\"reply\": \"your first suggestion\", \"tone\": \"professional\"},
  {\"reply\": \"your second suggestion\", \"tone\": \"friendly\"},
  {\"reply\": \"your third suggestion\", \"tone\": \"concise\"}
]

Only return the JSON array, nothing else. Keep replies relevant to the customer's message.";

        $response = $this->llm->chat($prompt);

        if ($response['success']) {
            try {
                $json = json_decode(trim($response['reply']), true);
                if (is_array($json) && isset($json[0]['reply'])) {
                    return $json;
                }
            } catch (\Exception $e) {
                Log::error('AIService smartReply JSON parse error: ' . $e->getMessage());
            }
        }

        return [
            ['reply' => $response['reply'], 'tone' => 'professional'],
            ['reply' => "I understand your concern. Let me help you with that.", 'tone' => 'friendly'],
            ['reply' => "Sure, I can help. What specifically do you need?", 'tone' => 'concise'],
        ];
    }

    /**
     * Rewrite a message with a specific tone.
     */
    public function rewriteMessage(string $message, string $tone): string
    {
        $validTones = ['professional', 'friendly', 'concise', 'empathetic', 'firm'];
        if (!in_array(strtolower($tone), $validTones)) {
            $tone = 'professional';
        }

        $prompt = "You are a professional communication assistant. Rewrite the following message in a {$tone} tone. Keep the meaning and key points the same, but adjust the tone, word choice, and phrasing to match the requested style.

Rules:
- Only return the rewritten message
- Do not add greetings or sign-offs unless the original has them
- Keep it natural and professional
- Do not include any explanation or notes

Message to rewrite:
{$message}";

        $response = $this->llm->chat($prompt);

        if ($response['success']) {
            return trim($response['reply']);
        }

        // Fallback: return original message if LLM unavailable
        return $message;
    }

    /**
     * Translate a message between languages.
     */
    public function translateMessage(string $message, string $sourceLang, string $targetLang): string
    {
        $prompt = "You are a translation assistant. Translate the following message from {$sourceLang} to {$targetLang}.

Rules:
- Only return the translated text
- Do not add any explanation or notes
- Preserve the original tone and meaning

Message to translate:
{$message}";

        $response = $this->llm->chat($prompt);

        if ($response['success']) {
            return trim($response['reply']);
        }

        return $message;
    }

    /**
     * Generate a saved reply based on a prompt.
     */
    public function generateSavedReply(string $prompt): string
    {
        $systemPrompt = "You are a customer support professional. Generate a helpful, clear, and professional reply based on the user's request. The reply should be ready to use as a canned response in a support system.

Rules:
- Be clear and concise
- Use a professional but friendly tone
- Include a greeting and sign-off
- Keep it under 200 words
- Do not include any meta-commentary

Here is the request:";

        $response = $this->llm->chat($prompt, [], [$systemPrompt]);

        if ($response['success']) {
            return trim($response['reply']);
        }

        return "Unable to generate reply at this time.";
    }

    /**
     * Check if the LLM backend is available.
     */
    public function isAvailable(): bool
    {
        return $this->llm->isAvailable();
    }

    /**
     * Get LLM configuration.
     */
    public function getConfig(): array
    {
        return $this->llm->getConfig();
    }
}
