<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.accounts.show', $account) }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">Team Access — {{ $account->username }}</h2>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <!-- Account Owner -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">Account Owner</h3>
        </div>
        <div class="flex items-center space-x-4 px-6 py-4">
            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                <span class="text-sm font-bold text-indigo-600">{{ strtoupper(substr($account->user->name, 0, 2)) }}</span>
            </div>
            <div>
                <div class="text-sm font-semibold text-gray-800">{{ $account->user->name }}</div>
                <div class="text-xs text-gray-500">{{ $account->user->email }}</div>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">Owner</span>
        </div>
    </div>

    <!-- Add Collaborator -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">Add Collaborator</h3>
            <p class="text-sm text-gray-500 mt-1">Invite a user to access this hosting account. If the email doesn't exist, a new user will be created.</p>
        </div>
        <form action="{{ route('admin.collaborators.store', $account) }}" method="POST" class="px-6 py-5"
              x-data="{ existingUser: true }">
            @csrf
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                    <div class="sm:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="user@email.com">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Name <span class="text-gray-400">(new users)</span></label>
                        <input type="text" name="name" value="{{ old('name') }}"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="John Doe">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password <span class="text-gray-400">(new)</span></label>
                        <input type="password" name="password"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="If new user">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
                        <select name="role"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($roles as $value => $label)
                                <option value="{{ $value }}">{{ explode(' — ', $label)[0] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-1 flex items-end">
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                            Add
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Role Permissions Reference -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">Role Permissions</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-white border-b border-gray-200">
                        <th class="text-left px-6 py-2.5 text-xs font-medium text-gray-500 uppercase">Role</th>
                        <th class="text-center px-3 py-2.5 text-xs font-medium text-gray-500 uppercase">Files</th>
                        <th class="text-center px-3 py-2.5 text-xs font-medium text-gray-500 uppercase">DBs</th>
                        <th class="text-center px-3 py-2.5 text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="text-center px-3 py-2.5 text-xs font-medium text-gray-500 uppercase">SSH</th>
                        <th class="text-center px-3 py-2.5 text-xs font-medium text-gray-500 uppercase">Cron</th>
                        <th class="text-center px-3 py-2.5 text-xs font-medium text-gray-500 uppercase">SSL</th>
                        <th class="text-center px-3 py-2.5 text-xs font-medium text-gray-500 uppercase">DNS</th>
                        <th class="text-center px-3 py-2.5 text-xs font-medium text-gray-500 uppercase">Settings</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach([
                        'Owner' => [1,1,1,1,1,1,1,1],
                        'Admin' => [1,1,1,1,1,1,1,0],
                        'Developer' => [1,1,0,1,1,0,0,0],
                        'Designer' => [1,0,0,0,0,0,0,0],
                        'Email Manager' => [0,0,1,0,0,0,0,0],
                        'Viewer' => [0,0,0,0,0,0,0,0],
                    ] as $roleName => $perms)
                        <tr>
                            <td class="px-6 py-2 font-medium text-gray-700">{{ $roleName }}</td>
                            @foreach($perms as $p)
                                <td class="text-center px-3 py-2">
                                    @if($p)
                                        <svg class="w-4 h-4 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    @else
                                        <svg class="w-4 h-4 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-gray-100">
            <p class="text-xs text-gray-400">Viewer role can see everything but cannot make changes.</p>
        </div>
    </div>

    <!-- Current Collaborators -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">Collaborators</h3>
        </div>
        @if($account->collaborators->isEmpty())
            <div class="px-6 py-12 text-center text-sm text-gray-400">No collaborators added yet. Only the account owner has access.</div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($account->collaborators as $collab)
                    <div class="flex items-center justify-between px-6 py-4">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-gray-600">{{ strtoupper(substr($collab->name, 0, 2)) }}</span>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">{{ $collab->name }}</div>
                                <div class="text-xs text-gray-500">{{ $collab->email }}</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <form action="{{ route('admin.collaborators.update-role', [$account, $collab]) }}" method="POST" class="flex items-center space-x-2">
                                @csrf
                                <select name="role" onchange="this.form.submit()"
                                    class="rounded-lg border-gray-300 shadow-sm text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach($roles as $value => $label)
                                        <option value="{{ $value }}" @selected($collab->pivot->role === $value)>{{ explode(' — ', $label)[0] }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <form action="{{ route('admin.collaborators.destroy', [$account, $collab]) }}" method="POST"
                                  onsubmit="return confirm('Remove {{ $collab->email }} from this account?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-admin-layout>
