<?php

namespace App\Http\Controllers;

use App\Models\OfflineMessage;
use App\Models\RoutingRule;
use Illuminate\Http\Request;

class OfflineMessageController extends Controller
{
    /**
     * List offline messages with optional status filter.
     */
    public function index(Request $request)
    {
        $query = OfflineMessage::with(['department', 'assignedUser'])
            ->latest();

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('department_id')) {
            $query->byDepartment($request->department_id);
        }

        $messages = $query->paginate(20);

        return response()->json($messages);
    }

    /**
     * Store a new offline message.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string',
            'subject' => 'nullable|string|max:255',
            'department_id' => 'nullable|integer|exists:departments,id',
        ]);

        $message = OfflineMessage::create($validated);

        // Auto-assign based on routing rules
        if ($message->department_id) {
            $message->autoAssign();
        }

        return response()->json($message, 201);
    }

    /**
     * Show a single offline message.
     */
    public function show(string $id)
    {
        $message = OfflineMessage::with(['department', 'assignedUser'])->findOrFail($id);

        return response()->json($message);
    }

    /**
     * Update an offline message.
     */
    public function update(Request $request, string $id)
    {
        $message = OfflineMessage::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:new,assigned,in_progress,resolved',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'message' => 'sometimes|string',
        ]);

        $message->update($validated);

        return response()->json($message);
    }

    /**
     * Delete an offline message.
     */
    public function destroy(string $id)
    {
        $message = OfflineMessage::findOrFail($id);
        $message->delete();

        return response()->json(['message' => 'Offline message deleted.']);
    }

    /**
     * Send email notification for an offline message.
     */
    public function sendEmailNotification(OfflineMessage $message)
    {
        // Try to send via Laravel's notification system
        try {
            $agents = null;
            if ($message->department_id) {
                $agents = \App\Models\DepartmentUser::where('department_id', $message->department_id)
                    ->with('user')
                    ->pluck('user');
            }

            if ($message->assigned_to) {
                $agents = $message->assignedUser ? collect([$message->assignedUser]) : collect();
            }

            if ($agents->isNotEmpty()) {
                foreach ($agents as $agent) {
                    try {
                        \Illuminate\Support\Facades\Notification::send($agent, new \App\Notifications\OfflineMessageReceived($message));
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::warning('Failed to send notification: ' . $e->getMessage());
                    }
                }
            }

            // Fallback: log it if mail isn't configured
            if (!config('mail.default')) {
                \Illuminate\Support\Facades\Log::info('Offline message notification [ID: ' . $message->id . ']: ' . $message->name . ' (' . $message->email . '): ' . $message->message);
            }

            return response()->json(['message' => 'Notification sent.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Notification failed: ' . $e->getMessage()], 500);
        }
    }
}
