<?php

namespace App\Models;

use App\Services\AIService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartNote extends Model
{
    use HasFactory;

    protected $table = 'smart_notes';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'summary',
        'action_items',
        'sentiment',
        'sentiment_score',
    ];

    protected $casts = [
        'action_items' => 'array',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a smart note using AI for a conversation.
     */
    public static function generate(int $conversationId, AIService $ai): self
    {
        $conversation = Conversation::with(['messages.user', 'user'])->findOrFail($conversationId);

        // Build conversation history
        $historyText = '';
        foreach ($conversation->messages()->latest()->limit(50)->get() as $msg) {
            $role = $msg->user && $msg->user->role === 'user' ? 'Customer' : 'Agent';
            $historyText .= "{$role}: {$msg->text}\n";
        }

        // Call AI for summary
        $summaryResponse = $ai->chat(
            "Summarize the following customer conversation in 3-4 sentences. Include the main issue and any resolution discussed.\n\nConversation:\n{$historyText}",
            [],
            ['You are a customer support summary assistant. Provide concise, professional summaries.']
        );

        // Call AI for action items
        $actionItemsResponse = $ai->chat(
            "Extract action items from the following customer conversation. List them as a bullet list. If there are no action items, say 'None'.\n\nConversation:\n{$historyText}",
            [],
            ['You are a customer support action item extractor. List only clear, actionable next steps.']
        );

        // Call AI for sentiment
        $sentimentResponse = $ai->chat(
            "Analyze the sentiment of the following customer conversation. Return only one word: 'positive', 'neutral', or 'negative', followed by a brief score (e.g., 'positive (8/10)').\n\nConversation:\n{$historyText}",
            [],
            ['You are a sentiment analysis assistant for customer support conversations.']
        );

        // Parse results
        $actionItemsText = trim($actionItemsResponse['reply']);
        $actionItems = array_map('trim', preg_split('/\n/', $actionItemsText));
        if (count($actionItems) === 1 && strtolower($actionItems[0]) === 'none') {
            $actionItems = [];
        }

        $sentimentParts = explode(' (', trim($sentimentResponse['reply']));
        $sentimentScore = isset($sentimentParts[1]) ? rtrim($sentimentParts[1], ')') : null;

        $note = self::create([
            'conversation_id' => $conversationId,
            'user_id' => auth()->id(),
            'summary' => $summaryResponse['success'] ? trim($summaryResponse['reply']) : 'Unable to generate summary.',
            'action_items' => $actionItems,
            'sentiment' => trim($sentimentParts[0]),
            'sentiment_score' => $sentimentScore,
        ]);

        return $note;
    }
}
