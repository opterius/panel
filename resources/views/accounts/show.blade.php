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
            <div class="text-xs font-medium text-gray-400 uppercase">Domains</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $account->domains->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-xs font-medium text-gray-400 uppercase">Databases</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $account->databases->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-xs font-medium text-gray-400 uppercase">Cron Jobs</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $account->cronJobs->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-xs font-medium text-gray-400 uppercase">Disk Used</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">
                @if($stats)
                    {{ number_format($stats['disk_usage']['total_mb'] ?? 0, 1) }} <span class="text-sm font-normal text-gray-400">MB</span>
                @else
                    --
                @endif
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-xs font-medium text-gray-400 uppercase">Bandwidth</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">
                @if($stats)
                    {{ number_format($stats['bandwidth']['total_mb'] ?? 0, 1) }} <span class="text-sm font-normal text-gray-400">MB</span>
                @else
                    --
                @endif
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-xs font-medium text-gray-400 uppercase">Disk Quota</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $account->disk_quota > 0 ? ($account->disk_quota >= 1024 ? round($account->disk_quota / 1024, 1) . ' GB' : $account->disk_quota . ' MB') : 'Unlimited' }}</div>
        </div>
    </div>

    <!-- Disk Usage Breakdown + Account Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Disk Breakdown -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-5">Disk Usage Breakdown</h3>

            @if($stats)
                @php
                    $diskTotal = max($stats['disk_usage']['total_mb'] ?? 0, 0.01);
                    $homePct = ($stats['disk_usage']['home_mb'] ?? 0) / $diskTotal * 100;
                    $emailPct = ($stats['disk_usage']['email_mb'] ?? 0) / $diskTotal * 100;
                    $dbPct = ($stats['database_size_mb'] ?? 0) / $diskTotal * 100;
                @endphp
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Home Directory</span>
                            <span class="font-medium text-gray-800">{{ number_format($stats['disk_usage']['home_mb'] ?? 0, 1) }} MB</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ min($homePct, 100) }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Email</span>
                            <span class="font-medium text-gray-800">{{ number_format($stats['disk_usage']['email_mb'] ?? 0, 1) }} MB</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-orange-500 h-2 rounded-full" style="width: {{ min($emailPct, 100) }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Databases</span>
                            <span class="font-medium text-gray-800">{{ number_format($stats['database_size_mb'] ?? 0, 1) }} MB</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-purple-500 h-2 rounded-full" style="width: {{ min($dbPct, 100) }}%"></div>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-gray-100">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Files (inodes)</span>
                            <span class="font-medium text-gray-800">{{ number_format($stats['inode_count'] ?? 0) }}</span>
                        </div>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-400">Stats unavailable. Check agent connection.</p>
            @endif
        </div>

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
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">PHP Version</dt>
                    <dd class="mt-1 text-sm text-gray-800">PHP {{ $account->php_version }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">SSH Access</dt>
                    <dd class="mt-1 text-sm">
                        @if($account->ssh_enabled)
                            <span class="text-green-600 font-medium">Enabled</span>
                        @else
                            <span class="text-gray-400">Disabled</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Bandwidth per Domain -->
    @if($stats && !empty($stats['bandwidth']['domains']))
        <div class="bg-white rounded-xl shadow-sm mb-8">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Bandwidth per Domain</h3>
                <p class="text-sm text-gray-500 mt-1">Traffic from Nginx access logs.</p>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($stats['bandwidth']['domains'] as $domain => $bw)
                    <div class="flex items-center justify-between px-6 py-3">
                        <div class="text-sm font-medium text-gray-800">{{ $domain }}</div>
                        <div class="flex items-center space-x-6 text-sm text-gray-500">
                            <span>{{ number_format($bw['request_count'] ?? 0) }} requests</span>
                            <span class="font-medium text-gray-800">{{ number_format($bw['bytes_mb'] ?? 0, 1) }} MB</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

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

    <!-- Suspend -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-8 border @if($account->suspended) border-amber-200 @else border-gray-200 @endif">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold @if($account->suspended) text-amber-600 @else text-gray-800 @endif">
                    @if($account->suspended)
                        Account Suspended
                        @if($account->suspended_at) <span class="text-sm font-normal text-gray-400">since {{ $account->suspended_at->diffForHumans() }}</span> @endif
                    @else
                        Suspend Account
                    @endif
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    @if($account->suspended)
                        All domains show a suspended page. Email and SSH are disabled.
                    @else
                        Suspend this account for non-payment or abuse. All domains will show a suspended page.
                    @endif
                </p>
            </div>
            <form action="{{ route('admin.accounts.suspend', $account) }}" method="POST">
                @csrf
                @if($account->suspended)
                    <button type="submit" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-green-600 bg-white border border-green-300 rounded-lg hover:bg-green-50 transition">
                        Unsuspend
                    </button>
                @else
                    <button type="submit" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-amber-600 bg-white border border-amber-300 rounded-lg hover:bg-amber-50 transition"
                            onclick="return confirm('Suspend account {{ $account->username }}? All sites will be taken offline.')">
                        Suspend
                    </button>
                @endif
            </form>
        </div>
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
            message="This will permanently delete the account '{{ $account->username }}' and all associated domains, databases, and cron jobs."
            :confirm-password="true">
            <x-slot name="trigger">
                <button type="button" class="inline-flex items-center px-4 py-2.5 bg-white text-red-600 text-sm font-medium border border-red-300 rounded-lg hover:bg-red-50 transition">
                    Delete Account
                </button>
            </x-slot>
        </x-delete-modal>
    </div>
</x-admin-layout>
