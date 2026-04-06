<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.api-keys.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">Create API Key</h2>
        </div>
    </x-slot>

    <form action="{{ route('admin.api-keys.store') }}" method="POST">
        @csrf

        <div class="max-w-2xl space-y-6">

            {{-- Key Name --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-indigo-600">1</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Key Details</h3>
                            <p class="text-sm text-gray-500">Give this key a descriptive name.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5 space-y-5">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="e.g. WHMCS Production">
                        @error('name')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="server_id" class="block text-sm font-medium text-gray-700 mb-1.5">Server Scope <span class="text-gray-400 font-normal">(optional)</span></label>
                        <select name="server_id" id="server_id"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All servers</option>
                            @foreach($servers as $server)
                                <option value="{{ $server->id }}" @selected(old('server_id') == $server->id)>
                                    {{ $server->name }} ({{ $server->ip_address }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-gray-400">Lock this key to a specific server, or leave blank for all servers.</p>
                    </div>
                </div>
            </div>

            {{-- Permissions --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-indigo-600">2</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Permissions</h3>
                            <p class="text-sm text-gray-500">Select what this key can do.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5 space-y-3">
                    <label class="flex items-center space-x-3 mb-4" x-data="{ all: false }" x-init="$watch('all', val => { document.querySelectorAll('.perm-checkbox').forEach(c => c.checked = val) })">
                        <input type="checkbox" x-model="all"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm font-semibold text-gray-700">Select all</span>
                    </label>
                    @foreach($permissions as $value => $label)
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" name="permissions[]" value="{{ $value }}" class="perm-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                @checked(is_array(old('permissions')) && in_array($value, old('permissions')))>
                            <span class="text-sm text-gray-700">{{ $label }}</span>
                            <code class="text-xs text-gray-400">{{ $value }}</code>
                        </label>
                    @endforeach
                    @error('permissions')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- IP Whitelist --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-indigo-600">3</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Security</h3>
                            <p class="text-sm text-gray-500">Restrict which IPs can use this key.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <label for="allowed_ips" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Allowed IPs <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <input type="text" name="allowed_ips" id="allowed_ips" value="{{ old('allowed_ips') }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="e.g. 203.0.113.5, 198.51.100.10">
                    <p class="mt-1.5 text-xs text-gray-400">Comma-separated list of IP addresses. Leave blank to allow any IP.</p>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center space-x-3">
                <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                    Generate API Key
                </button>
                <a href="{{ route('admin.api-keys.index') }}" class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </a>
            </div>

        </div>
    </form>
</x-admin-layout>
