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
                                        {{-- Restart --}}
                                        <div x-data="{ confirmRestart: false }">
                                            <button type="button" @click="confirmRestart = true" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                                Restart
                                            </button>
                                            <template x-teleport="body">
                                                <div x-show="confirmRestart" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                                                    <div x-show="confirmRestart" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="confirmRestart = false"></div>
                                                    <div class="fixed inset-0 flex items-center justify-center p-4">
                                                        <div x-show="confirmRestart" x-transition @click.stop @keydown.escape.window="confirmRestart = false" class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
                                                            <div class="p-6 pb-0">
                                                                <div class="flex items-start space-x-4">
                                                                    <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
                                                                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                                                    </div>
                                                                    <div>
                                                                        <h3 class="text-lg font-semibold text-gray-900">Restart {{ $service['display_name'] }}</h3>
                                                                        <p class="mt-1 text-sm text-gray-500">This will briefly interrupt the service. Active connections may be dropped.</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-center justify-end space-x-3 px-6 py-5 mt-2">
                                                                <button type="button" @click="confirmRestart = false" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                                                                <form action="{{ route('admin.services.action') }}" method="POST">
                                                                    @csrf
                                                                    <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                                                                    <input type="hidden" name="service" value="{{ $service['name'] }}">
                                                                    <input type="hidden" name="action" value="restart">
                                                                    <button type="submit" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Restart</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- Reload --}}
                                        <form action="{{ route('admin.services.action') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                                            <input type="hidden" name="service" value="{{ $service['name'] }}">
                                            <input type="hidden" name="action" value="reload">
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                                Reload
                                            </button>
                                        </form>

                                        {{-- Stop --}}
                                        @if($service['name'] !== 'opterius-agent')
                                            <div x-data="{ confirmStop: false }">
                                                <button type="button" @click="confirmStop = true" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">
                                                    Stop
                                                </button>
                                                <template x-teleport="body">
                                                    <div x-show="confirmStop" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                                                        <div x-show="confirmStop" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="confirmStop = false"></div>
                                                        <div class="fixed inset-0 flex items-center justify-center p-4">
                                                            <div x-show="confirmStop" x-transition @click.stop @keydown.escape.window="confirmStop = false" class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
                                                                <div class="p-6 pb-0">
                                                                    <div class="flex items-start space-x-4">
                                                                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                                                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                                                                        </div>
                                                                        <div>
                                                                            <h3 class="text-lg font-semibold text-gray-900">Stop {{ $service['display_name'] }}</h3>
                                                                            <p class="mt-1 text-sm text-gray-500">This will stop the service. Hosted sites may become unavailable until the service is started again.</p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="flex items-center justify-end space-x-3 px-6 py-5 mt-2">
                                                                    <button type="button" @click="confirmStop = false" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                                                                    <form action="{{ route('admin.services.action') }}" method="POST">
                                                                        @csrf
                                                                        <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                                                                        <input type="hidden" name="service" value="{{ $service['name'] }}">
                                                                        <input type="hidden" name="action" value="stop">
                                                                        <button type="submit" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">Stop Service</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
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
