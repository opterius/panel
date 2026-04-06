<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Server Dashboard</h2>
    </x-slot>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center space-x-4">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">Servers</div>
                <div class="text-2xl font-bold text-gray-900">{{ \App\Models\Server::count() }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center space-x-4">
            <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">Accounts</div>
                <div class="text-2xl font-bold text-gray-900">{{ \App\Models\Account::count() }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center space-x-4">
            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">Domains</div>
                <div class="text-2xl font-bold text-gray-900">{{ \App\Models\Domain::count() }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center space-x-4">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">Databases</div>
                <div class="text-2xl font-bold text-gray-900">{{ \App\Models\Database::count() }}</div>
            </div>
        </div>
    </div>

    <!-- Servers List -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-base font-semibold text-gray-800">Servers</h3>
            <a href="{{ route('admin.servers.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                Add Server
            </a>
        </div>

        @php $servers = \App\Models\Server::with('domains', 'accounts')->latest()->take(10)->get(); @endphp
        @if($servers->isEmpty())
            <div class="px-6 py-12 text-center">
                <p class="text-sm text-gray-500">No servers yet. Add your first server to get started.</p>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($servers as $server)
                    <a href="{{ route('admin.servers.show', $server) }}" class="flex items-center justify-between px-6 py-4 hover:bg-gray-100 transition">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                @if($server->status === 'online') bg-green-100 @else bg-gray-100 @endif">
                                <svg class="w-5 h-5 @if($server->status === 'online') text-green-600 @else text-gray-400 @endif"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">{{ $server->name }}</div>
                                <div class="text-xs text-gray-500">{{ $server->ip_address }} &middot; {{ $server->domains->count() }} domains &middot; {{ $server->accounts->count() }} accounts</div>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                            @if($server->status === 'online') bg-green-100 text-green-700 @else bg-gray-100 text-gray-600 @endif">
                            {{ ucfirst($server->status) }}
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-admin-layout>
