<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotFlow extends Model
{
    use HasFactory;

    protected $fillable = [
        'chatbot_id',
        'name',
        'description',
        'steps',
        'is_active',
    ];

    protected $casts = [
        'steps' => 'array',
        'is_active' => 'boolean',
    ];

    public function chatbot()
    {
        return $this->belongsTo(ChatbotConfig::class);
    }
}
