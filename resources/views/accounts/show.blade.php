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

    @if(session('warning'))
        <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg text-sm">
            {{ session('warning') }}
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-xs font-medium text-gray-400 uppercase">{{ __('domains.domains') }}</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $account->domains->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-xs font-medium text-gray-400 uppercase">{{ __('accounts.databases') }}</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $account->databases->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-xs font-medium text-gray-400 uppercase">{{ __('accounts.cron_jobs') }}</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $account->cronJobs->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-xs font-medium text-gray-400 uppercase">{{ __('accounts.disk_used') }}</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">
                @if($stats)
                    {{ number_format($stats['disk_usage']['total_mb'] ?? 0, 1) }} <span class="text-sm font-normal text-gray-400">{{ __('common.mb') }}</span>
                @else
                    --
                @endif
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-xs font-medium text-gray-400 uppercase">{{ __('accounts.bandwidth') }}</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">
                @if($stats)
                    {{ number_format($stats['bandwidth']['total_mb'] ?? 0, 1) }} <span class="text-sm font-normal text-gray-400">{{ __('common.mb') }}</span>
                @else
                    --
                @endif
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-xs font-medium text-gray-400 uppercase">{{ __('accounts.disk_quota') }}</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $account->disk_quota > 0 ? ($account->disk_quota >= 1024 ? round($account->disk_quota / 1024, 1) . ' GB' : $account->disk_quota . ' MB') : __('common.unlimited') }}</div>
        </div>
    </div>

    <!-- Disk Usage Breakdown + Account Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Disk Breakdown -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-5">{{ __('accounts.disk_usage_breakdown') }}</h3>

            @if($stats)
                @php
                    // Use disk quota as the reference. If unlimited (0), use 10GB as visual reference.
                    $quotaMb = $account->disk_quota > 0 ? $account->disk_quota : 10240;
                    $homePct = min(100, ($stats['disk_usage']['home_mb'] ?? 0) / $quotaMb * 100);
                    $emailPct = min(100, ($stats['disk_usage']['email_mb'] ?? 0) / $quotaMb * 100);
                    $dbPct = min(100, ($stats['database_size_mb'] ?? 0) / $quotaMb * 100);
                @endphp
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">{{ __('accounts.home_directory') }}</span>
                            <span class="font-medium text-gray-800">{{ number_format($stats['disk_usage']['home_mb'] ?? 0, 1) }} {{ __('common.mb') }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ min($homePct, 100) }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">{{ __('common.email_label') }}</span>
                            <span class="font-medium text-gray-800">{{ number_format($stats['disk_usage']['email_mb'] ?? 0, 1) }} {{ __('common.mb') }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-orange-500 h-2 rounded-full" style="width: {{ min($emailPct, 100) }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">{{ __('accounts.databases') }}</span>
                            <span class="font-medium text-gray-800">{{ number_format($stats['database_size_mb'] ?? 0, 1) }} {{ __('common.mb') }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-purple-500 h-2 rounded-full" style="width: {{ min($dbPct, 100) }}%"></div>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-gray-100">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ __('accounts.files_inodes') }}</span>
                            <span class="font-medium text-gray-800">{{ number_format($stats['inode_count'] ?? 0) }}</span>
                        </div>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-400">{{ __('accounts.stats_unavailable') }}</p>
            @endif
        </div>

        <!-- Account Details -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-5">{{ __('accounts.account_details') }}</h3>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('accounts.username') }}</dt>
                    <dd class="mt-1 text-sm text-gray-800 font-mono">{{ $account->username }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('accounts.server') }}</dt>
                    <dd class="mt-1 text-sm">
                        <a href="{{ route('admin.servers.show', $account->server) }}" class="text-indigo-600 hover:text-indigo-700">{{ $account->server->name }}</a>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('accounts.home_directory') }}</dt>
                    <dd class="mt-1 text-sm text-gray-800 font-mono">{{ $account->home_directory }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('common.created_at') }}</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $account->created_at->format('M d, Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('accounts.php_version') }}</dt>
                    <dd class="mt-1 text-sm text-gray-800">PHP {{ $account->php_version }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('accounts.ssh_access') }}</dt>
                    <dd class="mt-1 text-sm">
                        @if($account->ssh_enabled)
                            <span class="text-green-600 font-medium">{{ __('common.enabled') }}</span>
                        @else
                            <span class="text-gray-400">{{ __('common.disabled') }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Account Owner -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-8" x-data="{ editing: false }">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-800">{{ __('accounts.account_owner') }}</h3>
                <div class="mt-2 flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                        <span class="text-sm font-bold text-indigo-600">{{ strtoupper(substr($account->user->name, 0, 2)) }}</span>
                    </div>
                    <div x-show="!editing">
                        <div class="text-sm font-semibold text-gray-800">{{ $account->user->name }}</div>
                        <div class="text-xs text-gray-500">{{ $account->user->email }}</div>
                    </div>
                </div>
            </div>
            <button type="button" @click="editing = !editing" x-show="!editing"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-800 transition">
                {{ __('common.edit') }}
            </button>
        </div>

        <div x-show="editing" x-collapse class="mt-4">
            <form action="{{ route('admin.accounts.update-owner', $account) }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('common.name') }}</label>
                        <input type="text" name="name" value="{{ $account->user->name }}"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('common.email') }}</label>
                        <input type="email" name="email" value="{{ $account->user->email }}"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('accounts.new_password') }} <span class="text-gray-400 font-normal">{{ __('accounts.leave_empty_to_keep') }}</span></label>
                    <input type="password" name="password" placeholder="{{ __('accounts.leave_empty_placeholder') }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="flex items-center space-x-3">
                    <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                        {{ __('accounts.save_changes') }}
                    </button>
                    <button type="button" @click="editing = false" class="text-sm text-gray-500 hover:text-gray-700 transition">
                        {{ __('common.cancel') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Team Access -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-800">{{ __('accounts.team_access') }}</h3>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $account->collaborators()->count() }} {{ __('accounts.collaborator') }}{{ $account->collaborators()->count() !== 1 ? 's' : '' }}
                    &middot; {{ __('accounts.owner_label') }} {{ $account->user->email }}
                </p>
            </div>
            <a href="{{ route('admin.collaborators.index', $account) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-600 bg-white border border-indigo-300 rounded-lg hover:bg-indigo-50 transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                {{ __('accounts.manage_team') }}
            </a>
        </div>
    </div>

    <!-- Bandwidth per Domain -->
    @if($stats && !empty($stats['bandwidth']['domains']))
        <div class="bg-white rounded-xl shadow-sm mb-8">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">{{ __('accounts.bandwidth_per_domain') }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ __('accounts.traffic_from_nginx') }}</p>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($stats['bandwidth']['domains'] as $domain => $bw)
                    <div class="flex items-center justify-between px-6 py-3">
                        <div class="text-sm font-medium text-gray-800">{{ $domain }}</div>
                        <div class="flex items-center space-x-6 text-sm text-gray-500">
                            <span>{{ number_format($bw['request_count'] ?? 0) }} {{ __('accounts.requests') }}</span>
                            <span class="font-medium text-gray-800">{{ number_format($bw['bytes_mb'] ?? 0, 1) }} {{ __('common.mb') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Domains Table -->
    <div class="bg-white rounded-xl shadow-sm mb-8">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">{{ __('accounts.all_domains') }}</h3>
        </div>

        @if($account->domains->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-gray-400">
                {{ __('accounts.no_domains_added') }}
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
            <h3 class="text-base font-semibold text-gray-800">{{ __('accounts.databases') }}</h3>
        </div>

        @if($account->databases->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-gray-400">
                {{ __('accounts.no_databases_created') }}
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

    <!-- Suspend -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-8 border @if($account->suspended) border-amber-200 @else border-gray-200 @endif">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold @if($account->suspended) text-amber-600 @else text-gray-800 @endif">
                    @if($account->suspended)
                        {{ __('accounts.account_suspended_heading') }}
                        @if($account->suspended_at) <span class="text-sm font-normal text-gray-400">since {{ $account->suspended_at->diffForHumans() }}</span> @endif
                    @else
                        {{ __('accounts.suspend_account') }}
                    @endif
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    @if($account->suspended)
                        {{ __('accounts.all_domains_show_suspended_page') }}
                    @else
                        {{ __('accounts.suspend_description') }}
                    @endif
                </p>
            </div>
            @if($account->suspended)
                <x-delete-modal
                    :action="route('admin.accounts.suspend', $account)"
                    title="{{ __('accounts.unsuspend_account') }}"
                    message="{{ __('accounts.restore_domains_email_ssh', ['username' => $account->username]) }}"
                    :confirm-password="true">
                    <x-slot name="trigger">
                        <button type="button" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-green-600 bg-white border border-green-300 rounded-lg hover:bg-green-50 transition">
                            {{ __('accounts.unsuspend') }}
                        </button>
                    </x-slot>
                </x-delete-modal>
            @else
                <x-delete-modal
                    :action="route('admin.accounts.suspend', $account)"
                    title="{{ __('accounts.suspend_account') }}"
                    message="{{ __('accounts.takes_all_domains_offline', ['username' => $account->username]) }}"
                    :confirm-password="true">
                    <x-slot name="trigger">
                        <button type="button" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-amber-600 bg-white border border-amber-300 rounded-lg hover:bg-amber-50 transition">
                            {{ __('accounts.suspend') }}
                        </button>
                    </x-slot>
                </x-delete-modal>
            @endif
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-red-100">
        <h3 class="text-base font-semibold text-red-600 mb-2">{{ __('common.danger_zone') }}</h3>
        <p class="text-sm text-gray-500 mb-4">{{ __('accounts.deleting_removes_domains_databases') }}</p>

        @if($errors->has('password'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                {{ $errors->first('password') }}
            </div>
        @endif

        <x-delete-modal
            :action="route('admin.accounts.destroy', $account)"
            title="{{ __('accounts.delete_account') }}"
            message="{{ __('accounts.permanently_delete_account', ['username' => $account->username]) }}"
            :confirm-password="true">
            <x-slot name="trigger">
                <button type="button" class="inline-flex items-center px-4 py-2.5 bg-white text-red-600 text-sm font-medium border border-red-300 rounded-lg hover:bg-red-50 transition">
                    {{ __('accounts.delete_account') }}
                </button>
            </x-slot>
        </x-delete-modal>
    </div>
</x-admin-layout>
