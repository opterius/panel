<x-user-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('user.postgres.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">{{ $db->name }}</h2>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                @if($db->status === 'active') bg-green-100 text-green-700 @else bg-red-100 text-red-700 @endif">
                {{ ucfirst($db->status) }}
            </span>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    {{-- One-time password display --}}
    @if(session('pg_password'))
        <div class="mb-6 bg-amber-50 border border-amber-300 rounded-xl p-5">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <p class="text-sm font-semibold text-amber-800 mb-1">Save this password — it will not be shown again</p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="font-mono text-sm bg-amber-100 px-3 py-1.5 rounded-lg text-amber-900 select-all">{{ session('pg_password') }}</span>
                    </div>
                    <p class="text-xs text-amber-700 mt-2">Use this password with user <strong>{{ $db->pg_username }}</strong> to connect to the database.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        {{-- Database info --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-5">Database Details</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Database Name</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-800">{{ $db->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">User</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-800">{{ $db->pg_username }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Host</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-800">localhost · port 5432</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Size</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $info['size'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Tables</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $info['table_count'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Server</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $db->account->server->name }}</dd>
                </div>
            </dl>

            @if(!empty($info['users']))
                <div class="mt-6 pt-5 border-t border-gray-100">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Users with Access</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($info['users'] as $user)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-xs font-mono text-gray-700">{{ $user }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Change password --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">Change Password</h3>
            <form action="{{ route('user.postgres.password', $db) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">User</label>
                    <input type="text" value="{{ $db->pg_username }}" disabled
                        class="w-full rounded-lg border-gray-200 bg-gray-50 text-sm font-mono text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                    <x-password-input name="pg_password" id="pg_password_change" placeholder="Min 8 characters" :min-length="8" :default-length="20"/>
                    @error('pg_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit"
                    class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    Update Password
                </button>
            </form>
        </div>
    </div>

    {{-- Connection string --}}
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-base font-semibold text-gray-800 mb-3">Connection String</h3>
        <pre class="bg-slate-900 text-slate-300 text-xs font-mono p-4 rounded-lg overflow-x-auto">postgresql://{{ $db->pg_username }}:YOUR_PASSWORD@127.0.0.1:5432/{{ $db->name }}</pre>
        <p class="mt-2 text-xs text-gray-400">Replace <code class="bg-gray-100 px-1 rounded">YOUR_PASSWORD</code> with your actual password. For remote access, contact your server administrator.</p>
    </div>

    {{-- Danger zone --}}
    <div class="bg-white rounded-xl shadow-sm p-6 border border-red-100">
        <h3 class="text-base font-semibold text-red-600 mb-2">Danger Zone</h3>
        <p class="text-sm text-gray-500 mb-4">Permanently deletes the database and its user. All data is lost.</p>

        <x-delete-modal
            :action="route('user.postgres.destroy', $db)"
            title="Delete PostgreSQL Database"
            :message="'Permanently delete &quot;' . $db->name . '&quot; and user &quot;' . $db->pg_username . '&quot;? All data will be lost.'"
            :confirm-password="true">
            <x-slot name="trigger">
                <button type="button"
                    class="inline-flex items-center px-4 py-2.5 bg-white text-red-600 text-sm font-medium border border-red-300 rounded-lg hover:bg-red-50 transition">
                    Delete Database
                </button>
            </x-slot>
        </x-delete-modal>
    </div>
</x-user-layout>
