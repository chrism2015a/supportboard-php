@extends('layouts.app')

@section('title', 'Tickets')

@section('content')
<div x-data="{
    search: '{{ $search ?? '' }}',
    showCreateModal: false,
    newTicket: { subject: '', description: '', priority: 'medium', status: 'open' },
    async createTicket() {
        try {
            const res = await fetch('/api/tickets', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(this.newTicket)
            });
            if (res.ok) {
                window.location.reload();
            } else {
                alert('Failed to create ticket');
            }
        } catch (e) {
            alert('Error: ' + e.message);
        }
    }
}" class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Tickets</h1>
            <p class="text-gray-500 mt-1">Manage and track customer support tickets</p>
        </div>
        <button @click="showCreateModal = true" 
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
            <i class="fas fa-plus mr-2"></i>New Ticket
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <form method="GET" class="flex flex-col sm:flex-row gap-3">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search tickets..."
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">All Status</option>
                <option value="open" {{ $status == 'open' ? 'selected' : '' }}>Open</option>
                <option value="in_progress" {{ $status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="waiting_customer" {{ $status == 'waiting_customer' ? 'selected' : '' }}>Waiting</option>
                <option value="closed" {{ $status == 'closed' ? 'selected' : '' }}>Closed</option>
            </select>
            <select name="priority" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">All Priority</option>
                <option value="urgent" {{ $priority == 'urgent' ? 'selected' : '' }}>Urgent</option>
                <option value="high" {{ $priority == 'high' ? 'selected' : '' }}>High</option>
                <option value="medium" {{ $priority == 'medium' ? 'selected' : '' }}>Medium</option>
                <option value="low" {{ $priority == 'low' ? 'selected' : '' }}>Low</option>
            </select>
            <button type="submit" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">
                Filter
            </button>
        </form>
    </div>

    <!-- Tickets Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($tickets as $ticket)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-sm text-gray-900 font-medium">#{{ $ticket['id'] ?? 'N/A' }}</td>
                        <td class="px-6 py-4">
                            <a href="{{ route('tickets.show', $ticket['id']) }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                                {{ $ticket['subject'] ?? 'No subject' }}
                            </a>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $ticket['customer_email'] ?? 'N/A' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($ticket['status'] == 'open') bg-green-100 text-green-800
                                @elseif($ticket['status'] == 'in_progress') bg-blue-100 text-blue-800
                                @elseif($ticket['status'] == 'waiting_customer') bg-yellow-100 text-yellow-800
                                @elseif($ticket['status'] == 'closed') bg-gray-100 text-gray-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst(str_replace('_', ' ', $ticket['status'] ?? 'unknown')) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600 capitalize">{{ $ticket['priority'] ?? 'medium' }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ isset($ticket['created_at']) ? \Carbon\Carbon::parse($ticket['created_at'])->diffForHumans() : 'N/A' }}
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('tickets.show', $ticket['id']) }}" 
                                class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-ticket-alt text-3xl mb-3 text-gray-300"></i>
                            <p>No tickets found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Ticket Modal -->
    <div x-show="showCreateModal" x-cloak
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        @keydown.escape.window="showCreateModal = false">
        <div @click.away="showCreateModal = false" class="bg-white rounded-xl shadow-xl w-full max-w-lg">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Create New Ticket</h3>
                <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form @submit.prevent="createTicket()" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <input type="text" x-model="newTicket.subject" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea x-model="newTicket.description" required rows="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                        <select x-model="newTicket.priority"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select x-model="newTicket.status"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                        Create Ticket
                    </button>
                    <button type="button" @click="showCreateModal = false" 
                        class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
