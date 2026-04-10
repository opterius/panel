<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.servers.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">{{ $server->name }}</h2>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                @if($server->status === 'online') bg-green-100 text-green-700
                @elseif($server->status === 'offline') bg-red-100 text-red-700
                @elseif($server->status === 'error') bg-red-100 text-red-700
                @else bg-gray-100 text-gray-600
                @endif">
                {{ ucfirst($server->status) }}
            </span>
        </div>
    </x-slot>

    <!-- Server Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm font-medium text-gray-500">{{ __('servers.domains') }}</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $server->domains->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm font-medium text-gray-500">{{ __('servers.accounts') }}</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $server->accounts->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm font-medium text-gray-500">{{ __('servers.databases') }}</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $server->databases->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm font-medium text-gray-500">{{ __('servers.cron_jobs') }}</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $server->cronJobs->count() }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Server Details -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-5">{{ __('servers.server_details') }}</h3>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('servers.server_name') }}</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $server->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('servers.ip_address') }}</dt>
                    <dd class="mt-1 text-sm text-gray-800 font-mono">{{ $server->ip_address }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('servers.hostname') }}</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $server->hostname ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('servers.operating_system') }}</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $server->os ? $server->os . ' ' . $server->os_version : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('servers.agent_url') }}</dt>
                    <dd class="mt-1 text-sm text-gray-800 font-mono">{{ $server->agent_url ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('servers.last_ping') }}</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $server->last_ping_at ? $server->last_ping_at->diffForHumans() : __('common.never') }}</dd>
                </div>
            </dl>
        </div>

        {{-- Only show "Install Agent" for remote servers — the local server
             already has the agent installed by the installer. --}}
        @if(! str_contains($server->agent_url ?? '', '127.0.0.1'))
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-3">{{ __('servers.install_agent') }}</h3>
            <p class="text-sm text-gray-500 mb-4">{{ __('servers.install_agent_description') }}</p>

            <div class="relative">
                <pre class="bg-gray-900 text-green-400 rounded-lg p-4 text-xs font-mono overflow-x-auto">curl -sL https://get.opterius.com/agent | bash -s -- --token={{ $server->agent_token }}</pre>
            </div>

            <p class="text-xs text-gray-400 mt-3">{{ __('servers.install_agent_note') }}</p>
        </div>
        @endif
    </div>

    <!-- System tools -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
        <h3 class="text-base font-semibold text-gray-800 mb-4">{{ __('servers.system_tools') }}</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <a href="{{ route('admin.servers.time', $server) }}"
               class="flex items-center gap-3 p-4 rounded-xl border border-gray-100 hover:border-indigo-200 hover:bg-indigo-50/30 transition group">
                <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center group-hover:bg-indigo-100 transition shrink-0">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-gray-800">{{ __('servers.server_time') }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ __('servers.server_time_desc') }}</div>
                </div>
            </a>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-red-100">
        <h3 class="text-base font-semibold text-red-600 mb-2">{{ __('common.danger_zone') }}</h3>
        <p class="text-sm text-gray-500 mb-4">{{ __('servers.remove_server_description') }}</p>

        @if($errors->has('password'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                {{ $errors->first('password') }}
            </div>
        @endif

        <x-delete-modal
            :action="route('admin.servers.destroy', $server)"
            :title="__('servers.remove_server')"
            :message="__('servers.remove_server_message')"
            :confirm-password="true">
            <x-slot name="trigger">
                <button type="button" class="inline-flex items-center px-4 py-2.5 bg-white text-red-600 text-sm font-medium border border-red-300 rounded-lg hover:bg-red-50 transition">
                    {{ __('servers.remove_server') }}
                </button>
            </x-slot>
        </x-delete-modal>
    </div>
</x-admin-layout>
