<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Backups</h2>
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
            <form method="GET" action="{{ route('admin.backups.index') }}" class="flex items-end gap-4">
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
        <!-- Create Backup -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Create Backup</h3>
            </div>
            <form action="{{ route('admin.backups.create') }}" method="POST" class="px-6 py-5"
                  x-data="{ creating: false }" @submit="creating = true">
                @csrf
                <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-48">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Account</label>
                        <select name="account_id" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->username }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-40">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
                        <select name="type" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="full">Full Backup</option>
                            <option value="files">Files Only</option>
                            <option value="databases">Databases Only</option>
                            <option value="email">Email Only</option>
                        </select>
                    </div>
                    <button type="submit" :disabled="creating"
                        class="inline-flex items-center px-5 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="!creating">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                        </template>
                        <template x-if="creating">
                            <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        </template>
                        <span x-text="creating ? 'Creating backup...' : 'Create Backup'">Create Backup</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Backup List -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Backups</h3>
                <p class="text-sm text-gray-500 mt-1">Stored at /var/backups/opterius/ on the server.</p>
            </div>

            @if($backups->isEmpty())
                <div class="px-6 py-16 text-center">
                    <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                    <h3 class="mt-4 text-base font-medium text-gray-700">No backups yet</h3>
                    <p class="mt-2 text-sm text-gray-500">Create your first backup above.</p>
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($backups as $backup)
                        <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-100 transition">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                    @if($backup->status === 'completed') bg-green-100 @else bg-red-100 @endif">
                                    <svg class="w-5 h-5 @if($backup->status === 'completed') text-green-600 @else text-red-600 @endif"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-800">{{ $backup->username }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ ucfirst($backup->type) }} &middot; {{ number_format($backup->size_mb, 1) }} MB &middot; {{ $backup->created_at->format('M d, Y H:i') }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                    @if($backup->status === 'completed') bg-green-100 text-green-700 @else bg-red-100 text-red-700 @endif">
                                    {{ ucfirst($backup->status) }}
                                </span>

                                <a href="{{ route('admin.backups.download', $backup) }}" target="_blank"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                    Download
                                </a>

                                <form action="{{ route('admin.backups.restore', $backup) }}" method="POST"
                                      x-data="{ restoring: false }" @submit="restoring = true">
                                    @csrf
                                    <button type="submit" :disabled="restoring"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-amber-600 bg-amber-50 rounded-lg hover:bg-amber-100 transition disabled:opacity-50"
                                        onclick="return confirm('Restore this backup? This will overwrite current files/databases.')">
                                        <span x-text="restoring ? 'Restoring...' : 'Restore'">Restore</span>
                                    </button>
                                </form>

                                <form action="{{ route('admin.backups.destroy', $backup) }}" method="POST"
                                      onsubmit="return confirm('Delete this backup?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-400 hover:text-red-600 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</x-admin-layout>
