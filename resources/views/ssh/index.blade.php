<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">{{ __('ssh.ssh_keys') }}</h2>
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
            <h3 class="text-base font-semibold text-gray-800">{{ __('ssh.select_account') }}</h3>
            <p class="text-sm text-gray-500 mt-1">{{ __('ssh.select_account_hint') }}</p>
        </div>
        <div class="px-6 py-5">
            @if($accounts->isEmpty())
                <p class="text-sm text-gray-500">{{ __('ssh.no_accounts_available') }}</p>
            @else
                <form method="GET" action="{{ route('user.ssh.index') }}" class="flex items-end gap-4" autocomplete="off">
                    <div class="flex-1">
                        <label for="ssh_account" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('cron.account') }}</label>
                        <select name="account" id="ssh_account" autocomplete="off"
                            onchange="this.form.submit()"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @if(!$selectedAccount)
                                <option value="" disabled selected>{{ __('ssh.select_account') }}</option>
                            @endif
                            @foreach($accounts as $account)
                                <option value="{{ $account->username }}" @selected($selectedAccount && $selectedAccount->username === $account->username)>
                                    {{ $account->username }} ({{ $account->server->name }} &mdash; {{ $account->server->ip_address }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        {{ __('common.manage') }}
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
                    <h3 class="text-base font-semibold text-gray-800">{{ __('ssh.ssh_shell') }}</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        @if($sshEnabled)
                            {{ __('ssh.ssh_login_enabled_for') }} <span class="text-green-600 font-medium">{{ __('common.enabled') }}</span> {{ __('ssh.for_user') }} <span class="font-mono">{{ $selectedAccount->username }}</span>.
                            {{ __('ssh.connect_via') }} <span class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded">ssh {{ $selectedAccount->username }}@{{ $selectedAccount->server->ip_address }}</span>
                        @else
                            {{ __('ssh.ssh_login_enabled_for') }} <span class="text-red-600 font-medium">{{ __('common.disabled') }}</span> {{ __('ssh.for_user') }} <span class="font-mono">{{ $selectedAccount->username }}</span>.
                            {{ __('ssh.shell_set_to_nologin') }}
                        @endif
                    </p>
                </div>
                @if($sshEnabled)
                    <div x-data="{ confirmDisable: false }">
                        <button type="button" @click="confirmDisable = true" class="inline-flex items-center px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-300 rounded-lg hover:bg-red-50 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                            {{ __('ssh.disable_ssh') }}
                        </button>
                        <template x-teleport="body">
                            <div x-show="confirmDisable" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                                <div x-show="confirmDisable" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="confirmDisable = false"></div>
                                <div class="fixed inset-0 flex items-center justify-center p-4">
                                    <div x-show="confirmDisable" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.stop @keydown.escape.window="confirmDisable = false" class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
                                        <div class="p-6 pb-0">
                                            <div class="flex items-start space-x-4">
                                                <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                                                </div>
                                                <div>
                                                    <h3 class="text-lg font-semibold text-gray-900">{{ __('ssh.disable_ssh') }}</h3>
                                                    <p class="mt-1 text-sm text-gray-500">{{ __('ssh.disable_ssh_confirm', ['username' => $selectedAccount->username]) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-end space-x-3 px-6 py-5 mt-2">
                                            <button type="button" @click="confirmDisable = false" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">{{ __('common.cancel') }}</button>
                                            <form action="{{ route('user.ssh.toggle-shell') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                                                <input type="hidden" name="enabled" value="0">
                                                <button type="submit" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">{{ __('common.disable') }}</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                @else
                    <form action="{{ route('user.ssh.toggle-shell') }}" method="POST">
                        @csrf
                        <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                        <input type="hidden" name="enabled" value="1">
                        <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-green-600 bg-white border border-green-300 rounded-lg hover:bg-green-50 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" /></svg>
                            {{ __('ssh.enable_ssh') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Authorized Keys -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">{{ __('ssh.authorized_keys') }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ __('ssh.authorized_keys_hint') }}</p>
            </div>

            @if(empty($keys))
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                    <p class="mt-3 text-sm text-gray-500">{{ __('ssh.no_ssh_keys') }}</p>
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
                                    <div class="text-sm font-semibold text-gray-800">{{ $key['comment'] ?: __('ssh.unnamed_key') }}</div>
                                    <div class="text-xs text-gray-500">
                                        <span class="font-mono">{{ $key['type'] }}</span>
                                        &middot;
                                        <span class="font-mono">{{ $key['fingerprint'] }}</span>
                                    </div>
                                </div>
                            </div>
                            <div x-data="{ confirmDelete: false }" class="inline relative">
                                <button type="button" @click="confirmDelete = true" class="text-gray-400 hover:text-red-600 transition" title="{{ __('ssh.remove_key') }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                                <template x-teleport="body">
                                    <div x-show="confirmDelete" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                                        <div x-show="confirmDelete" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="confirmDelete = false"></div>
                                        <div class="fixed inset-0 flex items-center justify-center p-4">
                                            <div x-show="confirmDelete" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.stop @keydown.escape.window="confirmDelete = false" class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
                                                <div class="p-6 pb-0">
                                                    <div class="flex items-start space-x-4">
                                                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                                                        </div>
                                                        <div>
                                                            <h3 class="text-lg font-semibold text-gray-900">{{ __('ssh.remove_key') }}</h3>
                                                            <p class="mt-1 text-sm text-gray-500">{{ __('ssh.remove_key_confirm') }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center justify-end space-x-3 px-6 py-5 mt-2">
                                                    <button type="button" @click="confirmDelete = false" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">{{ __('common.cancel') }}</button>
                                                    <form action="{{ route('user.ssh.delete-key') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                                                        <input type="hidden" name="fingerprint" value="{{ $key['fingerprint'] }}">
                                                        <button type="submit" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">{{ __('common.remove') }}</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Add Key (tabs: Generate / Import) -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden" x-data="{ tab: 'generate' }">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">{{ __('ssh.add_ssh_key') }}</h3>
                <div class="flex space-x-1 mt-3 bg-gray-100 rounded-lg p-1 w-fit">
                    <button type="button" @click="tab = 'generate'"
                        class="px-4 py-1.5 text-xs font-medium rounded-md transition"
                        :class="tab === 'generate' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                        {{ __('ssh.generate_key') }}
                    </button>
                    <button type="button" @click="tab = 'import'"
                        class="px-4 py-1.5 text-xs font-medium rounded-md transition"
                        :class="tab === 'import' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                        {{ __('ssh.import_key') }}
                    </button>
                </div>
            </div>

            <!-- Generate Tab -->
            <div x-show="tab === 'generate'" class="px-6 py-5">
                <p class="text-sm text-gray-500 mb-4">{{ __('ssh.generate_key_hint') }}</p>
                <form action="{{ route('user.ssh.generate-key') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ssh.key_type') }}</label>
                        <div class="flex space-x-3">
                            <label class="relative">
                                <input type="radio" name="key_type" value="ed25519" class="peer sr-only" checked>
                                <div class="px-4 py-2.5 border border-gray-200 rounded-lg cursor-pointer text-sm font-medium
                                    peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-700
                                    hover:bg-gray-50 transition">
                                    Ed25519 <span class="text-xs text-gray-400 ml-1">(recommended)</span>
                                </div>
                            </label>
                            <label class="relative">
                                <input type="radio" name="key_type" value="rsa" class="peer sr-only">
                                <div class="px-4 py-2.5 border border-gray-200 rounded-lg cursor-pointer text-sm font-medium
                                    peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-700
                                    hover:bg-gray-50 transition">
                                    RSA 4096
                                </div>
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                        {{ __('ssh.generate_and_download') }}
                    </button>
                    <p class="text-xs text-gray-400">{{ __('ssh.generate_download_hint') }}</p>
                </form>
            </div>

            <!-- Import Tab -->
            <div x-show="tab === 'import'" class="px-6 py-5">
                <p class="text-sm text-gray-500 mb-4">{{ __('ssh.import_key_hint') }}</p>
                <form action="{{ route('user.ssh.import-key') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                    <div>
                        <label for="public_key" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('ssh.public_key') }}</label>
                        <textarea name="public_key" id="public_key" rows="3"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-xs font-mono focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="ssh-rsa AAAA... user@machine">{{ old('public_key') }}</textarea>
                        @error('public_key')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="private_key" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('ssh.private_key') }} <span class="text-gray-400 font-normal">({{ __('common.optional') }})</span></label>
                        <textarea name="private_key" id="private_key" rows="5"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-xs font-mono focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="-----BEGIN OPENSSH PRIVATE KEY-----&#10;...&#10;-----END OPENSSH PRIVATE KEY-----">{{ old('private_key') }}</textarea>
                        <p class="mt-1.5 text-xs text-gray-400">{{ __('ssh.private_key_hint') }}</p>
                        @error('private_key')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        {{ __('ssh.import_key_pair') }}
                    </button>
                </form>
            </div>
        </div>
    @endif
</x-user-layout>
