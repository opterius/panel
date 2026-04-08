<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Git</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    {{-- Domain selector --}}
    <div class="bg-white rounded-xl shadow-sm p-5 mb-6">
        <form method="GET" action="{{ route('user.git.index') }}" class="flex items-end gap-4">
            <div class="flex-1 max-w-sm">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Domain</label>
                <select name="domain_id" onchange="this.form.submit()"
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-orange-500 focus:ring-orange-500">
                    @foreach($domains as $domain)
                        <option value="{{ $domain->id }}"
                            @selected($selectedDomain?->id == $domain->id)>
                            {{ $domain->domain }} — {{ $domain->account->server->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if($selectedDomain)
        @php $workingDir = dirname($selectedDomain->document_root); @endphp

        @if(!($status['initialized'] ?? false))
            {{-- ── No repo: show clone form ── --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        <p class="text-sm font-medium text-gray-700 mb-1">No repository found</p>
                        <p class="text-xs text-gray-400 font-mono">{{ $workingDir }}</p>
                        <p class="text-xs text-gray-400 mt-2">Clone a repository below to get started.</p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-4">Clone Repository</h3>
                    <form action="{{ route('user.git.clone') }}" method="POST"
                          x-data="{ running: false }" @submit="running = true"
                          class="space-y-3">
                        @csrf
                        <input type="hidden" name="domain_id" value="{{ $selectedDomain->id }}">

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Repository URL</label>
                            <input type="text" name="repo_url"
                                placeholder="https://github.com/user/repo.git"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-xs font-mono focus:border-orange-500 focus:ring-orange-500"
                                value="{{ old('repo_url') }}" required>
                            <p class="text-xs text-gray-400 mt-1">HTTPS or git@ SSH URL</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Branch <span class="text-gray-400 font-normal">(optional, default: main)</span></label>
                            <input type="text" name="branch"
                                placeholder="main"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-xs font-mono focus:border-orange-500 focus:ring-orange-500"
                                value="{{ old('branch') }}">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Access Token <span class="text-gray-400 font-normal">(optional, private repos)</span></label>
                            <input type="password" name="access_token"
                                placeholder="ghp_xxxxxxxxxxxx"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-xs font-mono focus:border-orange-500 focus:ring-orange-500">
                            <p class="text-xs text-gray-400 mt-1">GitHub/GitLab personal access token. Stored in <code class="bg-gray-100 px-0.5 rounded">.git/config</code> for future pulls.</p>
                        </div>

                        <button type="submit" :disabled="running"
                            class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition disabled:opacity-50">
                            <template x-if="!running">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </template>
                            <template x-if="running">
                                <svg class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </template>
                            <span x-text="running ? 'Cloning...' : 'git clone'">git clone</span>
                        </button>
                    </form>
                </div>
            </div>

        @else
            {{-- ── Repo exists: show status + controls ── --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Commit log --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Status card --}}
                    <div class="bg-white rounded-xl shadow-sm p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                        {{ $status['branch'] ?: 'detached HEAD' }}
                                    </span>
                                    @if($status['clean'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">clean</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">uncommitted changes</span>
                                    @endif
                                </div>
                                @if($status['remote_url'])
                                    <p class="text-xs text-gray-400 font-mono truncate">{{ $status['remote_url'] }}</p>
                                @endif
                            </div>
                            <p class="text-xs text-gray-400 font-mono">{{ $workingDir }}</p>
                        </div>

                        @if(!empty($status['last_commit']))
                            @php $commit = $status['last_commit']; @endphp
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <p class="text-xs text-gray-500 mb-1">Last commit</p>
                                <div class="flex items-center gap-2">
                                    <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded text-gray-600">{{ $commit['hash'] }}</code>
                                    <span class="text-sm text-gray-800">{{ $commit['message'] }}</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">{{ $commit['author'] }} · {{ $commit['date'] }}</p>
                            </div>
                        @endif

                        @if($status['status'])
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <p class="text-xs text-gray-500 mb-2">Working tree</p>
                                <pre class="text-xs font-mono text-gray-600 bg-gray-50 rounded-lg p-3 overflow-x-auto">{{ $status['status'] }}</pre>
                            </div>
                        @endif
                    </div>

                    {{-- Commit log table --}}
                    @if(!empty($log))
                        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-800">Recent Commits</h3>
                            </div>
                            <div class="divide-y divide-gray-50">
                                @foreach($log as $entry)
                                    <div class="flex items-center gap-4 px-6 py-3 text-sm hover:bg-gray-50">
                                        <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded text-gray-500 shrink-0">{{ $entry['hash'] }}</code>
                                        <span class="flex-1 text-gray-800 text-xs truncate">{{ $entry['message'] }}</span>
                                        <span class="text-xs text-gray-400 shrink-0">{{ $entry['author'] }}</span>
                                        <span class="text-xs text-gray-300 shrink-0 hidden sm:block">{{ \Carbon\Carbon::parse($entry['date'])->diffForHumans() }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Actions panel --}}
                <div class="space-y-4">

                    {{-- Pull --}}
                    <div class="bg-white rounded-xl shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-3">Pull Latest</h3>
                        <form action="{{ route('user.git.pull') }}" method="POST"
                              x-data="{ running: false, needsToken: false }" @submit="running = true"
                              class="space-y-3">
                            @csrf
                            <input type="hidden" name="domain_id" value="{{ $selectedDomain->id }}">

                            <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer">
                                <input type="checkbox" x-model="needsToken"
                                    class="rounded border-gray-300 text-orange-500 focus:ring-orange-400">
                                Private repo — provide token
                            </label>

                            <div x-show="needsToken" x-cloak>
                                <input type="password" name="access_token"
                                    placeholder="Access token"
                                    class="w-full rounded-lg border-gray-300 shadow-sm text-xs font-mono focus:border-orange-500 focus:ring-orange-500">
                            </div>

                            <button type="submit" :disabled="running"
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition disabled:opacity-50">
                                <template x-if="!running">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                </template>
                                <template x-if="running">
                                    <svg class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                </template>
                                <span x-text="running ? 'Pulling...' : 'git pull'">git pull</span>
                            </button>
                        </form>
                    </div>

                    {{-- Info --}}
                    <div class="bg-gray-50 rounded-xl p-4 text-xs text-gray-500 space-y-1">
                        <p class="font-medium text-gray-600">Working directory</p>
                        <p class="font-mono break-all">{{ $workingDir }}</p>
                        <p class="pt-2 font-medium text-gray-600">Remote</p>
                        <p class="font-mono break-all">{{ $status['remote_url'] ?: '(none)' }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Output from last command --}}
        @if(session('git_output') !== null)
            <div class="mt-6 bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-800">
                        Output — <span class="font-mono text-orange-600">git {{ session('git_command') }}</span>
                    </h3>
                </div>
                <pre class="p-4 bg-slate-900 text-slate-300 text-xs font-mono leading-relaxed overflow-x-auto max-h-96 overflow-y-auto whitespace-pre-wrap">{{ session('git_output') ?: '(no output)' }}</pre>
            </div>
        @endif

    @else
        <div class="bg-white rounded-xl shadow-sm p-12 text-center text-gray-400 text-sm">
            Select a domain above to manage its Git repository.
        </div>
    @endif
</x-user-layout>
