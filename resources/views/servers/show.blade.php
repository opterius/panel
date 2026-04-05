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
            <div class="text-sm font-medium text-gray-500">Domains</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $server->domains->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm font-medium text-gray-500">Accounts</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $server->accounts->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm font-medium text-gray-500">Databases</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $server->databases->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm font-medium text-gray-500">Cron Jobs</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $server->cronJobs->count() }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Server Details -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-5">Server Details</h3>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Name</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $server->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">IP Address</dt>
                    <dd class="mt-1 text-sm text-gray-800 font-mono">{{ $server->ip_address }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Hostname</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $server->hostname ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Operating System</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $server->os ? $server->os . ' ' . $server->os_version : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Agent URL</dt>
                    <dd class="mt-1 text-sm text-gray-800 font-mono">{{ $server->agent_url ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Last Ping</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $server->last_ping_at ? $server->last_ping_at->diffForHumans() : 'Never' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Agent Install -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-3">Install Agent</h3>
            <p class="text-sm text-gray-500 mb-4">Run this command on your server to install and connect the Opterius agent:</p>

            <div class="relative">
                <pre class="bg-gray-900 text-green-400 rounded-lg p-4 text-xs font-mono overflow-x-auto">curl -sL https://get.opterius.com/agent | bash -s -- --token={{ $server->agent_token }}</pre>
            </div>

            <p class="text-xs text-gray-400 mt-3">The agent runs on port 7443 and communicates securely with this panel.</p>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-red-100">
        <h3 class="text-base font-semibold text-red-600 mb-2">Danger Zone</h3>
        <p class="text-sm text-gray-500 mb-4">Removing a server will disconnect it from Opterius. This does not delete anything on the server itself.</p>

        @if($errors->has('password'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                {{ $errors->first('password') }}
            </div>
        @endif

        <x-delete-modal
            :action="route('admin.servers.destroy', $server)"
            title="Remove Server"
            message="This will disconnect the server from Opterius. All associated domains, databases, and accounts will be removed from the panel. Nothing on the server itself will be deleted."
            :confirm-password="true">
            <x-slot name="trigger">
                <button type="button" class="inline-flex items-center px-4 py-2.5 bg-white text-red-600 text-sm font-medium border border-red-300 rounded-lg hover:bg-red-50 transition">
                    Remove Server
                </button>
            </x-slot>
        </x-delete-modal>
    </div>
</x-admin-layout>
