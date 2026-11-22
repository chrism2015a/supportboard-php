<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'chatbot_id',
        'date',
        'total_conversations',
        'resolved_by_bot',
        'transferred_to_human',
        'avg_response_time',
        'satisfaction_score',
    ];

    protected $casts = [
        'date' => 'date',
        'total_conversations' => 'integer',
        'resolved_by_bot' => 'integer',
        'transferred_to_human' => 'integer',
        'avg_response_time' => 'float',
        'satisfaction_score' => 'float',
    ];

    public function chatbot()
    {
        return $this->belongsTo(ChatbotConfig::class);
    }
}
