<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Spam Filter (Rspamd)</h2>
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
                            <option value="{{ $server->id }}" @selected($selectedServer && $selectedServer->id === $server->id)>{{ $server->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">Manage</button>
            </form>
        </div>
    </div>

    @if($selectedServer && $status)
        <div class="max-w-2xl space-y-6">

            {{-- Status --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-800">Status</h3>
                    @if($status['running'] ?? false)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                    @elseif($status['installed'] ?? false)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Stopped</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Not Installed</span>
                    @endif
                </div>

                @if(!($status['running'] ?? false))
                    <form action="{{ route('admin.spam-filter.configure') }}" method="POST"
                          x-data="{ loading: false }" @submit="loading = true">
                        @csrf
                        <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                        <input type="hidden" name="action" value="enable">
                        <p class="text-sm text-gray-500 mb-4">Enable Rspamd to automatically filter spam for all email accounts on this server.</p>
                        <button type="submit" :disabled="loading"
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="loading" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <span x-text="loading ? 'Installing Rspamd...' : 'Enable Spam Filter'">Enable Spam Filter</span>
                        </button>
                    </form>
                @else
                    <p class="text-sm text-gray-500">Rspamd is filtering incoming email. Spam is automatically moved to the Junk folder.</p>
                @endif
            </div>

            {{-- Configuration --}}
            @if($status['running'] ?? false)
                <form action="{{ route('admin.spam-filter.configure') }}" method="POST">
                    @csrf
                    <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                    <input type="hidden" name="action" value="configure">

                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-100">
                            <h3 class="text-base font-semibold text-gray-800">Spam Thresholds</h3>
                            <p class="text-sm text-gray-500 mt-1">Higher scores = more aggressive filtering. Lower = more permissive.</p>
                        </div>
                        <div class="px-6 py-5 space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Greylist Score</label>
                                <input type="number" name="greylist_score" value="4" step="0.5" min="1" max="50"
                                    class="w-32 rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-400">Temporarily reject and retry (delays spam bots). Default: 4</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Add Header Score</label>
                                <input type="number" name="add_header" value="6" step="0.5" min="1" max="50"
                                    class="w-32 rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-400">Mark as spam and deliver to Junk folder. Default: 6</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Reject Score</label>
                                <input type="number" name="reject_score" value="15" step="0.5" min="1" max="100"
                                    class="w-32 rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-400">Reject the email entirely. Default: 15</p>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center space-x-3">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                                Save Thresholds
                            </button>
                            <form action="{{ route('admin.spam-filter.configure') }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                                <input type="hidden" name="action" value="disable">
                                <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition">
                                    Disable Spam Filter
                                </button>
                            </form>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    @endif
</x-admin-layout>
