<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.accounts.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">{{ $account->username }}</h2>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm font-medium text-gray-500">Domains</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $account->domains->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm font-medium text-gray-500">Databases</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $account->databases->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm font-medium text-gray-500">Cron Jobs</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $account->cronJobs->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm font-medium text-gray-500">Disk Quota</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $account->disk_quota > 0 ? ($account->disk_quota >= 1024 ? round($account->disk_quota / 1024, 1) . ' GB' : $account->disk_quota . ' MB') : 'Unlimited' }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Account Details -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-5">Account Details</h3>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Username</dt>
                    <dd class="mt-1 text-sm text-gray-800 font-mono">{{ $account->username }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Server</dt>
                    <dd class="mt-1 text-sm">
                        <a href="{{ route('admin.servers.show', $account->server) }}" class="text-indigo-600 hover:text-indigo-700">{{ $account->server->name }}</a>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Home Directory</dt>
                    <dd class="mt-1 text-sm text-gray-800 font-mono">{{ $account->home_directory }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Created</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $account->created_at->format('M d, Y') }}</dd>
                </div>
            </dl>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">Quick Actions</h3>

            <div class="space-y-3">
                <a href="#" class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    <span class="text-sm font-medium text-gray-700">Add Subdomain</span>
                </a>
                <a href="#" class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    <span class="text-sm font-medium text-gray-700">Create Database</span>
                </a>
                <a href="#" class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    <span class="text-sm font-medium text-gray-700">Add Cron Job</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Domains Table -->
    <div class="bg-white rounded-xl shadow-sm mb-8">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">Domains</h3>
        </div>

        @if($account->domains->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-gray-400">
                No domains added to this account yet.
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($account->domains as $domain)
                    <div class="flex items-center justify-between px-6 py-4">
                        <div>
                            <div class="text-sm font-semibold text-gray-800">{{ $domain->domain }}</div>
                            <div class="text-xs text-gray-500">{{ $domain->document_root }} &middot; PHP {{ $domain->php_version }}</div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                            @if($domain->status === 'active') bg-green-100 text-green-700
                            @elseif($domain->status === 'suspended') bg-amber-100 text-amber-700
                            @elseif($domain->status === 'error') bg-red-100 text-red-700
                            @else bg-gray-100 text-gray-600
                            @endif">
                            {{ ucfirst($domain->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Databases Table -->
    <div class="bg-white rounded-xl shadow-sm mb-8">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">Databases</h3>
        </div>

        @if($account->databases->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-gray-400">
                No databases created for this account yet.
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($account->databases as $database)
                    <div class="flex items-center justify-between px-6 py-4">
                        <div>
                            <div class="text-sm font-semibold text-gray-800 font-mono">{{ $database->name }}</div>
                            <div class="text-xs text-gray-500">User: {{ $database->db_username }}</div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                            @if($database->status === 'active') bg-green-100 text-green-700
                            @else bg-red-100 text-red-700
                            @endif">
                            {{ ucfirst($database->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Danger Zone -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-red-100">
        <h3 class="text-base font-semibold text-red-600 mb-2">Danger Zone</h3>
        <p class="text-sm text-gray-500 mb-4">Deleting this account will remove all associated domains, databases, and cron jobs.</p>

        @if($errors->has('password'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                {{ $errors->first('password') }}
            </div>
        @endif

        <x-delete-modal
            :action="route('admin.accounts.destroy', $account)"
            title="Delete Account"
            message="This will permanently delete the account '{{ $account->username }}' and all associated domains, databases, and cron jobs. The system user will be removed from the server."
            :confirm-password="true">
            <x-slot name="trigger">
                <button type="button" class="inline-flex items-center px-4 py-2.5 bg-white text-red-600 text-sm font-medium border border-red-300 rounded-lg hover:bg-red-50 transition">
                    Delete Account
                </button>
            </x-slot>
        </x-delete-modal>
    </div>
</x-admin-layout>
