<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowExecution extends Model
{
    use HasFactory;

    protected $table = 'flow_executions';

    protected $fillable = [
        'chatbot_id',
        'flow_id',
        'session_id',
        'conversation_id',
        'current_step',
        'status',
        'variables',
        'error_message',
    ];

    protected $casts = [
        'variables' => 'array',
    ];

    public function chatbot()
    {
        return $this->belongsTo(ChatbotConfig::class);
    }

    public function flow()
    {
        return $this->belongsTo(ChatbotFlow::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Update flow execution state and advance to next step.
     */
    public function advanceStep(string $nextStep, array $data = []): bool
    {
        $this->current_step = $nextStep;
        $this->status = isset($data['error']) ? 'error' : 'running';
        $this->error_message = $data['error'] ?? null;

        if (!empty($data['variables'])) {
            $this->variables = array_merge($this->variables ?? [], $data['variables']);
        }

        return $this->save();
    }

    /**
     * Mark flow as completed.
     */
    public function complete(): void
    {
        $this->status = 'completed';
        $this->save();
    }

    /**
     * Mark flow as abandoned.
     */
    public function abandon(string $reason = ''): void
    {
        $this->status = 'abandoned';
        $this->error_message = $reason ?: null;
        $this->save();
    }
}
