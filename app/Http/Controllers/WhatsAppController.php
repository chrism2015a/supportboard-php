<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    protected WhatsAppService $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    /**
     * Handle incoming WhatsApp webhook.
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();

        // Verify hub.challenge for setup
        if (isset($payload['hub_mode']) && $payload['hub_mode'] === 'subscribe') {
            return response($payload['hub_challenge'], 200);
        }

        $result = $this->whatsapp->handleWebhook($payload);

        if (!$result['processed']) {
            return response()->json(['status' => 'ignored']);
        }

        // Find or create conversation from WhatsApp user
        $conversation = \App\Models\Conversation::firstOrCreate(
            [
                'external_id' => $result['from'],
                'source' => 'whatsapp',
            ],
            [
                'name' => $result['name'],
                'status' => 'open',
                'department_id' => config('services.whatsapp.default_department_id'),
            ]
        );

        // Create message
        $conversation->messages()->create([
            'user_id' => null,
            'sender_type' => 'customer',
            'content' => $result['text'],
            'type' => 'text',
            'is_read' => false,
        ]);

        // Auto-reply or route to agent
        $autoReply = config('services.whatsapp.auto_reply');
        if ($autoReply) {
            $this->whatsapp->sendMessage(
                preg_replace('/[^0-9+]/', '', $result['phone_number'] ?? ''),
                $autoReply
            );
        }

        // Create notification for agents
        if ($conversation->agent_id) {
            $conversation->agent->notify(new \App\Notifications\NewWhatsAppMessage($conversation, $result['text']));
        }

        return response()->json(['status' => 'received', 'message_id' => $result['message_id']]);
    }

    /**
     * Get WhatsApp configuration status.
     */
    public function status()
    {
        $configured = $this->whatsapp->isConfigured();
        $config = [
            'base_url' => config('services.whatsapp.base_url'),
            'phone_number_id' => $configured ? '***' . substr(config('services.whatsapp.phone_number_id'), -4) : null,
            'message_template_id' => config('services.whatsapp.message_template_id'),
            'auto_reply' => config('services.whatsapp.auto_reply') ? 'enabled' : 'disabled',
            'webhook_verified' => config('services.whatsapp.webhook_verified', false),
        ];

        return response()->json([
            'configured' => $configured,
            'config' => $config,
        ]);
    }

    /**
     * Test WhatsApp connection.
     */
    public function test()
    {
        $result = $this->whatsapp->testConnection();

        return response()->json($result);
    }

    /**
     * Send a WhatsApp message (admin-initiated).
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string|max:2000',
        ]);

        $result = $this->whatsapp->sendMessage($validated['phone'], $validated['message']);

        return response()->json($result);
    }
}
