<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoutingRule extends Model
{
    use HasFactory;

    protected $table = 'routing_rules';

    protected $fillable = [
        'name',
        'conditions',
        'condition_type',
        'department_id',
        'assigned_to',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if this rule matches a given ticket.
     */
    public function matchesTicket(Ticket $ticket): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $conditions = $this->conditions;
        if (empty($conditions)) {
            // No conditions means it matches everything
            return true;
        }

        $operator = $this->condition_type === 'or' ? 'or' : 'and';

        foreach ($conditions as $field => $value) {
            $ticketValue = $ticket->$field ?? null;

            $match = false;
            if (is_string($value) && str_starts_with($value, 'regex:')) {
                $pattern = substr($value, 6);
                $match = (bool) preg_match($pattern, (string) $ticketValue);
            } elseif (is_string($value) && str_starts_with($value, 'contains:')) {
                $searchTerm = substr($value, 9);
                $match = stripos((string) $ticketValue, $searchTerm) !== false;
            } elseif (is_array($value)) {
                $match = in_array($ticketValue, $value);
            } else {
                $match = $ticketValue == $value;
            }

            if ($operator === 'and' && !$match) {
                return false;
            }
            if ($operator === 'or' && $match) {
                return true;
            }
        }

        return $operator === 'and' || $this->conditions === [];
    }

    /**
     * Auto-assign a ticket based on the first matching active rule.
     */
    public static function assignTicket(Ticket $ticket): array
    {
        $rules = self::active()
            ->orderBy('priority', 'asc')
            ->get();

        $matchedRule = null;
        foreach ($rules as $rule) {
            if ($rule->matchesTicket($ticket)) {
                $matchedRule = $rule;
                break;
            }
        }

        if ($matchedRule && $matchedRule->assigned_to) {
            $ticket->agent_id = $matchedRule->assigned_to;
            $ticket->save();

            return [
                'assigned' => true,
                'rule' => $matchedRule,
                'agent_id' => $matchedRule->assigned_to,
            ];
        }

        return [
            'assigned' => false,
            'rule' => null,
            'agent_id' => null,
        ];
    }

    /**
     * Test if any rules would match a ticket.
     */
    public static function testMatch(array $ticketData): array
    {
        $rules = self::active()->orderBy('priority', 'asc')->get();
        $results = [];

        foreach ($rules as $rule) {
            // Create a temporary ticket-like object for matching
            $mockTicket = new class($ticketData) extends Ticket {
                private array $data;
                public function __construct(array $data) { $this->data = $data; }
                public function __get($key) { return $this->data[$key] ?? null; }
            };

            $results[] = [
                'rule' => $rule->name,
                'matches' => $rule->matchesTicket($mockTicket),
                'would_assign_to' => $rule->assigned_agent ? $rule->assigned_agent->name : null,
            ];
        }

        return ['rules' => $results];
    }
}
