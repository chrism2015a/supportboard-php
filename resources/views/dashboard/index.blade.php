@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div x-data="{
    loading: true,
    stats: {{ json_encode($stats) }},
    recentTickets: {{ json_encode($recentTickets) }},
    recentConversations: {{ json_encode($recentConversations) }},
    async refresh() {
        this.loading = true;
        try {
            const token = await this.getToken();
            const [dashboardRes, ticketsRes] = await Promise.all([
                fetch('/api/dashboard', { headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }}),
                fetch('/api/tickets?limit=5&sort=created_at&order=desc', { headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }})
            ]);
            this.stats = await dashboardRes.json().then(d => ({
                total_tickets: d.total_tickets || d.data?.total_tickets || this.stats.total_tickets,
                open_tickets: d.open_tickets || d.data?.open_tickets || this.stats.open_tickets,
                total_conversations: d.total_conversations || d.data?.total_conversations || this.stats.total_conversations,
                unread_messages: d.unread_messages || d.data?.unread_messages || this.stats.unread_messages,
            }));
            if (ticketsRes.ok) {
                const data = await ticketsRes.json();
                this.recentTickets = data.data || [];
            }
        } catch (e) {
            console.error('Refresh failed:', e);
        }
        this.loading = false;
    }
}" class="space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-500 mt-1">Welcome back! Here's what's happening.</p>
        </div>
        <button @click="refresh()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
            <i class="fas fa-sync-alt mr-2"></i>Refresh
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Tickets</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1" x-text="loading ? '...' : stats.total_tickets"></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-ticket-alt text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Open Tickets</p>
                    <p class="text-3xl font-bold text-orange-600 mt-1" x-text="loading ? '...' : stats.open_tickets"></p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-folder-open text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Conversations</p>
                    <p class="text-3xl font-bold text-green-600 mt-1" x-text="loading ? '...' : stats.total_conversations"></p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-comments text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Unread Messages</p>
                    <p class="text-3xl font-bold text-purple-600 mt-1" x-text="loading ? '...' : stats.unread_messages"></p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-envelope text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Ticket Trends</h3>
            <canvas id="ticketChart" height="200"></canvas>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Priority Distribution</h3>
            <canvas id="priorityChart" height="200"></canvas>
        </div>
    </div>

    <!-- Recent Items -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Tickets -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Recent Tickets</h3>
                <a href="{{ route('tickets.index') }}" class="text-sm text-blue-600 hover:text-blue-700">View All →</a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($recentTickets as $ticket)
                <div class="px-6 py-4 hover:bg-gray-50 transition">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-gray-900">{{ $ticket['subject'] ?? 'No subject' }}</h4>
                            <p class="text-xs text-gray-500 mt-1">#{{ $ticket['id'] ?? 'N/A' }} · {{ $ticket['status'] ?? 'unknown' }}</p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                            @if($ticket['status'] ?? '' == 'open') bg-green-100 text-green-800
                            @elseif($ticket['status'] ?? '' == 'in_progress') bg-blue-100 text-blue-800
                            @elseif($ticket['status'] ?? '' == 'closed') bg-gray-100 text-gray-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ ucfirst($ticket['status'] ?? 'unknown') }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-ticket-alt text-3xl mb-2 text-gray-300"></i>
                    <p>No tickets found</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Conversations -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Recent Conversations</h3>
                <a href="{{ route('conversations.index') }}" class="text-sm text-blue-600 hover:text-blue-700">View All →</a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($recentConversations as $conv)
                <div class="px-6 py-4 hover:bg-gray-50 transition">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-gray-900">{{ $conv['customer_name'] ?? 'Customer' }}</h4>
                            <p class="text-xs text-gray-500 mt-1">{{ Str::limit($conv['last_message'] ?? 'No messages', 50) }}</p>
                        </div>
                        @if(($conv['unread_count'] ?? 0) > 0)
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-500 text-white text-xs font-bold">
                            {{ $conv['unread_count'] }}
                        </span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-comments text-3xl mb-2 text-gray-300"></i>
                    <p>No conversations found</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
    // Ticket Status Chart
    const ticketCtx = document.getElementById('ticketChart');
    if (ticketCtx) {
        new Chart(ticketCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Created',
                    data: [3, 5, 2, 7, 4, 1, 2],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Priority Distribution Chart
    const priorityCtx = document.getElementById('priorityChart');
    if (priorityCtx) {
        new Chart(priorityCtx, {
            type: 'doughnut',
            data: {
                labels: ['Urgent', 'High', 'Medium', 'Low'],
                datasets: [{
                    data: [5, 12, 25, 18],
                    backgroundColor: ['#ef4444', '#f97316', '#3b82f6', '#22c55e'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 20 } }
                },
                cutout: '65%',
            }
        });
    }
</script>
@endsection
