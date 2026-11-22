<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'agent_id',
        'department_id',
        'status',
        'priority',
        'subject',
        'description',
        'category',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($ticket) {
            if (!$ticket->ticket_number) {
                $ticket->ticket_number = 'TB-' . str_pad((Ticket::count() + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

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
        return $this->hasMany(TicketMessage::class);
    }

    public function isOpen()
    {
        return $this->status === 'open';
    }

    public function isSolved()
    {
        return $this->status === 'solved';
    }
}
