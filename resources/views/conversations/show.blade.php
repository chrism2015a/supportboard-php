@extends('layouts.app')

@section('title', 'Conversation #' . ($conversation['id'] ?? ''))

@section('content')
<div x-data="{
    message: '',
    showSendBtn: true,
    async sendMessage() {
        this.showSendBtn = false;
        try {
            const res = await fetch('/api/conversations/{{ $conversation['id'] }}/messages', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ message: this.message })
            });
            if (res.ok) {
                this.message = '';
                window.location.reload();
            } else {
                alert('Failed to send message');
            }
        } catch (e) {
            alert('Error: ' + e.message);
        }
        this.showSendBtn = true;
    },
    async closeConversation() {
        if (confirm('Close this conversation?')) {
            try {
                await fetch('/api/conversations/{{ $conversation['id'] }}/close', { method: 'POST' });
                window.location.href = '{{ route('conversations.index') }}';
            } catch (e) {
                alert('Failed to close conversation');
            }
        }
    }
}" class="space-y-6">

    <!-- Back link -->
    <a href="{{ route('conversations.index') }}" class="text-sm text-blue-600 hover:text-blue-700 inline-flex items-center gap-2">
        <i class="fas fa-arrow-left"></i> Back to Conversations
    </a>

    <!-- Conversation Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold text-lg">
                    {{ strtoupper(substr($conversation['customer_name'] ?? 'C', 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-900">{{ $conversation['customer_name'] ?? 'Customer' }}</h1>
                    <p class="text-sm text-gray-500">
                        {{ $conversation['customer_email'] ?? 'N/A' }}
                    </p>
                </div>
            </div>
            <div class="flex gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                    @if($conversation['status'] == 'open') bg-green-100 text-green-800
                    @elseif($conversation['status'] == 'closed') bg-gray-100 text-gray-800
                    @else bg-gray-100 text-gray-800 @endif">
                    {{ ucfirst($conversation['status'] ?? 'unknown') }}
                </span>
                <button @click="closeConversation()" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg text-sm font-medium transition">
                    <i class="fas fa-times mr-1"></i>Close
                </button>
            </div>
        </div>
    </div>

    <!-- Messages -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="max-h-96 overflow-y-auto divide-y divide-gray-100">
            @forelse($messages as $msg)
            <div class="px-6 py-4 {{ $msg['sender_type'] == 'customer' ? 'bg-gray-50' : '' }}">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0
                        @if($msg['sender_type'] == 'customer') bg-orange-100 text-orange-600
                        @else bg-blue-100 text-blue-600 @endif">
                        {{ strtoupper(substr($msg['sender_type'] ?? 'A', 0, 1)) }}
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-900">
                                {{ $msg['sender_type'] == 'customer' ? 'Customer' : 'Agent' }}
                            </span>
                            <span class="text-xs text-gray-500">
                                {{ isset($msg['created_at']) ? \Carbon\Carbon::parse($msg['created_at'])->format('M d, Y h:i A') : 'N/A' }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-700 mt-1">{{ $msg['message'] ?? '' }}</p>
                    </div>
                </div>
            </div>
            @empty
            <div class="px-6 py-8 text-center text-gray-500">
                <p>No messages yet</p>
            </div>
            @endforelse
        </div>

        <!-- Message Input -->
        <div class="px-6 py-4 border-t border-gray-100">
            <form @submit.prevent="sendMessage()" class="flex gap-3">
                <textarea x-model="message" x-on:input="showSendBtn = message.trim().length > 0"
                    rows="2" placeholder="Type your message..."
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
                <button type="submit" x-show="showSendBtn"
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition self-end h-10">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
