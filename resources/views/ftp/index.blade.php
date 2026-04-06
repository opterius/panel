<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">{{ __('ftp.ftp_accounts') }}</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <!-- Account Selector -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5">
            <form method="GET" action="{{ route('user.ftp.index') }}" class="flex items-end gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('cron.account') }}</label>
                    <select name="account_id" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" @selected($selectedAccount && $selectedAccount->id === $account->id)>
                                {{ $account->username }} ({{ $account->server->name }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">{{ __('common.manage') }}</button>
            </form>
        </div>
    </div>

    @if($selectedAccount)
        <!-- Create FTP Account -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">{{ __('ftp.create_ftp_account') }}</h3>
            </div>
            <form action="{{ route('user.ftp.store') }}" method="POST" class="px-6 py-5">
                @csrf
                <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('common.username') }}</label>
                        <input type="text" name="username" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="ftpuser">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('common.password') }}</label>
                        <input type="password" name="password" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('ftp.min_chars') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('ftp.directory') }}</label>
                        <input type="text" name="directory" value="{{ $selectedAccount->home_directory }}" class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                            {{ __('common.create') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- FTP Accounts List -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">{{ __('ftp.ftp_accounts') }}</h3>
            </div>
            @if(empty($ftpAccounts))
                <div class="px-6 py-12 text-center text-sm text-gray-400">{{ __('ftp.no_ftp_accounts') }}</div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($ftpAccounts as $ftp)
                        <div class="flex items-center justify-between px-6 py-4">
                            <div>
                                <div class="text-sm font-semibold text-gray-800 font-mono">{{ $ftp['username'] }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ $ftp['directory'] }}</div>
                            </div>
                            <form action="{{ route('user.ftp.destroy') }}" method="POST" onsubmit="return confirm('{{ __('ftp.delete_ftp_account', ['username' => $ftp['username']]) }}')">
                                @csrf
                                <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                                <input type="hidden" name="username" value="{{ $ftp['username'] }}">
                                <button type="submit" class="text-gray-400 hover:text-red-600 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Connection Details -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">{{ __('ftp.ftp_connection_settings') }}</h3>
            </div>
            <div class="px-6 py-5">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">{{ __('ftp.ftp_server') }}</dt>
                        <dd class="font-mono text-gray-800">{{ $selectedAccount->server->ip_address }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('ftp.ftp_port') }}</dt>
                        <dd class="font-mono text-gray-800">21</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('ftp.ftp_protocol') }}</dt>
                        <dd class="text-gray-800">{{ __('ftp.ftp_protocol_value') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('common.username') }}</dt>
                        <dd class="text-gray-800">{{ __('ftp.ftp_username') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    @endif
</x-user-layout>
