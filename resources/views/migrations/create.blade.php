<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.migrations.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">{{ __('migrations.import_cpanel_backup') }}</h2>
        </div>
    </x-slot>

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    @if($servers->isEmpty())
        <div class="max-w-2xl">
            <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg>
                <h3 class="text-base font-semibold text-gray-800 mb-2">No servers available</h3>
                <p class="text-sm text-gray-500 mb-4">You need to add a server before importing a cPanel backup. Create a hosting account first — the server will be registered automatically.</p>
                <a href="{{ route('admin.accounts.create') }}" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    Create Account
                </a>
            </div>
        </div>
    @else

    <form action="{{ route('admin.migrations.parse') }}" method="POST"
          x-data="{ loading: false }" @submit="loading = true">
        @csrf
        <div class="max-w-2xl space-y-6">

            {{-- Server --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-indigo-600">1</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">{{ __('migrations.target_server') }}</h3>
                            <p class="text-sm text-gray-500">{{ __('migrations.select_server_description') }}</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <select name="server_id" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($servers as $server)
                            <option value="{{ $server->id }}">{{ $server->name }} ({{ $server->ip_address }})</option>
                        @endforeach
                    </select>
                    @error('server_id')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Backup Path --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-indigo-600">2</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">{{ __('migrations.backup_file') }}</h3>
                            <p class="text-sm text-gray-500">{{ __('migrations.backup_path_description') }}</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <input type="text" name="source_path" value="{{ old('source_path') }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="/var/backups/cpanel/backup-4.6.2025_07-45-22_username.tar.gz">
                    <p class="mt-2 text-xs text-gray-400">{{ __('migrations.backup_path_hint') }}</p>
                    @error('source_path')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Info --}}
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
                <div class="flex items-start space-x-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <div class="text-sm text-gray-600">
                        <p class="font-medium text-gray-700 mb-1">{{ __('migrations.how_to_get_backup') }}</p>
                        <ol class="list-decimal list-inside space-y-1 text-xs text-gray-500">
                            <li>Log into WHM on the source server</li>
                            <li>Go to Backup &gt; Download a Full Backup / cpmove file</li>
                            <li>Or: <code class="bg-gray-100 px-1 rounded">/scripts/pkgacct username</code> on the source server</li>
                            <li>Transfer the .tar.gz file to this server via SCP</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <button type="submit" :disabled="loading"
                    class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg x-show="!loading" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    <svg x-show="loading" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span x-text="loading ? '{{ __('migrations.parsing_backup') }}' : '{{ __('migrations.parse_backup') }}'">{{ __('migrations.parse_backup') }}</span>
                </button>
                <a href="{{ route('admin.migrations.index') }}" class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    {{ __('common.cancel') }}
                </a>
            </div>
        </div>
    </form>
    @endif
</x-admin-layout>
