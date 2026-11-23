<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SupportBoard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen" x-data="{ sidebarOpen: true }">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'w-64' : 'w-20'" 
            class="bg-white border-r border-gray-200 flex flex-col transition-all duration-300 ease-in-out flex-shrink-0">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-headset text-white text-sm"></i>
                    </div>
                    <span class="font-bold text-lg text-gray-900 transition-opacity duration-300" 
                        :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">SupportBoard</span>
                </a>
            </div>

            <nav class="flex-1 overflow-y-auto py-4">
                <div class="px-3 space-y-1">
                    <a href="{{ route('dashboard') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                        <i class="fas fa-chart-pie w-5 text-center flex-shrink-0"></i>
                        <span class="transition-opacity duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">Dashboard</span>
                    </a>
                    <a href="{{ route('tickets.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                        <i class="fas fa-ticket-alt w-5 text-center flex-shrink-0"></i>
                        <span class="transition-opacity duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">Tickets</span>
                    </a>
                    <a href="{{ route('conversations.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                        <i class="fas fa-comments w-5 text-center flex-shrink-0"></i>
                        <span class="transition-opacity duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">Conversations</span>
                    </a>
                    <a href="{{ route('chatbots.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                        <i class="fas fa-robot w-5 text-center flex-shrink-0"></i>
                        <span class="transition-opacity duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">Chatbots</span>
                    </a>
                    <a href="{{ route('settings.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                        <i class="fas fa-cog w-5 text-center flex-shrink-0"></i>
                        <span class="transition-opacity duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">Settings</span>
                    </a>
                </div>
            </nav>

            <div class="p-4 border-t border-gray-200">
                <a href="{{ route('logout') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-red-600 hover:bg-red-50 transition">
                    <i class="fas fa-sign-out-alt w-5 text-center flex-shrink-0"></i>
                    <span class="transition-opacity duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 hidden'">Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <button @click="sidebarOpen = !sidebarOpen" 
                    class="p-2 rounded-lg hover:bg-gray-100 transition text-gray-600">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600" x-text="user?.name || 'Admin'"></span>
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-medium text-sm">
                        {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6">
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        // Initialize Alpine.js data
        document.addEventListener('alpine:init', () => {
            Alpine.data('admin', () => ({
                user: @json(session('user', [])),
                async getToken() {
                    try {
                        const res = await fetch('{{ route('auth.token') }}');
                        const data = await res.json();
                        return data.token;
                    } catch (e) {
                        return null;
                    }
                }
            }));
        });
    </script>
</body>
</html>
