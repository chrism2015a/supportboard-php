<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfflineMessage extends Model
{
    use HasFactory;

    protected $table = 'offline_messages';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'message',
        'subject',
        'department_id',
        'status',
        'assigned_to',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Auto-assign this offline message based on routing rules.
     */
    public function autoAssign(): void
    {
        $assignedRule = RoutingRule::where('is_active', true)
            ->when($this->department_id, function ($q) {
                return $q->where('department_id', $this->department_id);
            })
            ->orderBy('priority', 'asc')
            ->first();

        if ($assignedRule && $assignedRule->assigned_to) {
            $this->assigned_to = $assignedRule->assigned_to;
            $this->status = 'assigned';
            $this->save();
        }
    }
}
