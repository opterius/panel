<x-user-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('user.nodejs.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">{{ $nodeApp->name }}</h2>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                @if($nodeApp->status === 'running') bg-green-100 text-green-700
                @elseif($nodeApp->status === 'error') bg-red-100 text-red-700
                @else bg-gray-100 text-gray-600 @endif">
                {{ ucfirst($nodeApp->status) }}
            </span>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    {{-- Controls --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <form action="{{ route('user.nodejs.restart', $nodeApp) }}" method="POST">
            @csrf
            <button type="submit" class="inline-flex items-center px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Restart
            </button>
        </form>

        @if($nodeApp->status === 'running')
            <form action="{{ route('user.nodejs.stop', $nodeApp) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Stop
                </button>
            </form>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        {{-- Details --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-5">App Details</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">PM2 Name</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-800">{{ $nodeApp->pm2Name() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Port</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-800">{{ $nodeApp->port }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Startup Command</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-800">{{ $nodeApp->startup_command }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Working Directory</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-700 break-all">{{ $nodeApp->working_dir }}</dd>
                </div>
                @if($nodeApp->domain)
                    <div>
                        <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Proxied Domain</dt>
                        <dd class="mt-1 text-sm text-gray-800">
                            <a href="https://{{ $nodeApp->domain->domain }}" target="_blank"
                               class="text-green-600 hover:text-green-800">{{ $nodeApp->domain->domain }}</a>
                        </dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Server</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $nodeApp->account->server->name }}</dd>
                </div>
            </dl>
        </div>

        {{-- Danger zone --}}
        <div class="bg-white rounded-xl shadow-sm p-6 border border-red-100">
            <h3 class="text-base font-semibold text-red-600 mb-2">Danger Zone</h3>
            <p class="text-sm text-gray-500 mb-4">Deletes the PM2 process. Your app files are not removed.</p>

            <x-delete-modal
                :action="route('user.nodejs.destroy', $nodeApp)"
                title="Delete App"
                :message="'Delete PM2 process &quot;' . $nodeApp->name . '&quot;? Your app files will remain on disk.'"
                :confirm-password="true">
                <x-slot name="trigger">
                    <button type="button" class="inline-flex items-center px-4 py-2.5 bg-white text-red-600 text-sm font-medium border border-red-300 rounded-lg hover:bg-red-50 transition">
                        Delete App
                    </button>
                </x-slot>
            </x-delete-modal>
        </div>
    </div>

    {{-- Logs --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">Recent Logs</h3>
            <span class="text-xs text-gray-400">Last 100 lines</span>
        </div>
        <pre class="p-4 bg-slate-900 text-slate-300 text-xs font-mono leading-relaxed overflow-x-auto max-h-96 overflow-y-auto whitespace-pre-wrap">{{ $logs ?: 'No log output available.' }}</pre>
    </div>
</x-user-layout>
