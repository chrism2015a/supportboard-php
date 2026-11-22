<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with(['user', 'agent', 'department']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        if ($request->agent_id) {
            $query->where('agent_id', $request->agent_id);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('subject', 'like', "%{$request->search}%")
                  ->orWhere('ticket_number', 'like', "%{$request->search}%");
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($tickets);
    }

    public function show(string $id)
    {
        $ticket = Ticket::with(['user', 'agent', 'department', 'messages.user'])
            ->findOrFail($id);

        return response()->json($ticket);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'priority' => 'sometimes|in:low,normal,high,urgent',
        ]);

        $ticket = Ticket::create([
            'user_id' => $request->user_id,
            'agent_id' => $request->agent_id ?? auth()->id(),
            'department_id' => $request->department_id,
            'subject' => $request->subject,
            'description' => $request->description,
            'category' => $request->category,
            'priority' => $request->priority ?? 'normal',
            'status' => 'open',
        ]);

        return response()->json($ticket, 201);
    }

    public function update(Request $request, string $id)
    {
        $ticket = Ticket::findOrFail($id);

        $request->validate([
            'agent_id' => 'nullable|exists:users,id',
            'status' => 'sometimes|in:open,pending,solved,closed',
            'priority' => 'sometimes|in:low,normal,high,urgent',
        ]);

        $ticket->update($request->only(['agent_id', 'status', 'priority']));

        if ($request->status === 'closed' || $request->status === 'solved') {
            $ticket->update(['closed_at' => now()]);
        }

        return response()->json($ticket);
    }

    public function addMessage(Request $request, string $id)
    {
        $request->validate([
            'content' => 'required|string',
            'is_internal' => 'sometimes|boolean',
        ]);

        $ticket = Ticket::findOrFail($id);

        $ticket->messages()->create([
            'user_id' => auth()->id(),
            'content' => $request->content,
            'is_internal' => $request->is_internal ?? false,
        ]);

        return response()->json(['message' => 'Message added']);
    }

    public function messages(string $id)
    {
        $ticket = Ticket::findOrFail($id);

        return response()->json($ticket->messages()->with('user')->orderBy('created_at', 'asc')->get());
    }

    /**
     * Get ticket queue sorted by priority and age.
     */
    public function queue(Request $request)
    {
        $openTickets = Ticket::where('status', 'open')
            ->with(['user', 'agent', 'department'])
            ->get();

        $urgent = $openTickets->where('priority', 'urgent');
        $high = $openTickets->where('priority', 'high');
        $medium = $openTickets->where('priority', 'normal');
        $low = $openTickets->where('priority', 'low');

        return response()->json([
            'summary' => [
                'urgent' => $urgent->count(),
                'high' => $high->count(),
                'medium' => $medium->count(),
                'low' => $low->count(),
                'total' => $openTickets->count(),
            ],
            'tickets' => $openTickets
                ->sortBy(function ($t) {
                    $priorityOrder = ['urgent' => 0, 'high' => 1, 'normal' => 2, 'low' => 3];
                    return ($priorityOrder[$t->priority] ?? 3) . $t->created_at->timestamp;
                })
                ->values()
                ->take(50),
        ]);
    }

    /**
     * Auto-assign a ticket based on routing rules.
     */
    public function assignQueue(Request $request)
    {
        $validated = $request->validate([
            'ticket_id' => 'required|integer|exists:tickets,id',
        ]);

        $ticket = Ticket::findOrFail($validated['ticket_id']);

        $result = \App\Models\RoutingRule::assignTicket($ticket);

        return response()->json([
            'assigned' => $result['assigned'],
            'ticket' => $ticket,
            'rule' => $result['rule'] ?? null,
            'agent_id' => $result['agent_id'],
        ]);
    }
}
