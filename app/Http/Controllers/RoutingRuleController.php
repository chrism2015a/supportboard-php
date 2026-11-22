<?php

namespace App\Http\Controllers;

use App\Models\RoutingRule;
use Illuminate\Http\Request;

class RoutingRuleController extends Controller
{
    /**
     * List routing rules.
     */
    public function index(Request $request)
    {
        $query = RoutingRule::with(['department', 'assignedAgent'])
            ->latest();

        if ($request->filled('active')) {
            $query->where('is_active', true);
        }

        $rules = $query->paginate(20);

        return response()->json($rules);
    }

    /**
     * Create a new routing rule.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'conditions' => 'nullable|array',
            'condition_type' => 'sometimes|in:and,or',
            'department_id' => 'nullable|integer|exists:departments,id',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'priority' => 'sometimes|integer|min:1|max:10',
            'is_active' => 'sometimes|boolean',
        ]);

        $rule = RoutingRule::create($validated);

        return response()->json($rule, 201);
    }

    /**
     * Show a single routing rule.
     */
    public function show(string $id)
    {
        $rule = RoutingRule::with(['department', 'assignedAgent'])->findOrFail($id);

        return response()->json($rule);
    }

    /**
     * Update a routing rule.
     */
    public function update(Request $request, string $id)
    {
        $rule = RoutingRule::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'conditions' => 'nullable|array',
            'condition_type' => 'sometimes|in:and,or',
            'department_id' => 'nullable|integer|exists:departments,id',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'priority' => 'sometimes|integer|min:1|max:10',
            'is_active' => 'sometimes|boolean',
        ]);

        $rule->update($validated);

        return response()->json($rule);
    }

    /**
     * Delete a routing rule.
     */
    public function destroy(string $id)
    {
        $rule = RoutingRule::findOrFail($id);
        $rule->delete();

        return response()->json(['message' => 'Routing rule deleted.']);
    }

    /**
     * Toggle a routing rule's active status.
     */
    public function activate(string $id)
    {
        $rule = RoutingRule::findOrFail($id);
        $rule->update(['is_active' => !$rule->is_active]);

        return response()->json([
            'message' => $rule->is_active ? 'Rule activated.' : 'Rule deactivated.',
            'rule' => $rule,
        ]);
    }

    /**
     * Test if any routing rules match a ticket.
     */
    public function testMatch(Request $request)
    {
        $validated = $request->validate([
            'ticket_id' => 'nullable|integer|exists:tickets,id',
            'status' => 'nullable|string',
            'priority' => 'nullable|string',
            'category' => 'nullable|string',
            'subject' => 'nullable|string',
            'department_id' => 'nullable|integer',
        ]);

        // Build ticket data
        $ticketData = $validated;
        if ($validated['ticket_id']) {
            $ticket = \App\Models\Ticket::find($validated['ticket_id']);
            if ($ticket) {
                $ticketData = array_merge($ticketData, $ticket->toArray());
            }
        }

        $results = RoutingRule::testMatch($ticketData);

        return response()->json($results);
    }
}
