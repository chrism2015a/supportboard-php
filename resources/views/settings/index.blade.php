@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
        <p class="text-gray-500 mt-1">Configure your support board</p>
    </div>

    <!-- General Settings -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">General Settings</h3>
        </div>
        <form class="p-6 space-y-4" x-data="{ saving: false }" @submit.prevent="saving = true; setTimeout(() => saving = false, 1000)">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Site Name</label>
                    <input type="text" name="site_name" value="SupportBoard"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
                    <select name="timezone"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="UTC">UTC</option>
                        <option value="America/New_York">Eastern Time</option>
                        <option value="America/Chicago">Central Time</option>
                        <option value="America/Los_Angeles">Pacific Time</option>
                        <option value="Europe/London">London</option>
                        <option value="Asia/Kolkata">India Standard Time</option>
                    </select>
                </div>
            </div>
            <button type="submit" :disabled="saving"
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white rounded-lg text-sm font-medium transition">
                Save Settings
            </button>
        </form>
    </div>

    <!-- WhatsApp Integration -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">WhatsApp Integration</h3>
            <p class="text-sm text-gray-500 mt-1">Connect WhatsApp Business API for omnichannel support</p>
        </div>
        <form class="p-6 space-y-4" x-data="{ saving: false }" @submit.prevent="saving = true; setTimeout(() => saving = false, 1000)">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                    <input type="password" name="whatsapp_api_key"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number ID</label>
                    <input type="text" name="whatsapp_phone_number_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message Template ID</label>
                    <input type="text" name="whatsapp_message_template_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Auto Reply Message</label>
                    <input type="text" name="whatsapp_auto_reply" placeholder="Thank you for contacting us..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>
            <button type="submit" :disabled="saving"
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white rounded-lg text-sm font-medium transition">
                Save WhatsApp Settings
            </button>
        </form>
    </div>

    <!-- Crawler Settings -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Knowledge Base Crawler</h3>
            <p class="text-sm text-gray-500 mt-1">Configure web crawler settings for knowledge base</p>
        </div>
        <form class="p-6 space-y-4" x-data="{ saving: false }" @submit.prevent="saving = true; setTimeout(() => saving = false, 1000)">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Pages</label>
                    <input type="number" name="crawler_max_pages" value="50" min="1"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Depth</label>
                    <input type="number" name="crawler_max_depth" value="3" min="1"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Timeout (seconds)</label>
                    <input type="number" name="crawler_timeout" value="30" min="1"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>
            <div class="flex items-center gap-3">
                <input type="checkbox" name="crawler_respect_robots" value="1" checked id="respect_robots"
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label for="respect_robots" class="text-sm text-gray-700">Respect robots.txt</label>
            </div>
            <button type="submit" :disabled="saving"
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white rounded-lg text-sm font-medium transition">
                Save Crawler Settings
            </button>
        </form>
    </div>

    <!-- API Keys -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">API Keys</h3>
            <p class="text-sm text-gray-500 mt-1">Manage API access for integrations</p>
        </div>
        <div class="p-6">
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Keep your API keys secure. Do not share them publicly.
            </div>
        </div>
    </div>

</div>
@endsection
