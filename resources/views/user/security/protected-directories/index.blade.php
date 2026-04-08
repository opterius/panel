<x-user-layout>
    <x-slot name="title">Directory Password Protection</x-slot>

    <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

        @if (session('success'))
            <div class="mb-6 rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900">Directory Password Protection</h1>
            <p class="text-slate-500 mt-1">Protect a directory with HTTP Basic Authentication. Visitors will be prompted for a username and password before accessing files inside.</p>
        </div>

        @foreach ($domains as $domain)
            <div class="bg-white rounded-2xl border border-slate-200 mb-6" x-data="{ open: false }">
                <div class="flex items-center justify-between p-5 border-b border-slate-100">
                    <div>
                        <h2 class="font-bold text-slate-900">{{ $domain->domain }}</h2>
                        <p class="text-xs text-slate-500">Account: {{ $domain->account->username }}</p>
                    </div>
                    <button type="button" @click="open = !open" class="text-sm font-semibold text-orange-600 hover:text-orange-700">
                        <span x-show="!open">+ Add protected directory</span>
                        <span x-show="open">Cancel</span>
                    </button>
                </div>

                {{-- Add form --}}
                <div x-show="open" x-cloak class="p-5 bg-orange-50/30 border-b border-slate-100">
                    <form method="POST" action="{{ route('user.security.directories.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @csrf
                        <input type="hidden" name="domain_id" value="{{ $domain->id }}">

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Directory path (relative to public_html)</label>
                            <input type="text" name="path" required placeholder="admin or wp-admin or private/files"
                                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Realm label (optional)</label>
                            <input type="text" name="label" placeholder="Restricted Area"
                                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500">
                        </div>

                        <div></div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Username</label>
                            <input type="text" name="username" required
                                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                            <input type="password" name="password" required minlength="6"
                                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500">
                        </div>

                        <div class="md:col-span-2 flex justify-end">
                            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                                Create
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Existing protected directories --}}
                @if ($domain->protectedDirectories->isEmpty())
                    <div class="p-5 text-center text-slate-500 text-sm">No protected directories.</div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($domain->protectedDirectories as $dir)
                            <div class="p-5">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <div class="font-semibold text-slate-900 font-mono text-sm">/{{ $dir->path }}/</div>
                                        @if ($dir->label)
                                            <div class="text-xs text-slate-500 mt-0.5">Label: "{{ $dir->label }}"</div>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('user.security.directories.destroy', $dir) }}"
                                          onsubmit="return confirm('Remove protection from this directory?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">Remove protection</button>
                                    </form>
                                </div>

                                {{-- User list --}}
                                <div class="bg-slate-50 rounded-lg p-3 mb-3">
                                    <div class="text-xs font-semibold text-slate-700 mb-2">Users ({{ $dir->users->count() }}):</div>
                                    <div class="space-y-1.5">
                                        @foreach ($dir->users as $u)
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="font-mono text-slate-700">{{ $u->username }}</span>
                                                <form method="POST" action="{{ route('user.security.directories.users.destroy', $u) }}"
                                                      onsubmit="return confirm('Remove user {{ $u->username }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs text-red-600 hover:text-red-700">remove</button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Add user inline form --}}
                                <form method="POST" action="{{ route('user.security.directories.users.store', $dir) }}"
                                      class="flex flex-wrap items-end gap-2">
                                    @csrf
                                    <input type="text" name="username" required placeholder="username"
                                           class="flex-1 min-w-[120px] border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-orange-500">
                                    <input type="password" name="password" required minlength="6" placeholder="password"
                                           class="flex-1 min-w-[120px] border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-orange-500">
                                    <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold px-4 py-1.5 rounded-lg transition">
                                        Add user
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        @if ($domains->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-500">
                You have no domains yet. Add a domain first to set up directory protection.
            </div>
        @endif

    </div>
</x-user-layout>
