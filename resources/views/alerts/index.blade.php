<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Alert Rules</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Server Selector -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5">
            <form method="GET" action="{{ route('admin.alerts.index') }}" class="flex items-end gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Server</label>
                    <select name="server_id"
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
        <!-- Create Alert Rule -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Create Alert Rule</h3>
                <p class="text-sm text-gray-500 mt-1">Get notified when a metric exceeds a threshold.</p>
            </div>
            <form action="{{ route('admin.alerts.store') }}" method="POST" class="px-6 py-5"
                  x-data="{ channel: 'email' }">
                @csrf
                <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">

                <div class="grid grid-cols-1 sm:grid-cols-6 gap-4">
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Metric</label>
                        <select name="metric"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="cpu">CPU %</option>
                            <option value="memory">Memory %</option>
                            <option value="disk">Disk %</option>
                            <option value="load">Load Avg</option>
                        </select>
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">When</label>
                        <select name="operator"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value=">">Above (&gt;)</option>
                            <option value="<">Below (&lt;)</option>
                        </select>
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Threshold</label>
                        <input type="number" name="threshold" value="90" min="0" max="100" step="0.1"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">For (min)</label>
                        <input type="number" name="duration_minutes" value="5" min="1" max="60"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Channel</label>
                        <select name="channel" x-model="channel"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="email">Email</option>
                            <option value="telegram">Telegram</option>
                            <option value="slack">Slack</option>
                            <option value="discord">Discord</option>
                        </select>
                    </div>
                    <div class="sm:col-span-1 flex items-end">
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                            Create
                        </button>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        <span x-text="channel === 'email' ? 'Email Address' : channel === 'telegram' ? 'Bot Token:Chat ID' : 'Webhook URL'"></span>
                    </label>
                    <input type="text" name="channel_value"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500"
                        :placeholder="channel === 'email' ? 'admin@example.com' : channel === 'telegram' ? '123456:AABBcc:12345678' : 'https://hooks.slack.com/services/...'"
                        value="{{ auth()->user()->email }}">
                </div>
            </form>
        </div>

        <!-- Existing Rules -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Active Rules</h3>
            </div>

            @if($rules->isEmpty())
                <div class="px-6 py-10 text-center text-sm text-gray-400">
                    No alert rules configured for this server.
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($rules as $rule)
                        <div class="flex items-center justify-between px-6 py-4">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                    @if($rule->enabled) bg-green-100 @else bg-gray-100 @endif">
                                    <svg class="w-5 h-5 @if($rule->enabled) text-green-600 @else text-gray-400 @endif"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-800">
                                        {{ $rule->metricLabel() }} {{ $rule->operator }} {{ $rule->threshold }}%
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        For {{ $rule->duration_minutes }} min → {{ ucfirst($rule->channel) }}
                                        @if($rule->last_triggered_at)
                                            &middot; Last: {{ $rule->last_triggered_at->diffForHumans() }}
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                <form action="{{ route('admin.alerts.toggle', $rule) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-sm font-medium transition
                                        @if($rule->enabled) text-yellow-600 hover:text-yellow-800
                                        @else text-green-600 hover:text-green-800 @endif">
                                        {{ $rule->enabled ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>

                                <form action="{{ route('admin.alerts.destroy', $rule) }}" method="POST"
                                      onsubmit="return confirm('Delete this alert rule?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-400 hover:text-red-600 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Recent Alert History -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Alert History</h3>
            </div>

            @if($recentLogs->isEmpty())
                <div class="px-6 py-10 text-center text-sm text-gray-400">
                    No alerts triggered yet.
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($recentLogs as $log)
                        <div class="flex items-center justify-between px-6 py-3">
                            <div class="text-sm">
                                <span class="font-medium text-gray-800">{{ $log->rule?->metricLabel() ?? $log->metric }}</span>
                                <span class="text-gray-500">{{ $log->value }}% (threshold: {{ $log->threshold }}%)</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    @if($log->status === 'triggered') bg-red-100 text-red-700
                                    @else bg-green-100 text-green-700 @endif">
                                    {{ ucfirst($log->status) }}
                                </span>
                                <span class="text-xs text-gray-400">{{ $log->triggered_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</x-admin-layout>
