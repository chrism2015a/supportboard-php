<?php

namespace App\Http\Controllers;

use App\Models\ChatbotFlow;
use App\Models\FlowExecution;
use App\Models\ChatbotConfig;
use App\Services\AIService;
use Illuminate\Http\Request;

class FlowController extends Controller
{
    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * List flows for a chatbot.
     */
    public function index(Request $request)
    {
        $query = ChatbotFlow::with('chatbot')
            ->latest();

        if ($request->filled('chatbot_id')) {
            $query->where('chatbot_id', $request->chatbot_id);
        }

        if ($request->filled('active')) {
            $query->where('is_active', true);
        }

        $flows = $query->paginate(20);

        return response()->json($flows);
    }

    /**
     * Create a new flow.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'chatbot_id' => 'required|exists:chatbot_configs,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'steps' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $flow = ChatbotFlow::create($validated);

        return response()->json($flow, 201);
    }

    /**
     * Show a single flow.
     */
    public function show(string $id)
    {
        $flow = ChatbotFlow::with(['chatbot', 'executions'])->findOrFail($id);

        return response()->json($flow);
    }

    /**
     * Update a flow.
     */
    public function update(Request $request, string $id)
    {
        $flow = ChatbotFlow::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'steps' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $flow->update($validated);

        return response()->json($flow);
    }

    /**
     * Delete a flow.
     */
    public function destroy(string $id)
    {
        $flow = ChatbotFlow::findOrFail($id);
        $flow->delete();

        return response()->json(['message' => 'Flow deleted.']);
    }

    /**
     * Toggle flow active status.
     */
    public function activate(string $id)
    {
        $flow = ChatbotFlow::findOrFail($id);
        $flow->update(['is_active' => !$flow->is_active]);

        return response()->json([
            'message' => $flow->is_active ? 'Flow activated.' : 'Flow deactivated.',
            'flow' => $flow,
        ]);
    }

    /**
     * Execute a flow step for a conversation.
     */
    public function execute(Request $request)
    {
        $validated = $request->validate([
            'flow_id' => 'required|integer|exists:chatbot_flows,id',
            'session_id' => 'required|string',
            'conversation_id' => 'required|integer',
            'message' => 'required|string',
            'step' => 'nullable|string',
        ]);

        $flow = ChatbotFlow::findOrFail($validated['flow_id']);
        $chatbot = ChatbotConfig::where('is_active', true)->first();

        // Get or create flow execution
        $execution = FlowExecution::where('session_id', $validated['session_id'])
            ->where('flow_id', $validated['flow_id'])
            ->first();

        if (!$execution) {
            $execution = FlowExecution::create([
                'chatbot_id' => $chatbot?->id,
                'flow_id' => $validated['flow_id'],
                'session_id' => $validated['session_id'],
                'conversation_id' => $validated['conversation_id'],
                'current_step' => $validated['step'] ?? 'start',
                'status' => 'running',
            ]);
        }

        // Execute flow logic
        $steps = $flow->steps ?? [];
        $currentStep = $validated['step'] ?? 'start';

        // Find the current step
        $stepData = null;
        foreach ($steps as $step) {
            if ($step['name'] === $currentStep) {
                $stepData = $step;
                break;
            }
        }

        // If no step specified, find first matching step based on message
        if (!$stepData) {
            foreach ($steps as $step) {
                if (isset($step['trigger']) && strpos(strtolower($validated['message']), strtolower($step['trigger'])) !== false) {
                    $stepData = $step;
                    break;
                }
            }

            // Default to first step
            if (!$stepData && count($steps) > 0) {
                $stepData = $steps[0];
            }
        }

        if (!$stepData) {
            $execution->abandon('No matching step found');
            return response()->json([
                'message' => "I couldn't understand that. How can I help?",
                'flow_completed' => true,
            ], 200);
        }

        // Process step action
        $response = $this->processStep($stepData, $validated['message'], $execution->variables ?? []);

        // Determine next step
        $nextStep = $this->getNextStep($stepData, $steps, $response);

        if ($nextStep) {
            $execution->advanceStep($nextStep, [
                'variables' => $response['variables'] ?? [],
            ]);
        } else {
            $execution->complete();
        }

        return response()->json([
            'response' => $response['message'],
            'next_step' => $nextStep,
            'flow_completed' => $nextStep === null,
            'variables' => $execution->variables,
        ]);
    }

    /**
     * Process a flow step.
     */
    protected function processStep(array $step, string $message, array $variables): array
    {
        $action = $step['action'] ?? 'respond';

        switch ($action) {
            case 'respond':
                return [
                    'message' => $step['response'] ?? 'Thank you for your response.',
                    'variables' => $variables,
                ];

            case 'ask':
                return [
                    'message' => $step['question'] ?? 'Please provide more information.',
                    'variables' => $variables,
                ];

            case 'branch':
                $condition = $step['condition'] ?? '';
                $value = $variables[$condition['variable'] ?? ''] ?? '';

                if ($condition['operator'] === 'equals' && $value === ($condition['value'] ?? '')) {
                    return [
                        'message' => $step['true_response'] ?? 'Correct!',
                        'next_step' => $step['true_branch'] ?? null,
                        'variables' => $variables,
                    ];
                }

                return [
                    'message' => $step['false_response'] ?? 'Let me try again.',
                    'next_step' => $step['false_branch'] ?? null,
                    'variables' => $variables,
                ];

            case 'ai':
                $prompt = $step['prompt'] ?? "Respond to: {$message}";
                $response = $this->aiService->chat($prompt);

                return [
                    'message' => $response['success'] ? $response['reply'] : 'Sorry, I\'m having trouble responding right now.',
                    'variables' => $variables,
                ];

            case 'collect':
                $field = $step['field'] ?? 'response';
                $newVariables = array_merge($variables, [$field => $message]);

                return [
                    'message' => $step['confirmation'] ?? "Got it: {$message}",
                    'variables' => $newVariables,
                ];

            default:
                return [
                    'message' => $step['response'] ?? "Step: {$action}",
                    'variables' => $variables,
                ];
        }
    }

    /**
     * Get next step from current step.
     */
    protected function getNextStep(array $currentStep, array $steps, array $response): ?string
    {
        // Explicit next step
        if (isset($response['next_step'])) {
            return $response['next_step'];
        }

        if (isset($currentStep['next_step'])) {
            return $currentStep['next_step'];
        }

        return null;
    }

    /**
     * Get flow execution history.
     */
    public function executions(Request $request)
    {
        $validated = $request->validate([
            'flow_id' => 'required|integer|exists:chatbot_flows,id',
            'session_id' => 'nullable|string',
        ]);

        $query = FlowExecution::with(['chatbot', 'flow', 'conversation'])
            ->where('flow_id', $validated['flow_id']);

        if ($validated['session_id'] ?? null) {
            $query->where('session_id', $validated['session_id']);
        }

        $executions = $query->latest()->paginate(20);

        return response()->json($executions);
    }

    /**
     * Get flow analytics.
     */
    public function analytics(Request $request)
    {
        $validated = $request->validate([
            'flow_id' => 'required|integer|exists:chatbot_flows,id',
        ]);

        $executions = FlowExecution::where('flow_id', $validated['flow_id'])->get();

        $total = $executions->count();
        $completed = $executions->where('status', 'completed')->count();
        $errors = $executions->where('status', 'error')->count();
        $abandoned = $executions->where('status', 'abandoned')->count();

        // Find most common final step
        $finalSteps = $executions
            ->where('status', 'completed')
            ->pluck('current_step')
            ->countValues();

        return response()->json([
            'total_executions' => $total,
            'completed' => $completed,
            'errors' => $errors,
            'abandoned' => $abandoned,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'most_common_final_step' => $finalSteps->firstKey() ?? null,
            'most_common_count' => $finalSteps->first() ?? 0,
        ]);
    }
}
