<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">SSL Overview</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <!-- Server Selector (only if multiple servers) -->
    @if($servers->count() > 1)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5">
                <form method="GET" class="flex items-end gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Server</label>
                        <select name="server_id" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($servers as $server)
                                <option value="{{ $server->id }}" @selected($selectedServer && $selectedServer->id === $server->id)>{{ $server->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if(!$selectedServer)
        <div class="bg-white rounded-xl shadow-sm px-6 py-16 text-center">
            <p class="text-sm text-gray-500">No server selected.</p>
        </div>
    @else
        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="text-xs text-gray-500 uppercase font-medium">Total</div>
                <div class="mt-1 text-2xl font-bold text-gray-800">{{ $stats['total'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-green-400">
                <div class="text-xs text-gray-500 uppercase font-medium">Active</div>
                <div class="mt-1 text-2xl font-bold text-green-600">{{ $stats['active'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-amber-400">
                <div class="text-xs text-gray-500 uppercase font-medium">Pending</div>
                <div class="mt-1 text-2xl font-bold text-amber-600">{{ $stats['pending'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-red-400">
                <div class="text-xs text-gray-500 uppercase font-medium">Failed</div>
                <div class="mt-1 text-2xl font-bold text-red-600">{{ $stats['failed'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-gray-400">
                <div class="text-xs text-gray-500 uppercase font-medium">Missing</div>
                <div class="mt-1 text-2xl font-bold text-gray-600">{{ $stats['missing'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-orange-400">
                <div class="text-xs text-gray-500 uppercase font-medium">Expiring &lt;30d</div>
                <div class="mt-1 text-2xl font-bold text-orange-600">{{ $stats['expiring'] }}</div>
            </div>
        </div>

        {{-- Auto-SSL Setting --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Auto-SSL</h3>
                    <p class="text-sm text-gray-500 mt-1">Automatically issue Let's Encrypt certificates when new domains and subdomains are created.</p>
                </div>
                <form action="{{ route('admin.ssl-overview.toggle-auto') }}" method="POST">
                    @csrf
                    <input type="hidden" name="enabled" value="{{ $autoSslEnabled ? '0' : '1' }}">
                    <button type="submit" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $autoSslEnabled ? 'bg-indigo-600' : 'bg-gray-200' }}">
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $autoSslEnabled ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Bulk Actions --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Re-check Missing Certificates</h3>
                    <p class="text-sm text-gray-500 mt-1">Issues Let's Encrypt certificates only for domains/subdomains <strong>without a valid cert</strong>. Active certs are skipped to avoid Let's Encrypt rate limits.</p>
                </div>
                <form action="{{ route('admin.ssl-overview.recheck') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                    <button type="submit" :disabled="loading"
                        class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="!loading" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <svg x-show="loading" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="loading ? 'Checking...' : 'Re-check Missing'">Re-check Missing</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Domain List Grouped by Account --}}
        @if($accounts->isEmpty())
            <div class="bg-white rounded-xl shadow-sm px-6 py-16 text-center">
                <p class="text-sm text-gray-500">No accounts on this server yet.</p>
            </div>
        @else
            @php
                $renderRow = function ($d, $isSub = false) {
                    $cert = $d->sslCertificate;
                    $status = $cert?->status ?? 'none';
                    $statusColor = match($status) {
                        'active'  => 'bg-green-100 text-green-700',
                        'pending' => 'bg-amber-100 text-amber-700',
                        'error', 'failed' => 'bg-red-100 text-red-700',
                        default   => 'bg-gray-100 text-gray-500',
                    };
                    $statusLabel = match($status) {
                        'active'  => 'Active',
                        'pending' => 'Pending',
                        'error', 'failed' => 'Failed',
                        default   => 'Missing',
                    };
                    $expiringSoon = $cert && $cert->status === 'active' && $cert->expires_at && $cert->expires_at->diffInDays(now(), false) > -30;
                    return compact('cert', 'status', 'statusColor', 'statusLabel', 'expiringSoon');
                };
            @endphp

            <div class="space-y-5">
                @foreach($accounts as $account)
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                    <span class="text-xs font-bold text-indigo-600">{{ strtoupper(substr($account->username, 0, 2)) }}</span>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-800 font-mono">{{ $account->username }}</div>
                                    <div class="text-xs text-gray-500">{{ $account->user?->email ?? 'no owner' }}</div>
                                </div>
                            </div>
                            <a href="{{ route('admin.accounts.show', $account) }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">View Account →</a>
                        </div>

                        <div class="divide-y divide-gray-50">
                            @foreach($account->domains as $domain)
                                @php $info = $renderRow($domain); @endphp
                                <div class="px-6 py-3 flex items-center justify-between">
                                    <div class="flex items-center space-x-3 min-w-0 flex-1">
                                        @if($info['status'] === 'active')
                                            <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                        @else
                                            <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm font-medium text-gray-800 truncate">{{ $domain->domain }}</div>
                                            @if($info['cert'] && $info['cert']->expires_at && $info['status'] === 'active')
                                                <div class="text-xs {{ $info['expiringSoon'] ? 'text-orange-600' : 'text-gray-400' }}">
                                                    Expires {{ $info['cert']->expires_at->format('M d, Y') }}
                                                    @if($info['expiringSoon']) (soon!) @endif
                                                </div>
                                            @endif
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $info['statusColor'] }}">
                                            {{ $info['statusLabel'] }}
                                        </span>
                                    </div>
                                </div>

                                @foreach($domain->subdomains as $sub)
                                    @php $subInfo = $renderRow($sub, true); @endphp
                                    <div class="pl-12 pr-6 py-2 flex items-center justify-between bg-gray-50/50">
                                        <div class="flex items-center space-x-3 min-w-0 flex-1">
                                            @if($subInfo['status'] === 'active')
                                                <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                            @else
                                                <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                            @endif
                                            <div class="text-sm text-gray-700 truncate min-w-0 flex-1">{{ $sub->domain }}</div>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $subInfo['statusColor'] }}">
                                                {{ $subInfo['statusLabel'] }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</x-admin-layout>
