<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $table = 'attachments';

    protected $fillable = [
        'message_id',
        'user_id',
        'original_name',
        'stored_path',
        'mime_type',
        'file_size',
        'type',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get file URL.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->stored_path);
    }

    /**
     * Get download URL.
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('attachments.download', $this->id);
    }

    /**
     * Check if file is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Format file size for display.
     */
    public function getFormattedSizeAttribute(): string
    {
        $size = $this->file_size;
        if ($size < 1024) {
            return $size . ' B';
        }
        if ($size < 1024 * 1024) {
            return round($size / 1024, 2) . ' KB';
        }
        return round($size / (1024 * 1024), 2) . ' MB';
    }
}
