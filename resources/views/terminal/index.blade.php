<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Web Terminal</h2>
    </x-slot>

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <div class="mb-6">
        <p class="text-sm text-gray-500">SSH access details for your accounts. SSH must be enabled for the account.</p>
    </div>

    @if($accounts->isEmpty())
        <div class="bg-white rounded-xl shadow-sm px-6 py-16 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            <h3 class="mt-4 text-base font-medium text-gray-700">No SSH-enabled accounts</h3>
            <p class="mt-2 text-sm text-gray-500">Enable SSH access for an account from the SSH Access page first.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($accounts as $account)
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-900 flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">{{ $account->username }}</div>
                                <div class="text-xs text-gray-500">{{ $account->domains->first()?->domain ?? $account->server->name }}</div>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">SSH Enabled</span>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">SSH Command</label>
                            <div class="flex items-center gap-2" x-data="{ copied: false }">
                                <code class="flex-1 bg-gray-900 text-green-400 px-4 py-2.5 rounded-lg text-sm font-mono select-all">ssh {{ $account->username }}@{{ $account->server->ip_address }}</code>
                                <button type="button" @click="navigator.clipboard.writeText('ssh {{ $account->username }}@{{ $account->server->ip_address }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="p-2 rounded-lg bg-gray-900 hover:bg-gray-800 transition">
                                    <svg x-show="!copied" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                    <svg x-show="copied" class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="text-xs text-gray-400 block">Host</span>
                                <span class="font-mono text-gray-700">{{ $account->server->ip_address }}</span>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 block">Port</span>
                                <span class="font-mono text-gray-700">22</span>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 block">Username</span>
                                <span class="font-mono text-gray-700">{{ $account->username }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-user-layout>
