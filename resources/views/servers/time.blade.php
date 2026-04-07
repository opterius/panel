<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.servers.show', $server) }}" class="text-gray-400 hover:text-gray-600 transition" title="{{ __('server-time.back_to_server') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">{{ $server->name }} — {{ __('server-time.page_title') }}</h2>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    @if(!$reachable)
        {{-- Agent unreachable: short-circuit and show a friendly explanation. --}}
        <div class="bg-white rounded-xl shadow-sm p-12 text-center">
            <svg class="mx-auto w-12 h-12 text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <h3 class="text-base font-medium text-gray-800 mb-1">{{ __('server-time.agent_unreachable_title') }}</h3>
            <p class="text-sm text-gray-500 max-w-md mx-auto">{{ __('server-time.agent_unreachable_text', ['port' => config('opterius.agent_port', 7443)]) }}</p>
        </div>
    @else

    <div x-data="serverTimeClock(@js($time['unix_seconds'] ?? 0), @js($time['timezone'] ?? 'UTC'))"
         x-init="start()"
         class="space-y-6">

        {{-- Big live clock + status card --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-8 sm:px-10 sm:py-12 bg-gradient-to-br from-slate-50 to-white">
                <div class="text-center">
                    <div class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-2">{{ __('server-time.current_time') }}</div>
                    <div class="text-5xl sm:text-6xl font-extrabold text-gray-900 tabular-nums" x-text="display"></div>
                    <div class="mt-3 text-sm text-gray-500">
                        <span class="font-mono">{{ $time['timezone'] ?? '—' }}</span>
                        @if(!empty($time['ntp_synced']))
                            <span class="inline-flex items-center gap-1 ml-3 px-2.5 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                {{ __('server-time.ntp_synced') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 ml-3 px-2.5 py-1 bg-amber-100 text-amber-700 text-xs font-medium rounded-full">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                {{ __('server-time.ntp_not_synced') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Bottom strip: NTP details --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 border-t border-gray-100">
                <div class="px-6 py-5">
                    <div class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('server-time.timezone_label') }}</div>
                    <div class="mt-1 text-sm text-gray-800 font-mono">{{ $time['timezone'] ?? '—' }}</div>
                </div>
                <div class="px-6 py-5">
                    <div class="text-xs font-medium text-gray-400 uppercase tracking-wide">
                        @if(isset($time['ntp_offset_ms']))
                            {{ __('server-time.ntp_offset', ['ms' => $time['ntp_offset_ms']]) }}
                        @else
                            {{ __('server-time.ntp_offset_unknown') }}
                        @endif
                    </div>
                    <div class="mt-1 text-sm text-gray-800">
                        @if(!empty($time['ntp_servers']))
                            <span class="font-mono text-xs text-gray-600">{{ implode(', ', array_slice($time['ntp_servers'], 0, 3)) }}</span>
                        @else
                            <span class="text-gray-400">{{ __('server-time.ntp_no_servers') }}</span>
                        @endif
                    </div>
                </div>
                <div class="px-6 py-5">
                    <div class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('server-time.last_sync_label') }}</div>
                    <div class="mt-1 text-sm text-gray-800">{{ $time['ntp_last_sync'] ?? __('server-time.unknown') }}</div>
                </div>
            </div>
        </div>

        {{-- Two side-by-side cards: Sync now + Change timezone --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Sync Now --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-2">{{ __('server-time.sync_now') }}</h3>
                <p class="text-sm text-gray-500 mb-5">{{ __('server-time.sync_now_desc') }}</p>
                <form action="{{ route('admin.servers.time.sync', $server) }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    <button type="submit" :disabled="loading"
                            class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="!loading" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <svg x-show="loading" x-cloak class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="loading ? '...' : '{{ __('server-time.sync_now') }}'">{{ __('server-time.sync_now') }}</span>
                    </button>
                </form>
            </div>

            {{-- Change Timezone --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-2">{{ __('server-time.change_timezone') }}</h3>
                <p class="text-sm text-gray-500 mb-5">{{ __('server-time.change_timezone_desc') }}</p>

                <form action="{{ route('admin.servers.time.timezone', $server) }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    <label for="timezone" class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">{{ __('server-time.timezone_select') }}</label>
                    <select name="timezone" id="timezone"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($timezones as $region => $zones)
                            <optgroup label="{{ $region }}">
                                @foreach($zones as $tz)
                                    <option value="{{ $tz }}" @selected(($time['timezone'] ?? '') === $tz)>{{ $tz }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <button type="submit" :disabled="loading"
                            class="mt-4 inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="loading" x-cloak class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="loading ? '...' : '{{ __('server-time.timezone_save') }}'">{{ __('server-time.timezone_save') }}</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- "Why this matters" explainer --}}
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 text-sm text-slate-600">
            <div class="font-semibold text-slate-700 mb-1">{{ __('server-time.why_matters_title') }}</div>
            <p>{{ __('server-time.why_matters_text') }}</p>
        </div>
    </div>

    {{-- Live JS clock that ticks once per second based on the agent's reported
         unix timestamp + browser drift offset. Re-rendered server-side on each
         page load, so the user always sees the actual server time, not their
         own browser time. --}}
    <script>
        function serverTimeClock(serverUnixSeconds, timezone) {
            return {
                display: '—',
                _offsetMs: 0,
                start() {
                    if (!serverUnixSeconds) {
                        this.display = 'unknown';
                        return;
                    }
                    // Compute the offset between this browser's clock and the
                    // server's clock at page load. From now on we tick the
                    // browser clock and apply the offset.
                    this._offsetMs = (serverUnixSeconds * 1000) - Date.now();
                    this.tick();
                    setInterval(() => this.tick(), 1000);
                },
                tick() {
                    const serverNow = new Date(Date.now() + this._offsetMs);
                    try {
                        this.display = serverNow.toLocaleString('en-GB', {
                            timeZone: timezone,
                            year: 'numeric', month: '2-digit', day: '2-digit',
                            hour: '2-digit', minute: '2-digit', second: '2-digit',
                            hour12: false,
                        });
                    } catch (e) {
                        // If the browser doesn't recognize the timezone, fall
                        // back to UTC so the clock still ticks.
                        this.display = serverNow.toISOString().replace('T', ' ').split('.')[0];
                    }
                },
            };
        }
    </script>

    @endif
</x-admin-layout>
