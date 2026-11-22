<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedReply extends Model
{
    use HasFactory;

    protected $table = 'saved_replies';

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'category',
        'usage_count',
    ];

    protected $casts = [
        'usage_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function incrementUsage()
    {
        $this->increment('usage_count');
    }
}
