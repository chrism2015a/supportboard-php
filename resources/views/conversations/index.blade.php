@extends('layouts.app')

@section('title', 'Conversations')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Conversations</h1>
            <p class="text-gray-500 mt-1">Manage live chat conversations</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <form method="GET" class="flex flex-col sm:flex-row gap-3">
            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">All Status</option>
                <option value="open" {{ $status == 'open' ? 'selected' : '' }}>Open</option>
                <option value="closed" {{ $status == 'closed' ? 'selected' : '' }}>Closed</option>
            </select>
            <button type="submit" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">
                Filter
            </button>
        </form>
    </div>

    <!-- Conversations List -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="divide-y divide-gray-100">
            @forelse($conversations as $conv)
            <a href="{{ route('conversations.show', $conv['id']) }}" class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 transition block">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold text-sm flex-shrink-0">
                    {{ strtoupper(substr($conv['customer_name'] ?? 'C', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <h4 class="text-sm font-medium text-gray-900 truncate">{{ $conv['customer_name'] ?? 'Customer' }}</h4>
                        @if(($conv['unread_count'] ?? 0) > 0)
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-xs font-bold">
                            {{ $conv['unread_count'] }}
                        </span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5 truncate">
                        {{ Str::limit($conv['last_message'] ?? 'No messages', 60) }}
                    </p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-xs text-gray-500">
                        {{ isset($conv['updated_at']) ? \Carbon\Carbon::parse($conv['updated_at'])->diffForHumans() : 'N/A' }}
                    </p>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mt-1
                        @if($conv['status'] == 'open') bg-green-100 text-green-800
                        @elseif($conv['status'] == 'closed') bg-gray-100 text-gray-800
                        @else bg-gray-100 text-gray-800 @endif">
                        {{ ucfirst($conv['status'] ?? 'unknown') }}
                    </span>
                </div>
            </a>
            @empty
            <div class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-comments text-3xl mb-3 text-gray-300"></i>
                <p>No conversations found</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
