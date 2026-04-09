<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">{{ __('databases.databases') }}</h2>
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

    @php
        // The phpMyAdmin URL is server-wide, not per-database. Pick the IP of
        // any database's server to build it — they're all served by the same
        // pma instance for accounts on this server.
        $pmaServerIp = optional($databases->first()?->account?->server)->ip_address;
        $pmaUrl = $pmaServerIp
            ? str_replace('SERVER_IP', $pmaServerIp, config('opterius.phpmyadmin_url', 'https://SERVER_IP:8081'))
            : null;
    @endphp

    <div class="bg-white rounded-xl shadow-sm">
        <!-- Header -->
        <div class="flex justify-between items-center px-6 py-5 border-b border-gray-100">
            <div>
                <h3 class="text-base font-semibold text-gray-800">{{ __('databases.all_databases') }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ __('databases.manage_mysql_databases') }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if($pmaUrl)
                    <a href="{{ $pmaUrl }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center px-4 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                        phpMyAdmin
                    </a>
                @endif
                <a href="{{ route('user.databases.create') }}" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    {{ __('databases.create_database') }}
                </a>
            </div>
        </div>

        @if($databases->isEmpty())
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
                <h3 class="mt-4 text-base font-medium text-gray-700">{{ __('databases.no_databases_yet') }}</h3>
                <p class="mt-2 text-sm text-gray-500">{{ __('databases.no_databases_create_first') }}</p>
                <a href="{{ route('user.databases.create') }}" class="mt-6 inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    {{ __('databases.create_database') }}
                </a>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($databases as $database)
                    <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-100 transition">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                @if($database->status === 'active') bg-purple-100
                                @else bg-red-100
                                @endif">
                                <svg class="w-5 h-5
                                    @if($database->status === 'active') text-purple-600
                                    @else text-red-600
                                    @endif"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                </svg>
                            </div>
                            <div>
                                <a href="{{ route('user.databases.show', $database) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition">{{ $database->name }}</a>
                                <div class="text-xs text-gray-500">
                                    {{ __('databases.db_user') }}: <span class="font-mono">{{ $database->db_username }}</span>
                                    &middot; {{ $database->server->name }}
                                    &middot; {{ $database->account->username }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                @if($database->status === 'active') bg-green-100 text-green-700
                                @else bg-red-100 text-red-700
                                @endif">
                                {{ ucfirst($database->status) }}
                            </span>

                            @php
                                $rowPmaUrl = str_replace('SERVER_IP', $database->account->server->ip_address, config('opterius.phpmyadmin_url', 'https://SERVER_IP:8081'));
                            @endphp
                            <a href="{{ $rowPmaUrl }}" target="_blank" rel="noopener noreferrer"
                               title="Open phpMyAdmin"
                               class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-lg transition">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                phpMyAdmin
                            </a>

                            <x-delete-modal
                                :action="route('user.databases.destroy', $database)"
                                :title="__('databases.delete_database')"
                                :message="__('databases.delete_database_msg', ['name' => $database->name])"
                                :confirm-password="true">
                                <x-slot name="trigger">
                                    <button type="button" class="text-gray-400 hover:text-red-600 transition" title="{{ __('databases.delete_database') }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </x-slot>
                            </x-delete-modal>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-user-layout>
