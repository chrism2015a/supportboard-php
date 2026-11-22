<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'slug',
        'category_id',
        'author_id',
        'status',
        'views',
        'likes',
        'helpful',
        'not_helpful',
        'language',
    ];

    protected $casts = [
        'views' => 'integer',
        'likes' => 'integer',
        'helpful' => 'integer',
        'not_helpful' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function isPublished()
    {
        return $this->status === 'published';
    }
}
