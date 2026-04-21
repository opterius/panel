<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
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
            <a href="{{ route('admin.servers.edit', $server) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                {{ __('servers.edit_server') }}
            </a>
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

        @if($server->status === 'online')
            {{-- Agent is connected — show a confirmation card with an
                 expandable "reinstall / re-connect agent" section only for admins
                 who may need to recover after a failure. --}}
            <div class="bg-white rounded-xl shadow-sm p-6" x-data="{ showReinstall: false, showToken: false, copied: false }">
                <div class="flex items-start gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Agent connected</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            The Opterius agent on this server is communicating with the panel. No action required.
                            @if($server->last_ping_at)
                                Last ping {{ $server->last_ping_at->diffForHumans() }}.
                            @endif
                        </p>
                    </div>
                </div>

                <button type="button" @click="showReinstall = !showReinstall"
                    class="text-xs font-medium text-gray-500 hover:text-gray-700 transition inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-90': showReinstall }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    Reinstall / reconnect agent
                </button>

                <div x-show="showReinstall" x-collapse class="mt-3 pt-3 border-t border-gray-100 space-y-3">
                    <p class="text-xs text-gray-500">Only use this if the agent stopped responding and a fresh install is needed.</p>

                    <div>
                        <div class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1.5">One-line install command</div>
                        <pre class="bg-gray-900 text-green-400 rounded-lg p-3 text-xs font-mono overflow-x-auto whitespace-pre-wrap break-all">curl -sL https://get.opterius.com/agent | bash -s -- --token={{ $server->agent_token }}</pre>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="text-xs font-medium text-gray-400 uppercase tracking-wide">Agent token</div>
                            <button type="button" @click="showToken = !showToken" class="text-xs text-indigo-600 hover:text-indigo-800" x-text="showToken ? 'Hide' : 'Show'"></button>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" readonly
                                :value="showToken ? '{{ $server->agent_token }}' : '••••••••••••••••••••••••••••••••'"
                                class="flex-1 min-w-0 bg-gray-50 border border-gray-200 rounded-md px-3 py-1.5 text-xs font-mono text-gray-700 focus:outline-none">
                            <button type="button"
                                @click="navigator.clipboard.writeText('{{ $server->agent_token }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition">
                                <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <svg x-show="copied" class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span x-text="copied ? 'Copied' : 'Copy'"></span>
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1.5">Shared secret the agent uses to authenticate with the panel. Keep it private.</p>
                    </div>
                </div>
            </div>
        @else
            {{-- Agent has never connected or is offline — install instructions
                 need to be prominent so the admin can get going. --}}
            <div class="bg-white rounded-xl shadow-sm p-6" x-data="{ showToken: false, copied: false }">
                <div class="flex items-start gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">{{ __('servers.install_agent') }}</h3>
                        <p class="text-sm text-gray-500 mt-0.5">{{ __('servers.install_agent_description') }}</p>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1.5">One-line install command</div>
                    <pre class="bg-gray-900 text-green-400 rounded-lg p-3 text-xs font-mono overflow-x-auto whitespace-pre-wrap break-all">curl -sL https://get.opterius.com/agent | bash -s -- --token={{ $server->agent_token }}</pre>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="text-xs font-medium text-gray-400 uppercase tracking-wide">Agent token</div>
                        <button type="button" @click="showToken = !showToken" class="text-xs text-indigo-600 hover:text-indigo-800" x-text="showToken ? 'Hide' : 'Show'"></button>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly
                            :value="showToken ? '{{ $server->agent_token }}' : '••••••••••••••••••••••••••••••••'"
                            class="flex-1 min-w-0 bg-gray-50 border border-gray-200 rounded-md px-3 py-1.5 text-xs font-mono text-gray-700 focus:outline-none">
                        <button type="button"
                            @click="navigator.clipboard.writeText('{{ $server->agent_token }}'); copied = true; setTimeout(() => copied = false, 1500)"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition">
                            <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <svg x-show="copied" class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span x-text="copied ? 'Copied' : 'Copy'"></span>
                        </button>
                    </div>
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
