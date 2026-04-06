<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.migrations.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">Migration Preview</h2>
        </div>
    </x-slot>

    @php $m = $migration->manifest ?? []; @endphp

    <form action="{{ route('admin.migrations.execute', $migration) }}" method="POST"
          x-data="{ username: '{{ $migration->target_username }}' }">
        @csrf

        <div class="max-w-3xl space-y-6">

            {{-- Account Summary --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Detected Account</h3>
                </div>
                <div class="px-6 py-5 grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <span class="text-xs text-gray-400 block">Username</span>
                        <span class="text-sm font-semibold text-gray-800 font-mono">{{ $m['username'] ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block">Main Domain</span>
                        <span class="text-sm font-semibold text-gray-800">{{ $m['main_domain'] ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block">Disk Usage</span>
                        <span class="text-sm font-semibold text-gray-800">{{ number_format($m['disk_usage_mb'] ?? 0, 1) }} MB</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block">Server</span>
                        <span class="text-sm font-semibold text-gray-800">{{ $migration->server->name }}</span>
                    </div>
                </div>
            </div>

            {{-- Configuration --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Import Settings</h3>
                </div>
                <div class="px-6 py-5 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="target_username" class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                            <input type="text" name="target_username" id="target_username" x-model="username"
                                value="{{ old('target_username', $migration->target_username) }}"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="mt-1 text-xs text-gray-400">Change if the username conflicts with an existing account.</p>
                            @error('target_username')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="package_id" class="block text-sm font-medium text-gray-700 mb-1.5">Package</label>
                            <select name="package_id" id="package_id"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($packages as $pkg)
                                    <option value="{{ $pkg->id }}" @selected($pkg->is_default)>
                                        {{ $pkg->name }} ({{ $pkg->diskQuotaLabel() }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- What to Import --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Components to Import</h3>
                </div>
                <div class="px-6 py-5 space-y-4">

                    {{-- Files --}}
                    <label class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition cursor-pointer">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="import_files" value="1" checked
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="text-sm font-medium text-gray-800">Files</span>
                                <span class="text-xs text-gray-500 block">{{ number_format($m['disk_usage_mb'] ?? 0, 1) }} MB</span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                    </label>

                    {{-- Databases --}}
                    <label class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition cursor-pointer">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="import_databases" value="1" checked
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="text-sm font-medium text-gray-800">Databases</span>
                                <span class="text-xs text-gray-500 block">{{ count($m['databases'] ?? []) }} database(s)</span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" /></svg>
                    </label>

                    @if(!empty($m['databases']))
                        <div class="ml-10 text-xs text-gray-500 space-y-1">
                            @foreach($m['databases'] as $db)
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono">{{ $db['name'] }}</span>
                                    @if(($db['size_mb'] ?? 0) > 0)
                                        <span class="text-gray-400">({{ number_format($db['size_mb'], 1) }} MB)</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Email --}}
                    <label class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition cursor-pointer">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="import_email" value="1" checked
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="text-sm font-medium text-gray-800">Email Accounts</span>
                                <span class="text-xs text-gray-500 block">{{ count($m['email_accounts'] ?? []) }} account(s)</span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </label>

                    {{-- DNS --}}
                    <label class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition cursor-pointer">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="import_dns" value="1" checked
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="text-sm font-medium text-gray-800">DNS Zones</span>
                                <span class="text-xs text-gray-500 block">{{ count($m['dns_zones'] ?? []) }} zone(s)</span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" /></svg>
                    </label>

                    {{-- SSL --}}
                    <label class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition cursor-pointer">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="import_ssl" value="1" checked
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="text-sm font-medium text-gray-800">SSL Certificates</span>
                                <span class="text-xs text-gray-500 block">{{ count($m['ssl_certs'] ?? []) }} certificate(s)</span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    </label>

                    {{-- Cron --}}
                    <label class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition cursor-pointer">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="import_cron" value="1" checked
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="text-sm font-medium text-gray-800">Cron Jobs</span>
                                <span class="text-xs text-gray-500 block">{{ count($m['cron_jobs'] ?? []) }} job(s)</span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </label>

                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center space-x-3">
                <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    Start Migration
                </button>
                <a href="{{ route('admin.migrations.index') }}" class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</x-admin-layout>
