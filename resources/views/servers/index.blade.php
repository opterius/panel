<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">{{ __('servers.servers') }}</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- License limit info banner — shown when the user is at their server limit --}}
    @if(isset($atLimit) && $atLimit)
        <div class="mb-6 bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="flex-1">
                <strong class="font-semibold text-amber-900">Server limit reached ({{ $currentServers }} / {{ $maxServers }})</strong>
                <p class="mt-1 text-sm text-amber-800">
                    Your current license plan allows up to <strong>{{ $maxServers }} {{ $maxServers === 1 ? 'server' : 'servers' }}</strong>. To connect more servers to this Panel,
                    upgrade your plan at
                    <a href="https://opterius.com/dashboard/billing" target="_blank" rel="noopener" class="font-semibold underline hover:text-amber-900">opterius.com/dashboard/billing</a>.
                </p>
                <p class="mt-1 text-xs text-amber-700">
                    Plans: Plus = 1 server · Business = 3 servers · Datacenter = unlimited.
                    Each server you add uses one activation slot. You can also revoke an unused activation
                    at <a href="https://opterius.com/dashboard/licenses" target="_blank" rel="noopener" class="font-semibold underline hover:text-amber-900">opterius.com/dashboard/licenses</a> to free a slot.
                </p>
            </div>
            <a href="https://opterius.com/dashboard/billing" target="_blank" rel="noopener"
               class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg transition">
                Upgrade Plan
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm">
        <!-- Header -->
        <div class="flex justify-between items-center px-6 py-5 border-b border-gray-100">
            <div>
                <h3 class="text-base font-semibold text-gray-800">{{ __('servers.all_servers') }}</h3>
                <p class="text-sm text-gray-500 mt-1">
                    {{ __('servers.manage_servers_description') }}
                    @if(isset($maxServers) && $maxServers !== PHP_INT_MAX)
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $atLimit ? 'bg-red-100 text-red-700' : 'bg-indigo-100 text-indigo-700' }}">
                            {{ $currentServers }} / {{ $maxServers }} servers
                        </span>
                    @endif
                </p>
            </div>
            @if(isset($atLimit) && $atLimit)
                <a href="https://opterius.com/dashboard/billing" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-amber-100 hover:bg-amber-200 text-amber-800 text-sm font-medium rounded-lg transition"
                   title="Server limit reached — click to upgrade your plan">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>
                    Upgrade to Add Server
                </a>
            @else
                <a href="{{ route('admin.servers.create') }}" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    {{ __('servers.add_server') }}
                </a>
            @endif
        </div>

        <!-- Server List -->
        @if($servers->isEmpty())
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>
                <h3 class="mt-4 text-base font-medium text-gray-700">{{ __('servers.no_servers_yet') }}</h3>
                <p class="mt-2 text-sm text-gray-500">{{ __('servers.add_first_server') }}</p>
                <a href="{{ route('admin.servers.create') }}" class="mt-6 inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    {{ __('servers.add_server') }}
                </a>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($servers as $server)
                    <a href="{{ route('admin.servers.show', $server) }}" class="flex items-center justify-between px-6 py-4 hover:bg-gray-100 transition">
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
