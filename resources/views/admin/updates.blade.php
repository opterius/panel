<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Updates</h2>
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

    {{-- ── Panel update ─────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center
                        @if($updateAvailable) bg-amber-100 @else bg-green-100 @endif">
                        @if($updateAvailable)
                            <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        @else
                            <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        @endif
                    </div>
                    <div>
                        <div class="text-lg font-bold text-gray-900">Opterius v{{ $currentVersion }}</div>
                        <div class="text-sm text-gray-500">
                            @if($updateAvailable)
                                <span class="text-amber-600 font-medium">Version {{ $latestVersion }} is available</span>
                            @elseif($latestVersion)
                                <span class="text-green-600 font-medium">You're up to date</span>
                            @else
                                <span class="text-gray-400">Could not check for updates</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @if($updateAvailable)
                        <form action="{{ route('admin.updates.run') }}" method="POST"
                              x-data="{ updating: false }" @submit="updating = true">
                            @csrf
                            <button type="submit" :disabled="updating"
                                class="inline-flex items-center px-5 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <template x-if="!updating">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                </template>
                                <template x-if="updating">
                                    <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                </template>
                                <span x-text="updating ? 'Updating...' : 'Update Opterius'">Update Opterius</span>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('admin.updates.index') }}"
                           class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            Check Again
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($changelog)
        {{-- ── Changelog ──────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">
                    @if($updateAvailable)
                        What's New in v{{ $latestVersion }}
                    @else
                        Current Release Notes (v{{ $latestVersion ?? $currentVersion }})
                    @endif
                </h3>
            </div>
            <div class="px-6 py-5 prose prose-sm max-w-none text-gray-600">
                {!! \Illuminate\Support\Str::markdown($changelog) !!}
            </div>
        </div>
    @endif

    {{-- ── System information ──────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">System Information</h3>
        </div>
        <div class="px-6 py-5">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase">Panel Version</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-800">{{ $currentVersion }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase">Agent Version</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-800">{{ $agentVersion ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase">Laravel</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-800">{{ app()->version() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase">PHP</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-800">{{ PHP_VERSION }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase">Server OS</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-800">
                        @php
                            $osRelease = @file_get_contents('/etc/os-release');
                            $prettyName = 'Unknown';
                            if ($osRelease && preg_match('/^PRETTY_NAME="?([^"\n]+)"?/m', $osRelease, $m)) {
                                $prettyName = $m[1];
                            }
                        @endphp
                        {{ $prettyName }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- ── Update log ───────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden"
         x-data="updateLog()" x-init="init()">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-800">Update Log</h3>
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-400" x-text="lastRefreshed ? 'Last refreshed ' + lastRefreshed : ''"></span>
                <button @click="refresh()"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                    <svg class="w-3.5 h-3.5 mr-1" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    Refresh
                </button>
            </div>
        </div>
        <div class="relative">
            <pre x-ref="logBox"
                 class="text-xs font-mono text-gray-300 bg-gray-900 p-5 overflow-auto max-h-96 whitespace-pre-wrap leading-relaxed"
                 x-text="log || 'No log entries yet. The log appears here after the first update check.'"
            ></pre>
        </div>
    </div>

    {{-- ── Advanced (collapsed) — for troubleshooting only ─────────────── --}}
    <div class="mt-6" x-data="{ open: false }">
        <button type="button" @click="open = !open"
            class="inline-flex items-center text-xs font-medium text-gray-500 hover:text-gray-700 transition">
            <svg class="w-3.5 h-3.5 mr-1 transition-transform" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            Advanced
        </button>
        <div x-show="open" x-collapse class="mt-3 bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="text-xs text-gray-500 mb-3">
                These actions are for troubleshooting only. The main <strong>Update Opterius</strong> button above already updates both the panel and the agent together.
            </p>
            <form action="{{ route('admin.updates.agent') }}" method="POST"
                  x-data="{ busy: false }" @submit="busy = true">
                @csrf
                <button type="submit" :disabled="busy"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition disabled:opacity-50">
                    <svg class="w-3.5 h-3.5 mr-1.5" :class="{ 'animate-spin': busy }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    <span x-text="busy ? 'Triggering...' : 'Force agent binary re-download'">Force agent binary re-download</span>
                </button>
            </form>
        </div>
    </div>

    <script>
    function updateLog() {
        return {
            log: @json($updateLog ?? ''),
            loading: false,
            lastRefreshed: null,

            init() {
                this.scrollToBottom();
                // Auto-refresh every 10 seconds
                setInterval(() => this.refresh(), 10000);
            },

            async refresh() {
                if (this.loading) return;
                this.loading = true;
                try {
                    const res = await fetch('{{ route('admin.updates.log') }}');
                    if (res.ok) {
                        const json = await res.json();
                        this.log = json.log || '';
                        this.lastRefreshed = new Date().toLocaleTimeString();
                        this.$nextTick(() => this.scrollToBottom());
                    }
                } catch (e) {}
                this.loading = false;
            },

            scrollToBottom() {
                const box = this.$refs.logBox;
                if (box) box.scrollTop = box.scrollHeight;
            }
        }
    }
    </script>
</x-admin-layout>
