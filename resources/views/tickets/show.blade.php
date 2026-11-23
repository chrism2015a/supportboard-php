@extends('layouts.app')

@section('title', 'Ticket #' . ($ticket['id'] ?? ''))

@section('content')
<div x-data="{
    showMessageModal: false,
    message: '',
    tickets: {{ json_encode($messages ?? []) }},
    async addMessage() {
        try {
            const res = await fetch('/api/tickets/{{ $ticket['id'] }}/messages', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ message: this.message })
            });
            if (res.ok) {
                this.message = '';
                this.showMessageModal = false;
                window.location.reload();
            } else {
                alert('Failed to send message');
            }
        } catch (e) {
            alert('Error: ' + e.message);
        }
    },
    async updateStatus(status) {
        try {
            const res = await fetch('/api/tickets/{{ $ticket['id'] }}, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ status: status })
            });
            if (res.ok) {
                window.location.reload();
            }
        } catch (e) {
            alert('Failed to update status');
        }
    }
}" class="space-y-6">

    <!-- Back link -->
    <a href="{{ route('tickets.index') }}" class="text-sm text-blue-600 hover:text-blue-700 inline-flex items-center gap-2">
        <i class="fas fa-arrow-left"></i> Back to Tickets
    </a>

    <!-- Ticket Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-xl font-bold text-gray-900">{{ $ticket['subject'] ?? 'No subject' }}</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if($ticket['status'] == 'open') bg-green-100 text-green-800
                        @elseif($ticket['status'] == 'in_progress') bg-blue-100 text-blue-800
                        @elseif($ticket['status'] == 'waiting_customer') bg-yellow-100 text-yellow-800
                        @elseif($ticket['status'] == 'closed') bg-gray-100 text-gray-800
                        @else bg-gray-100 text-gray-800 @endif">
                        {{ ucfirst(str_replace('_', ' ', $ticket['status'] ?? 'unknown')) }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if($ticket['priority'] == 'urgent') bg-red-100 text-red-800
                        @elseif($ticket['priority'] == 'high') bg-orange-100 text-orange-800
                        @elseif($ticket['priority'] == 'medium') bg-blue-100 text-blue-800
                        @else bg-gray-100 text-gray-800 @endif">
                        {{ ucfirst($ticket['priority'] ?? 'medium') }}
                    </span>
                </div>
                <p class="text-sm text-gray-500">
                    #{{ $ticket['id'] ?? 'N/A' }} · Created {{ isset($ticket['created_at']) ? \Carbon\Carbon::parse($ticket['created_at'])->diffForHumans() : 'N/A' }}
                </p>
            </div>
            <div class="flex gap-2">
                <button @click="updateStatus('in_progress')" class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-sm font-medium transition">
                    Start Working
                </button>
                <button @click="updateStatus('closed')" class="px-3 py-1.5 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg text-sm font-medium transition">
                    Close Ticket
                </button>
            </div>
        </div>
    </div>

    <!-- Ticket Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Description -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Description</h3>
            <div class="prose prose-sm max-w-none text-gray-700">
                {{ nl2br(e($ticket['description'] ?? '')) }}
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Details</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm text-gray-500">Customer</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $ticket['customer_email'] ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Status</dt>
                        <dd class="text-sm font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $ticket['status'] ?? 'N/A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Priority</dt>
                        <dd class="text-sm font-medium text-gray-900 capitalize">{{ $ticket['priority'] ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Created</dt>
                        <dd class="text-sm font-medium text-gray-900">
                            {{ isset($ticket['created_at']) ? \Carbon\Carbon::parse($ticket['created_at'])->format('M d, Y H:i') : 'N/A' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Messages / Activity -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Activity ({{ count($messages ?? []) }})</h3>
            <button @click="showMessageModal = true" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                <i class="fas fa-plus mr-2"></i>Add Message
            </button>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($messages as $msg)
            <div class="px-6 py-4">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xs font-bold flex-shrink-0">
                        {{ strtoupper(substr($msg['sender_type'] ?? 'A', 0, 1)) }}
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-900">{{ $msg['sender_type'] == 'customer' ? 'Customer' : 'Agent' }}</span>
                            <span class="text-xs text-gray-500">
                                {{ isset($msg['created_at']) ? \Carbon\Carbon::parse($msg['created_at'])->diffForHumans() : 'N/A' }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-700 mt-1">{{ $msg['message'] ?? '' }}</p>
                    </div>
                </div>
            </div>
            @empty
            <div class="px-6 py-8 text-center text-gray-500">
                <i class="fas fa-comment text-3xl mb-2 text-gray-300"></i>
                <p>No messages yet</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Add Message Modal -->
    <div x-show="showMessageModal" x-cloak
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        @keydown.escape.window="showMessageModal = false">
        <div @click.away="showMessageModal = false" class="bg-white rounded-xl shadow-xl w-full max-w-lg">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Add Message</h3>
                <button @click="showMessageModal = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form @submit.prevent="addMessage()" class="p-6 space-y-4">
                <textarea x-model="message" required rows="4" placeholder="Type your message..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                        Send Message
                    </button>
                    <button type="button" @click="showMessageModal = false" 
                        class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
