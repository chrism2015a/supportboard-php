<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotQnA extends Model
{
    use HasFactory;

    protected $table = 'chatbot_qnas';

    protected $fillable = [
        'chatbot_id',
        'question',
        'answer',
        'category',
        'usage_count',
    ];

    protected $casts = [
        'usage_count' => 'integer',
    ];

    public function chatbot()
    {
        return $this->belongsTo(ChatbotConfig::class);
    }
}
