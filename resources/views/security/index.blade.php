<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Security</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <!-- Server Selector -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5">
            <form method="GET" action="{{ route('admin.security.index') }}" class="flex items-end gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Server</label>
                    <select name="server_id" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($servers as $server)
                            <option value="{{ $server->id }}" @selected($selectedServer && $selectedServer->id === $server->id)>{{ $server->name }} ({{ $server->ip_address }})</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">Manage</button>
            </form>
        </div>
    </div>

    @if($selectedServer)
        <!-- Malware Scanner -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Malware Scanner</h3>
                <p class="text-sm text-gray-500 mt-1">Scan hosting accounts for known malware patterns and backdoors.</p>
            </div>
            <form action="{{ route('admin.security.scan') }}" method="POST" class="px-6 py-5">
                @csrf
                <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                <div class="flex items-end gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Account (optional)</label>
                        <input type="text" name="username" placeholder="Leave empty to scan all accounts"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <label class="flex items-center space-x-2 pb-2.5">
                        <input type="checkbox" name="use_clamav" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">Use ClamAV</span>
                    </label>
                    <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        Scan Now
                    </button>
                </div>
            </form>
        </div>

        <!-- Block IP -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Block / Unblock IP</h3>
            </div>
            <form action="{{ route('admin.security.ip-block') }}" method="POST" class="px-6 py-5">
                @csrf
                <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                <div class="flex items-end gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">IP Address</label>
                        <input type="text" name="ip" placeholder="e.g. 192.168.1.100"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <select name="action" class="rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="block">Block</option>
                            <option value="unblock">Unblock</option>
                        </select>
                    </div>
                    <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-gray-800 text-white text-sm font-medium rounded-lg hover:bg-gray-900 transition">
                        Apply
                    </button>
                </div>
            </form>
        </div>

        <!-- Firewall Rules -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Firewall (UFW)</h3>
                    <p class="text-sm mt-1">
                        Status:
                        <span class="font-medium @if($firewallStatus === 'active') text-green-600 @else text-red-600 @endif">
                            {{ ucfirst($firewallStatus) }}
                        </span>
                    </p>
                </div>
            </div>

            <!-- Add Rule -->
            <form action="{{ route('admin.security.firewall-add') }}" method="POST" class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                @csrf
                <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                <div class="flex items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Action</label>
                        <select name="action" class="rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="allow">Allow</option>
                            <option value="deny">Deny</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Port</label>
                        <input type="text" name="port" placeholder="e.g. 80/tcp, 443, 3306"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">From IP (optional)</label>
                        <input type="text" name="from" placeholder="any"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        Add Rule
                    </button>
                </div>
            </form>

            @if(empty($firewallRules))
                <div class="px-6 py-10 text-center text-sm text-gray-400">No firewall rules found.</div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($firewallRules as $rule)
                        <div class="flex items-center justify-between px-6 py-3">
                            <div class="flex items-center space-x-4">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold
                                    @if($rule['action'] === 'ALLOW') bg-green-100 text-green-700
                                    @else bg-red-100 text-red-700 @endif">
                                    {{ $rule['action'] }}
                                </span>
                                <span class="text-sm font-mono text-gray-800">{{ $rule['to'] }}</span>
                                <span class="text-xs text-gray-400">from {{ $rule['from'] }}</span>
                            </div>
                            <form action="{{ route('admin.security.firewall-remove') }}" method="POST">
                                @csrf
                                <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                                <input type="hidden" name="number" value="{{ $rule['number'] }}">
                                <button type="submit" class="text-gray-400 hover:text-red-600 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Fail2ban -->
        @if($fail2ban)
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Fail2ban</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        @if($fail2ban['installed'] ?? false) Active — monitoring login attempts
                        @else Not installed
                        @endif
                    </p>
                </div>

                @if(!empty($fail2ban['jails']))
                    <div class="px-6 py-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @foreach($fail2ban['jails'] as $jail)
                            <div class="border rounded-lg p-3 text-center">
                                <div class="text-sm font-semibold text-gray-800">{{ $jail['name'] }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ $jail['banned_ips'] }} banned / {{ $jail['total_bans'] }} total</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(!empty($fail2ban['banned_ips']))
                    <div class="border-t border-gray-100">
                        <div class="px-6 py-3 text-xs font-medium text-gray-400 uppercase bg-gray-50">Currently Banned IPs</div>
                        <div class="divide-y divide-gray-50">
                            @foreach($fail2ban['banned_ips'] as $ban)
                                <div class="flex items-center justify-between px-6 py-2">
                                    <div class="text-sm">
                                        <span class="font-mono font-semibold text-gray-800">{{ $ban['ip'] }}</span>
                                        <span class="text-xs text-gray-400 ml-2">{{ $ban['jail'] }}</span>
                                    </div>
                                    <form action="{{ route('admin.security.fail2ban-unban') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                                        <input type="hidden" name="ip" value="{{ $ban['ip'] }}">
                                        <input type="hidden" name="jail" value="{{ $ban['jail'] }}">
                                        <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition">Unban</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    @endif
</x-admin-layout>
