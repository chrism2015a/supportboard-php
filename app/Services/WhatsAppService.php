<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected Client $http;
    protected string $baseUrl;
    protected string $apiKey;
    protected ?string $phoneNumberId;
    protected ?string $messageTemplateId;

    public function __construct()
    {
        $this->baseUrl = config('services.whatsapp.base_url') ?? 'https://graph.facebook.com/v18.0';
        $this->apiKey = config('services.whatsapp.api_key');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->messageTemplateId = config('services.whatsapp.message_template_id');

        $this->http = new Client([
            'timeout' => 30,
            'verify' => true,
        ]);
    }

    /**
     * Send a WhatsApp message to a user.
     *
     * @param string $toPhone The recipient's phone number (E.164 format)
     * @param string $message The message text
     * @param string|null $templateName Optional template name for structured messages
     * @return array {success: bool, messageId: ?string, error: ?string}
     */
    public function sendMessage(string $toPhone, string $message, ?string $templateName = null): array
    {
        if (!$this->apiKey || !$this->phoneNumberId) {
            Log::warning('WhatsApp: API key or phone number ID not configured.');
            return ['success' => false, 'messageId' => null, 'error' => 'WhatsApp not configured.'];
        }

        // Strip non-numeric from phone number
        $toPhone = preg_replace('/[^0-9+]/', '', $toPhone);
        if (!str_starts_with($toPhone, '+')) {
            $toPhone = '+' . $toPhone;
        }

        try {
            if ($templateName) {
                return $this->sendTemplate($toPhone, $templateName);
            }

            // Send free-form text via text template
            return $this->sendTemplate($toPhone, 'supportboard_message', [
                'text' => $message,
            ]);
        } catch (GuzzleException $e) {
            Log::error('WhatsApp send error: ' . $e->getMessage());
            return ['success' => false, 'messageId' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send a WhatsApp template message.
     */
    protected function sendTemplate(string $toPhone, string $templateName, array $params = []): array
    {
        $url = "{$this->baseUrl}/{$this->phoneNumberId}/messages";

        $templateComponents = [];
        if (!empty($params['text'])) {
            $templateComponents[] = [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $params['text']],
                ],
            ];
        }

        $body = [
            'messaging_product' => 'whatsapp',
            'to' => $toPhone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => 'en_US'],
                'components' => $templateComponents,
            ],
        ];

        $response = $this->http->post($url, [
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ],
            'json' => $body,
        ]);

        $data = json_decode($response->getBody(), true);

        return [
            'success' => isset($data['messages'][0]['id']),
            'messageId' => $data['messages'][0]['id'] ?? null,
            'error' => $data['errors'][0]['message'] ?? null,
        ];
    }

    /**
     * Send a reply to an incoming WhatsApp conversation.
     */
    public function reply(string $waId, string $message): array
    {
        return $this->sendMessage(
            config('services.whatsapp.incoming_sender_phone'),
            $message
        );
    }

    /**
     * Check if WhatsApp is properly configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->phoneNumberId);
    }

    /**
     * Test the WhatsApp connection.
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'WhatsApp not configured. Provide API key and phone number ID.'];
        }

        try {
            $response = $this->http->get("{$this->baseUrl}/me", [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return [
                'success' => isset($data['id']),
                'phoneNumber' => $data['phone_number'] ?? null,
                'businessId' => $data['id'] ?? null,
                'error' => null,
            ];
        } catch (GuzzleException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Receive incoming WhatsApp message (webhook handler).
     */
    public function handleWebhook(array $payload): array
    {
        $contacts = $payload['contacts'] ?? [];
        $messages = $payload['messages'] ?? [];
        $timestamp = $payload['timestamp'] ?? '';

        if (empty($messages) || empty($contacts)) {
            return ['processed' => false, 'reason' => 'No messages or contacts.'];
        }

        $contact = $contacts[0];
        $message = $messages[0];

        $from = $contact['wa_id'] ?? '';
        $phoneNumber = $contact['phone'] ?? '';
        $name = $contact['profile']['name'] ?? 'WhatsApp User';
        $text = $message['text']['body'] ?? '';
        $messageId = $message['id'] ?? '';

        return [
            'from' => $from,
            'phone_number' => $phoneNumber,
            'name' => $name,
            'text' => $text,
            'message_id' => $messageId,
            'timestamp' => $timestamp,
            'processed' => true,
        ];
    }
}
