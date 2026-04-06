<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">API Key Created</h2>
    </x-slot>

    <div class="max-w-2xl space-y-6">

        {{-- Warning --}}
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                <div>
                    <h4 class="text-sm font-semibold text-amber-800">Copy your API key now</h4>
                    <p class="text-sm text-amber-700 mt-1">This is the only time the full key will be shown. Store it securely.</p>
                </div>
            </div>
        </div>

        {{-- Key Display --}}
        <div class="bg-white rounded-xl shadow-sm p-6" x-data="{ copied: false }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-800">{{ $apiKey->name }}</h3>
                <span class="text-xs text-gray-400">Created just now</span>
            </div>

            <div class="relative">
                <code id="api-key-value" class="block w-full bg-gray-900 text-green-400 px-4 py-3 rounded-lg text-sm font-mono break-all select-all">{{ $plaintext }}</code>
                <button type="button"
                    @click="navigator.clipboard.writeText('{{ $plaintext }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="absolute top-2 right-2 p-1.5 rounded-md bg-gray-800 hover:bg-gray-700 transition">
                    <svg x-show="!copied" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    <svg x-show="copied" class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </button>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-400 text-xs block mb-0.5">Server</span>
                    <span class="font-medium text-gray-700">{{ $apiKey->server?->name ?? 'All servers' }}</span>
                </div>
                <div>
                    <span class="text-gray-400 text-xs block mb-0.5">Permissions</span>
                    <div class="flex flex-wrap gap-1">
                        @foreach($apiKey->permissions ?? [] as $perm)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">{{ $perm }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- WHMCS Setup Instructions --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h4 class="text-sm font-semibold text-gray-800 mb-3">WHMCS Setup</h4>
            <ol class="text-sm text-gray-600 space-y-2 list-decimal list-inside">
                <li>In WHMCS, go to <span class="font-medium">Configuration &gt; System Settings &gt; Servers</span></li>
                <li>Click <span class="font-medium">Add New Server</span></li>
                <li>Set <span class="font-medium">Module</span> to <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">Opterius</code></li>
                <li>Set <span class="font-medium">Hostname</span> to <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">{{ request()->getHost() }}</code></li>
                <li>Set <span class="font-medium">Port</span> to <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">{{ request()->getPort() }}</code></li>
                <li>Paste the API key above into the <span class="font-medium">Access Hash</span> field</li>
                <li>Check <span class="font-medium">Secure</span> (HTTPS)</li>
                <li>Click <span class="font-medium">Test Connection</span> to verify</li>
            </ol>
        </div>

        <a href="{{ route('admin.api-keys.index') }}" class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            Done
        </a>

    </div>
</x-admin-layout>
