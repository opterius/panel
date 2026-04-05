<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Services</h2>
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

    <!-- Server Selector -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">Select Server</h3>
        </div>
        <div class="px-6 py-5">
            <form method="GET" action="{{ route('admin.services.index') }}" class="flex items-end gap-4">
                <div class="flex-1">
                    <label for="server_id" class="block text-sm font-medium text-gray-700 mb-1.5">Server</label>
                    <select name="server_id" id="server_id"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($servers as $server)
                            <option value="{{ $server->id }}" @selected($selectedServer && $selectedServer->id === $server->id)>
                                {{ $server->name }} ({{ $server->ip_address }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    Manage
                </button>
            </form>
        </div>
    </div>

    @if($selectedServer)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Services on {{ $selectedServer->name }}</h3>
                <p class="text-sm text-gray-500 mt-1">Manage system services. Restart, stop, or start services as needed.</p>
            </div>

            @if(empty($services))
                <div class="px-6 py-12 text-center">
                    <p class="text-sm text-gray-500">Could not retrieve services. Check agent connection.</p>
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($services as $service)
                        <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                    @if($service['status'] === 'active') bg-green-100
                                    @elseif($service['status'] === 'inactive') bg-gray-100
                                    @else bg-red-100
                                    @endif">
                                    @if($service['status'] === 'active')
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    @elseif($service['status'] === 'inactive')
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    @else
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                                    @endif
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-800">{{ $service['display_name'] }}</div>
                                    <div class="text-xs text-gray-500">
                                        <span class="font-mono">{{ $service['name'] }}</span>
                                        @if($service['uptime'])
                                            &middot; Since {{ $service['uptime'] }}
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                    @if($service['status'] === 'active') bg-green-100 text-green-700
                                    @elseif($service['status'] === 'inactive') bg-gray-100 text-gray-500
                                    @else bg-red-100 text-red-700
                                    @endif">
                                    {{ ucfirst($service['status']) }}
                                </span>

                                <div class="flex items-center space-x-1">
                                    @if($service['status'] === 'active')
                                        <form action="{{ route('admin.services.action') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                                            <input type="hidden" name="service" value="{{ $service['name'] }}">
                                            <input type="hidden" name="action" value="restart">
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                                Restart
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.services.action') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                                            <input type="hidden" name="service" value="{{ $service['name'] }}">
                                            <input type="hidden" name="action" value="reload">
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                                Reload
                                            </button>
                                        </form>

                                        @if($service['name'] !== 'opterius-agent')
                                            <form action="{{ route('admin.services.action') }}" method="POST"
                                                  x-data="{ confirm: false }">
                                                @csrf
                                                <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                                                <input type="hidden" name="service" value="{{ $service['name'] }}">
                                                <input type="hidden" name="action" value="stop">
                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition"
                                                        onclick="return confirm('Stop {{ $service['display_name'] }}? This may affect hosted sites.')">
                                                    Stop
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <form action="{{ route('admin.services.action') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                                            <input type="hidden" name="service" value="{{ $service['name'] }}">
                                            <input type="hidden" name="action" value="start">
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-600 bg-green-50 rounded-lg hover:bg-green-100 transition">
                                                Start
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</x-admin-layout>
