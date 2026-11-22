<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrawlSource extends Model
{
    use HasFactory;

    protected $table = 'crawl_sources';

    protected $fillable = [
        'chatbot_id',
        'name',
        'base_url',
        'allowed_paths',
        'disallowed_paths',
        'max_pages',
        'max_depth',
        'respect_robots',
        'crawl_sitemap',
        'schedule',
        'is_active',
        'last_crawled_at',
        'total_crawled',
        'failed_count',
    ];

    protected $casts = [
        'allowed_paths' => 'array',
        'disallowed_paths' => 'array',
        'respect_robots' => 'boolean',
        'crawl_sitemap' => 'boolean',
        'is_active' => 'boolean',
        'max_pages' => 'integer',
        'max_depth' => 'integer',
        'total_crawled' => 'integer',
        'failed_count' => 'integer',
        'last_crawled_at' => 'datetime',
    ];

    public function chatbot()
    {
        return $this->belongsTo(ChatbotConfig::class);
    }

    public function webCrawls()
    {
        return $this->hasMany(WebCrawl::class);
    }
}
