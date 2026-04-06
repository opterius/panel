<x-user-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('user.databases.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">{{ __('databases.create_database') }}</h2>
        </div>
    </x-slot>

    @if($accounts->isEmpty())
        <div class="max-w-2xl">
            <div class="bg-white rounded-xl shadow-sm p-6 text-center py-16">
                <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                <h3 class="mt-4 text-base font-medium text-gray-700">{{ __('databases.no_accounts_available') }}</h3>
                <p class="mt-2 text-sm text-gray-500">
                    @if(auth()->user()->isAdmin() || auth()->user()->isReseller())
                        {{ __('databases.need_account_admin') }}
                    @else
                        {{ __('databases.contact_hosting_provider') }}
                    @endif
                </p>
                @if(auth()->user()->isAdmin() || auth()->user()->isReseller())
                    <a href="{{ route('admin.accounts.create') }}" class="mt-6 inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        {{ __('accounts.create_account') }}
                    </a>
                @endif
            </div>
        </div>
    @else
        <form action="{{ route('user.databases.store') }}" method="POST"
              x-data="{
                  dbName: '{{ old('name') }}',
                  dbUser: '{{ old('db_username') }}',
                  remote: {{ old('remote', false) ? 'true' : 'false' }}
              }">
            @csrf

            <div class="max-w-2xl space-y-6">

                {{-- Section 1: Account --}}
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600">1</span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-800">{{ __('databases.account') }}</h3>
                                <p class="text-sm text-gray-500">{{ __('databases.select_hosting_account') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-5">
                        <label for="account_id" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('databases.account') }}</label>
                        <select name="account_id" id="account_id"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" @selected(old('account_id') == $account->id)>
                                    {{ $account->username }} ({{ $account->server->name }} &mdash; {{ $account->server->ip_address }})
                                </option>
                            @endforeach
                        </select>
                        @error('account_id')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Section 2: Database --}}
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600">2</span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-800">{{ __('databases.database_details') }}</h3>
                                <p class="text-sm text-gray-500">{{ __('databases.name_for_new_mysql') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-5">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('databases.database_name') }}</label>
                        <input type="text" name="name" id="name" x-model="dbName"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="e.g. myapp_production">
                        <p class="mt-1.5 text-xs text-gray-400">{{ __('databases.alphanumeric_underscores') }}</p>
                        @error('name')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Section 3: User --}}
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600">3</span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-800">{{ __('databases.db_user') }}</h3>
                                <p class="text-sm text-gray-500">{{ __('databases.user_granted_full_access') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-5 space-y-5">
                        <div>
                            <label for="db_username" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('databases.db_username') }}</label>
                            <input type="text" name="db_username" id="db_username" x-model="dbUser"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="e.g. myapp_user">
                            @error('db_username')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="db_password" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('databases.db_password') }}</label>
                            <input type="password" name="db_password" id="db_password"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Minimum 8 characters">
                            @error('db_password')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" name="remote" value="1" x-model="remote"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">{{ __('databases.allow_remote_connections') }}</span>
                            </label>
                            <p class="mt-1 text-xs text-gray-400 ml-7">{{ __('databases.remote_from_any_ip') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Summary --}}
                <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-5">
                    <h4 class="text-sm font-semibold text-indigo-800 mb-3">{{ __('databases.summary') }}</h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm text-indigo-700">
                        <div>
                            <span class="text-indigo-400 block text-xs mb-0.5">{{ __('databases.database') }}</span>
                            <span class="font-medium font-mono" x-text="dbName || '—'">—</span>
                        </div>
                        <div>
                            <span class="text-indigo-400 block text-xs mb-0.5">{{ __('databases.db_user') }}</span>
                            <span class="font-medium font-mono" x-text="dbUser || '—'">—</span>
                        </div>
                        <div>
                            <span class="text-indigo-400 block text-xs mb-0.5">{{ __('databases.access') }}</span>
                            <span class="font-medium" x-text="remote ? '{{ __('databases.remote_percent') }}' : '{{ __('databases.local_only') }}'">{{ __('databases.local_only') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center space-x-3">
                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        {{ __('databases.create_database') }}
                    </button>
                    <a href="{{ route('user.databases.index') }}" class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        {{ __('common.cancel') }}
                    </a>
                </div>

            </div>
        </form>
    @endif
</x-user-layout>
