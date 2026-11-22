<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'default_message',
        'welcome_message',
        'fallback_message',
        'human_takeover_on_fallback',
        'auto_translate',
        'speech_recognition',
        'image_analysis',
        'web_search',
        'conversation_history',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'human_takeover_on_fallback' => 'boolean',
        'auto_translate' => 'boolean',
        'speech_recognition' => 'boolean',
        'image_analysis' => 'boolean',
        'web_search' => 'boolean',
        'conversation_history' => 'boolean',
        'settings' => 'array',
    ];

    public function qnas()
    {
        return $this->hasMany(ChatbotQnA::class);
    }

    public function flows()
    {
        return $this->hasMany(ChatbotFlow::class);
    }

    public function analytics()
    {
        return $this->hasMany(ChatbotAnalytics::class);
    }
}
