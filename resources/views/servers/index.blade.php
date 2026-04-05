<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Servers</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm">
        <!-- Header -->
        <div class="flex justify-between items-center px-6 py-5 border-b border-gray-100">
            <div>
                <h3 class="text-base font-semibold text-gray-800">All Servers</h3>
                <p class="text-sm text-gray-500 mt-1">Manage your connected servers and view their status.</p>
            </div>
            <a href="{{ route('admin.servers.create') }}" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                Add Server
            </a>
        </div>

        <!-- Server List -->
        @if($servers->isEmpty())
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>
                <h3 class="mt-4 text-base font-medium text-gray-700">No servers yet</h3>
                <p class="mt-2 text-sm text-gray-500">Add your first server to start managing it.</p>
                <a href="{{ route('admin.servers.create') }}" class="mt-6 inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    Add Server
                </a>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($servers as $server)
                    <a href="{{ route('admin.servers.show', $server) }}" class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition">
                        <div class="flex items-center space-x-4">
                            <!-- Status Indicator -->
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                @if($server->status === 'online') bg-green-100
                                @elseif($server->status === 'offline') bg-red-100
                                @elseif($server->status === 'error') bg-red-100
                                @else bg-gray-100
                                @endif">
                                <svg class="w-5 h-5
                                    @if($server->status === 'online') text-green-600
                                    @elseif($server->status === 'offline') text-red-600
                                    @elseif($server->status === 'error') text-red-600
                                    @else text-gray-400
                                    @endif"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                </svg>
                            </div>

                            <div>
                                <div class="text-sm font-semibold text-gray-800">{{ $server->name }}</div>
                                <div class="text-sm text-gray-500">{{ $server->ip_address }}@if($server->hostname) &middot; {{ $server->hostname }}@endif</div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-6">
                            <!-- Stats -->
                            <div class="hidden md:flex items-center space-x-6 text-sm text-gray-500">
                                <span>{{ $server->domains->count() }} domains</span>
                                <span>{{ $server->accounts->count() }} accounts</span>
                                <span>{{ $server->databases->count() }} databases</span>
                            </div>

                            <!-- Status Badge -->
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                @if($server->status === 'online') bg-green-100 text-green-700
                                @elseif($server->status === 'offline') bg-red-100 text-red-700
                                @elseif($server->status === 'error') bg-red-100 text-red-700
                                @else bg-gray-100 text-gray-600
                                @endif">
                                {{ ucfirst($server->status) }}
                            </span>

                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-admin-layout>
