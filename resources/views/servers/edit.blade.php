<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.servers.show', $server) }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">{{ __('servers.edit_server') }}</h2>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <form action="{{ route('admin.servers.update', $server) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('servers.server_name') }}</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $server->name) }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('name')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- IP Address -->
                <div>
                    <label for="ip_address" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('servers.ip_address') }}</label>
                    <input type="text" name="ip_address" id="ip_address" value="{{ old('ip_address', $server->ip_address) }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono">
                    @error('ip_address')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Hostname -->
                <div>
                    <label for="hostname" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('servers.hostname') }} <span class="text-gray-400">({{ __('common.optional') }})</span></label>
                    <input type="text" name="hostname" id="hostname" value="{{ old('hostname', $server->hostname) }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('hostname')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Agent URL -->
                <div>
                    <label for="agent_url" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('servers.agent_url') }}</label>
                    <input type="text" name="agent_url" id="agent_url" value="{{ old('agent_url', $server->agent_url) }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                        placeholder="http://127.0.0.1:7443">
                    @error('agent_url')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Agent Token -->
                <div>
                    <label for="agent_token" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('servers.agent_token') }}</label>
                    @if(is_null($agentToken) && !old('agent_token'))
                        <div class="mb-2 bg-amber-50 border border-amber-200 text-amber-700 px-3 py-2 rounded-lg text-xs">
                            Token could not be decrypted — it may have been set directly in the database. Enter the correct secret below.
                        </div>
                    @endif
                    <input type="text" name="agent_token" id="agent_token" value="{{ old('agent_token', $agentToken) }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                        placeholder="Secret from /etc/opterius/agent.conf">
                    <p class="mt-1.5 text-xs text-gray-400">{{ __('servers.agent_token_hint') }}</p>
                    @error('agent_token')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Actions -->
                <div class="flex items-center space-x-3 pt-4 border-t border-gray-100">
                    <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        {{ __('common.save_changes') }}
                    </button>
                    <a href="{{ route('admin.servers.show', $server) }}" class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        {{ __('common.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
