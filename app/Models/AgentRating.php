<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentRating extends Model
{
    use HasFactory;

    protected $table = 'agent_ratings';

    protected $fillable = [
        'chatbot_conversation_id',
        'user_id',
        'conversation_id',
        'rating',
        'feedback',
        'response',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function chatbotConversation()
    {
        return $this->belongsTo(ChatbotConversation::class, 'chatbot_conversation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByConversationId($query, string $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get average rating statistics.
     */
    public static function getAverageRating(?int $chatbotId = null): array
    {
        $query = self::selectRaw('AVG(rating) as avg_rating, COUNT(*) as total,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1');

        if ($chatbotId) {
            $query->whereHas('chatbotConversation', function ($q) use ($chatbotId) {
                $q->where('chatbot_id', $chatbotId);
            });
        }

        $stats = $query->first();

        return [
            'average' => round($stats->avg_rating ?? 0, 2),
            'total' => (int) $stats->total,
            'distribution' => [
                5 => (int) $stats->rating_5,
                4 => (int) $stats->rating_4,
                3 => (int) $stats->rating_3,
                2 => (int) $stats->rating_2,
                1 => (int) $stats->rating_1,
            ],
        ];
    }
}
