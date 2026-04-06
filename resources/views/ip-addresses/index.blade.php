<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">IP Address Management</h2>
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
            <form method="GET" class="flex items-end gap-4">
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
        <!-- Add IP -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">Add IP Address</h3>
            </div>
            <div class="px-6 py-4">
                <form action="{{ route('admin.ip-addresses.store') }}" method="POST" class="flex items-end gap-3">
                    @csrf
                    <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                    <div class="w-48">
                        <label class="block text-xs font-medium text-gray-500 mb-1">IP Address</label>
                        <input type="text" name="ip_address" class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500" placeholder="192.168.1.100">
                    </div>
                    <div class="w-36">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                        <select name="type" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="shared">Shared</option>
                            <option value="dedicated">Dedicated</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Note</label>
                        <input type="text" name="note" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Optional">
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">Add</button>
                </form>
            </div>
        </div>

        <!-- IP List -->
        @if($ipAddresses->isEmpty())
            <div class="bg-white rounded-xl shadow-sm px-6 py-12 text-center">
                <p class="text-sm text-gray-500">No IP addresses registered for this server. Add the server's primary IP and any additional IPs above.</p>
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigned To</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Note</th>
                            <th class="px-6 py-3 w-24"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($ipAddresses as $ip)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-mono font-medium text-gray-800">{{ $ip->ip_address }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                        {{ $ip->type === 'dedicated' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ ucfirst($ip->type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($ip->type === 'dedicated')
                                        <form action="{{ route('admin.ip-addresses.assign', $ip) }}" method="POST" class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <select name="account_id" class="rounded-lg border-gray-300 shadow-sm text-xs focus:border-indigo-500 focus:ring-indigo-500" onchange="this.form.submit()">
                                                <option value="">— Unassigned —</option>
                                                @foreach(\App\Models\Account::where('server_id', $selectedServer->id)->get() as $account)
                                                    <option value="{{ $account->id }}" @selected($ip->account_id === $account->id)>{{ $account->username }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">All accounts</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $ip->note ?? '—' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <form action="{{ route('admin.ip-addresses.destroy', $ip) }}" method="POST" class="inline" onsubmit="return confirm('Remove this IP?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-600 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</x-admin-layout>
