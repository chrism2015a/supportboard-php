<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebCrawlChunk extends Model
{
    use HasFactory;

    protected $table = 'web_crawl_chunks';

    protected $fillable = [
        'web_crawl_id',
        'content',
        'chunk_index',
        'word_count',
        'source_url',
        'embedding',
    ];

    protected $casts = [
        'embedding' => 'array',
        'chunk_index' => 'integer',
        'word_count' => 'integer',
    ];

    public function webCrawl()
    {
        return $this->belongsTo(WebCrawl::class);
    }
}
