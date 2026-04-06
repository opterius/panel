<x-user-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('user.databases.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">{{ $database->name }}</h2>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                @if($database->status === 'active') bg-green-100 text-green-700 @else bg-red-100 text-red-700 @endif">
                {{ ucfirst($database->status) }}
            </span>
        </div>
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

    <!-- Quick Actions -->
    <div class="flex flex-wrap gap-3 mb-6">
        <a href="{{ str_replace('SERVER_IP', $database->account->server->ip_address, config('opterius.phpmyadmin_url', 'https://SERVER_IP:8081')) }}"
           target="_blank"
           class="inline-flex items-center px-4 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
            Open phpMyAdmin
        </a>

        <form action="{{ route('user.databases.repair', $database) }}" method="POST">
            @csrf
            <input type="hidden" name="action" value="repair">
            <button type="submit" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                Repair
            </button>
        </form>

        <form action="{{ route('user.databases.repair', $database) }}" method="POST">
            @csrf
            <input type="hidden" name="action" value="optimize">
            <button type="submit" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                Optimize
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Database Info -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-5">Database Details</h3>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Database Name</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-800">{{ $database->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Username</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-800">{{ $database->db_username }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Server</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $database->account->server->name }} ({{ $database->account->server->ip_address }})</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Size</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $info['size_mb'] ?? '--' }} MB</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Tables</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $info['table_count'] ?? '--' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Account</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $database->account->username }}</dd>
                </div>
            </dl>
        </div>

        <!-- Change Password -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">Change User Password</h3>
            <form action="{{ route('user.databases.password', $database) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">User</label>
                    <input type="text" value="{{ $database->db_username }}" disabled
                        class="w-full rounded-lg border-gray-200 bg-gray-50 shadow-sm text-sm font-mono text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                    <input type="password" name="db_password" placeholder="Min 8 characters"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('db_password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    Update Password
                </button>
            </form>
        </div>
    </div>

    <!-- Tables List -->
    @if(!empty($info['tables']))
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Tables</h3>
            </div>
            <div class="hidden sm:grid grid-cols-12 px-6 py-2 text-xs font-medium text-gray-400 uppercase tracking-wide border-b border-gray-100 bg-gray-50">
                <div class="col-span-6">Table Name</div>
                <div class="col-span-3">Size</div>
                <div class="col-span-3">Rows</div>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($info['tables'] as $table)
                    <div class="grid grid-cols-12 items-center px-6 py-2.5 text-sm">
                        <div class="col-span-6 font-mono text-gray-800">{{ $table['name'] }}</div>
                        <div class="col-span-3 text-gray-500">{{ $table['size_kb'] }} KB</div>
                        <div class="col-span-3 text-gray-500">{{ $table['rows'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Database Users -->
    @if(!empty($info['users']))
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Authorized Users</h3>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($info['users'] as $user)
                    <div class="flex items-center justify-between px-6 py-3">
                        <div class="text-sm">
                            <span class="font-mono font-semibold text-gray-800">{{ $user['username'] }}</span>
                            <span class="text-gray-400">@</span>
                            <span class="font-mono text-gray-500">{{ $user['host'] }}</span>
                        </div>
                        <span class="text-xs text-gray-400">{{ $user['host'] === '%' ? 'Remote access' : 'Local only' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Danger Zone -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-red-100">
        <h3 class="text-base font-semibold text-red-600 mb-2">Danger Zone</h3>
        <p class="text-sm text-gray-500 mb-4">Deleting this database will permanently remove all data and the associated user.</p>

        <x-delete-modal
            :action="route('user.databases.destroy', $database)"
            title="Delete Database"
            message="This will permanently delete the database {{ $database->name }} and user {{ $database->db_username }} from the server. All data will be lost."
            :confirm-password="true">
            <x-slot name="trigger">
                <button type="button" class="inline-flex items-center px-4 py-2.5 bg-white text-red-600 text-sm font-medium border border-red-300 rounded-lg hover:bg-red-50 transition">
                    Delete Database
                </button>
            </x-slot>
        </x-delete-modal>
    </div>
</x-user-layout>
