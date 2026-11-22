<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebCrawl extends Model
{
    use HasFactory;

    protected $table = 'web_crawls';

    protected $fillable = [
        'chatbot_id',
        'url',
        'title',
        'content',
        'domain',
        'crawl_type',
        'status',
        'error_message',
        'word_count',
        'chunk_count',
        'metadata',
        'last_crawled_at',
        'next_crawl_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_crawled_at' => 'datetime',
        'next_crawl_at' => 'datetime',
        'word_count' => 'integer',
        'chunk_count' => 'integer',
        'status' => 'integer',
    ];

    public function chatbot()
    {
        return $this->belongsTo(ChatbotConfig::class);
    }

    public function chunks()
    {
        return $this->hasMany(WebCrawlChunk::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    public function scopeByDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }
}
