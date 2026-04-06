<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">API Keys</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <p class="text-sm text-gray-500">Manage API keys for WHMCS and other billing system integrations.</p>
        <a href="{{ route('admin.api-keys.create') }}" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
            Create API Key
        </a>
    </div>

    @if($apiKeys->isEmpty())
        <div class="bg-white rounded-xl shadow-sm px-6 py-16 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
            <h3 class="mt-4 text-base font-medium text-gray-700">No API keys</h3>
            <p class="mt-2 text-sm text-gray-500">Create an API key to integrate with WHMCS or other billing systems.</p>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Key</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Server</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Permissions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Used</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($apiKeys as $key)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-800">{{ $key->name }}</div>
                                <div class="text-xs text-gray-400">Created {{ $key->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <code class="text-xs bg-gray-100 px-2 py-1 rounded text-gray-600">{{ $key->key_prefix }}...</code>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $key->server?->name ?? 'All servers' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($key->permissions ?? [] as $perm)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">{{ $perm }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Never' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <x-delete-modal
                                    :action="route('admin.api-keys.destroy', $key)"
                                    title="Revoke API Key"
                                    message="This will immediately revoke the key '{{ $key->name }}'. Any integrations using this key will stop working."
                                    :confirm-password="true">
                                    <x-slot name="trigger">
                                        <button type="button" class="text-gray-400 hover:text-red-600 transition text-sm font-medium">
                                            Revoke
                                        </button>
                                    </x-slot>
                                </x-delete-modal>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- API Base URL info --}}
        <div class="mt-6 bg-gray-50 border border-gray-200 rounded-xl p-5">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">API Endpoint</h4>
            <code class="text-sm bg-white px-3 py-2 rounded border border-gray-200 block text-gray-800">{{ url('/api/v1') }}</code>
            <p class="mt-2 text-xs text-gray-500">Use this URL as the server hostname in WHMCS. Set the API key as the Access Hash.</p>
        </div>
    @endif
</x-admin-layout>
