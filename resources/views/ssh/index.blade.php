<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">SSH Access</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Account Selector -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">Select Account</h3>
            <p class="text-sm text-gray-500 mt-1">Manage SSH keys and access per hosting account.</p>
        </div>
        <div class="px-6 py-5">
            @if($accounts->isEmpty())
                <p class="text-sm text-gray-500">No accounts available.</p>
            @else
                <form method="GET" action="{{ route('user.ssh.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <label for="account_id" class="block text-sm font-medium text-gray-700 mb-1.5">Account</label>
                        <select name="account_id" id="account_id"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" @selected($selectedAccount && $selectedAccount->id === $account->id)>
                                    {{ $account->username }} ({{ $account->server->name }} &mdash; {{ $account->server->ip_address }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        Manage
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if($selectedAccount)
        <!-- SSH Access Toggle -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">SSH Shell Access</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        @if($sshEnabled)
                            SSH login is <span class="text-green-600 font-medium">enabled</span> for <span class="font-mono">{{ $selectedAccount->username }}</span>.
                            The user can connect via <span class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded">ssh {{ $selectedAccount->username }}@{{ $selectedAccount->server->ip_address }}</span>
                        @else
                            SSH login is <span class="text-red-600 font-medium">disabled</span> for <span class="font-mono">{{ $selectedAccount->username }}</span>.
                            Shell is set to <span class="font-mono text-xs">/usr/sbin/nologin</span>.
                        @endif
                    </p>
                </div>
                <form action="{{ route('user.ssh.toggle-shell') }}" method="POST">
                    @csrf
                    <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                    <input type="hidden" name="enabled" value="{{ $sshEnabled ? '0' : '1' }}">
                    @if($sshEnabled)
                        <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-300 rounded-lg hover:bg-red-50 transition"
                                onclick="return confirm('Disable SSH access for {{ $selectedAccount->username }}?')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                            Disable SSH
                        </button>
                    @else
                        <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-green-600 bg-white border border-green-300 rounded-lg hover:bg-green-50 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" /></svg>
                            Enable SSH
                        </button>
                    @endif
                </form>
            </div>
        </div>

        <!-- Authorized Keys -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Authorized Keys</h3>
                <p class="text-sm text-gray-500 mt-1">Public keys authorized for SSH access to this account.</p>
            </div>

            @if(empty($keys))
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                    <p class="mt-3 text-sm text-gray-500">No SSH keys authorized yet.</p>
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($keys as $key)
                        <div class="flex items-center justify-between px-6 py-4">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-800">{{ $key['comment'] ?: 'Unnamed key' }}</div>
                                    <div class="text-xs text-gray-500">
                                        <span class="font-mono">{{ $key['type'] }}</span>
                                        &middot;
                                        <span class="font-mono">{{ $key['fingerprint'] }}</span>
                                    </div>
                                </div>
                            </div>
                            <form action="{{ route('user.ssh.delete-key') }}" method="POST"
                                  onsubmit="return confirm('Remove this SSH key?')">
                                @csrf
                                <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                                <input type="hidden" name="fingerprint" value="{{ $key['fingerprint'] }}">
                                <button type="submit" class="text-gray-400 hover:text-red-600 transition" title="Remove key">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Import Key -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Import Public Key</h3>
                <p class="text-sm text-gray-500 mt-1">Paste an SSH public key to authorize it for this account.</p>
            </div>
            <form action="{{ route('user.ssh.import-key') }}" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                <div>
                    <label for="public_key" class="block text-sm font-medium text-gray-700 mb-1.5">Public Key</label>
                    <textarea name="public_key" id="public_key" rows="4"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-xs font-mono focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="ssh-rsa AAAA... user@machine&#10;or&#10;ssh-ed25519 AAAA... user@machine">{{ old('public_key') }}</textarea>
                    <p class="mt-1.5 text-xs text-gray-400">Supported types: ssh-rsa, ssh-ed25519, ecdsa</p>
                    @error('public_key')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    Import Key
                </button>
            </form>
        </div>
    @endif
</x-user-layout>
