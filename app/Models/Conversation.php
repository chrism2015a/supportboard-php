<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'agent_id',
        'department_id',
        'status',
        'source',
        'subject',
        'quick_buttons',
        'tags',
        'custom_data',
        'started_at',
        'closed_at',
        'last_message_at',
        'queue_position',
        'wait_time',
        'language',
    ];

    protected $casts = [
        'tags' => 'array',
        'custom_data' => 'array',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_message_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function isOpen()
    {
        return $this->status === 'open';
    }

    public function isClosed()
    {
        return $this->status === 'closed';
    }
}
