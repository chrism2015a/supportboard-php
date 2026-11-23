@extends('layouts.app')

@section('title', 'Chatbot Configuration')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Chatbot Configuration</h1>
        <p class="text-gray-500 mt-1">Configure AI features for your support chatbot</p>
    </div>

    <!-- LLM Configuration -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">LLM Settings</h3>
            <p class="text-sm text-gray-500 mt-1">Configure your AI language model provider</p>
        </div>
        <form class="p-6 space-y-4" x-data="{
            endpoint: '',
            model: '',
            apiKey: '',
            temperature: '0.7',
            maxTokens: '1024',
            systemPrompt: 'You are a helpful customer support assistant.',
            saving: false,

            async save() {
                this.saving = true;
                try {
                    const res = await fetch('/api/chatbots/config/llm/update', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({
                            llm_endpoint: this.endpoint,
                            llm_model: this.model,
                            llm_api_key: this.apiKey,
                            llm_temperature: parseFloat(this.temperature),
                            llm_max_tokens: parseInt(this.maxTokens),
                            llm_system_prompt: this.systemPrompt,
                        })
                    });
                    if (res.ok) {
                        alert('LLM configuration saved successfully!');
                    } else {
                        alert('Failed to save LLM configuration');
                    }
                } catch (e) {
                    alert('Error: ' + e.message);
                }
                this.saving = false;
            }
        }">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">API Endpoint</label>
                    <input type="url" x-model="endpoint" placeholder="https://api.openai.com/v1"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                    <input type="text" x-model="model" placeholder="gpt-3.5-turbo"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                    <input type="password" x-model="apiKey" placeholder="sk-..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Temperature (0-2)</label>
                    <input type="number" x-model="temperature" min="0" max="2" step="0.1"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Tokens</label>
                    <input type="number" x-model="maxTokens" min="1"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">System Prompt</label>
                <textarea x-model="systemPrompt" rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
            </div>
            <button type="submit" :disabled="saving"
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white rounded-lg text-sm font-medium transition">
                Save LLM Settings
            </button>
        </form>
    </div>

    <!-- AI Features -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">AI Features</h3>
            <p class="text-sm text-gray-500 mt-1">Toggle AI-powered features</p>
        </div>
        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between py-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-900">Smart Reply</h4>
                    <p class="text-xs text-gray-500 mt-0.5">AI-generated suggested replies for agents</p>
                </div>
                <button class="w-11 h-6 bg-green-500 rounded-full relative transition" 
                    onclick="this.classList.toggle('bg-green-500'); this.classList.toggle('bg-gray-300')">
                    <span class="absolute right-1 top-1 w-4 h-4 bg-white rounded-full transition-transform"></span>
                </button>
            </div>
            <div class="flex items-center justify-between py-3 border-t border-gray-100">
                <div>
                    <h4 class="text-sm font-medium text-gray-900">Message Rewriting</h4>
                    <p class="text-xs text-gray-500 mt-0.5">Rewrite messages for better clarity and tone</p>
                </div>
                <button class="w-11 h-6 bg-green-500 rounded-full relative transition"
                    onclick="this.classList.toggle('bg-green-500'); this.classList.toggle('bg-gray-300')">
                    <span class="absolute right-1 top-1 w-4 h-4 bg-white rounded-full transition-transform"></span>
                </button>
            </div>
            <div class="flex items-center justify-between py-3 border-t border-gray-100">
                <div>
                    <h4 class="text-sm font-medium text-gray-900">Message Translation</h4>
                    <p class="text-xs text-gray-500 mt-0.5">Auto-translate messages between languages</p>
                </div>
                <button class="w-11 h-6 bg-green-500 rounded-full relative transition"
                    onclick="this.classList.toggle('bg-green-500'); this.classList.toggle('bg-gray-300')">
                    <span class="absolute right-1 top-1 w-4 h-4 bg-white rounded-full transition-transform"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Saved Replies -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Saved Replies</h3>
                <p class="text-sm text-gray-500 mt-1">Pre-built responses for common questions</p>
            </div>
            <button x-data="{ show: false, title: '', content: '' }" @click="show = true"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                <i class="fas fa-plus mr-2"></i>Add Reply
            </button>
        </div>
        <div class="p-6">
            <template x-data="{ loading: true, replies: [] }" x-init="
                (async () => {
                    try {
                        const res = await fetch('/api/saved-replies', {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (res.ok) replies = (await res.json()).data || [];
                    } catch (e) { console.error(e); }
                    loading = false;
                })()
            ">
                <div x-show="loading" class="text-center py-8 text-gray-500">
                    <i class="fas fa-spinner fa-spin text-2xl"></i>
                </div>
                <div x-show="!loading" class="space-y-3">
                    <template x-for="reply in replies" :key="reply.id">
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900" x-text="reply.title || reply.name"></h4>
                            <p class="text-xs text-gray-500 mt-1" x-text="reply.content ? (reply.content.substring(0, 100) + (reply.content.length > 100 ? '...' : '')) : ''"></p>
                        </div>
                    </template>
                    <p x-show="!loading && replies.length === 0" class="text-center text-gray-500 py-4">No saved replies yet</p>
                </div>
            </template>
        </div>
    </div>

</div>
@endsection
