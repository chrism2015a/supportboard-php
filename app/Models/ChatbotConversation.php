<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotConversation extends Model
{
    use HasFactory;

    protected $table = 'chatbot_conversations';

    protected $fillable = [
        'chatbot_id',
        'conversation_id',
        'session_id',
        'prompt',
        'response',
        'context',
        'model',
        'prompt_tokens',
        'completion_tokens',
    ];

    protected $casts = [
        'context' => 'array',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
    ];

    public function chatbot()
    {
        return $this->belongsTo(ChatbotConfig::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get recent conversation history for a session.
     */
    public static function getHistory(string $sessionId, int $limit = 10): array
    {
        return self::where('session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($conv) => [
                'role' => 'user',
                'content' => $conv->prompt,
            ])->toArray();
    }
}
