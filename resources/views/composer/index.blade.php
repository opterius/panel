<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Composer</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    {{-- Domain selector --}}
    <div class="bg-white rounded-xl shadow-sm p-5 mb-6">
        <form method="GET" action="{{ route('user.composer.index') }}" class="flex items-end gap-4">
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

        @if(isset($info['error']))
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center mb-6">
                <svg class="w-10 h-10 mx-auto text-amber-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <p class="text-sm font-medium text-amber-800">No <code class="bg-amber-100 px-1 rounded">composer.json</code> found</p>
                <p class="text-xs text-amber-600 mt-1">Looking in: <span class="font-mono">{{ $workingDir }}</span></p>
                <p class="text-xs text-amber-600 mt-3">Upload your project files via FTP or SSH, then return here.</p>
            </div>
        @else
            {{-- Project info --}}
            <div class="bg-white rounded-xl shadow-sm p-5 mb-6">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-sm font-semibold text-gray-800">
                        {{ $info['name'] ?? 'Project' }}
                    </h3>
                    <div class="flex items-center gap-2">
                        @if($info['vendor_exists'] ?? false)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">vendor/ present</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">vendor/ missing — run install</span>
                        @endif
                        @if($info['has_lock'] ?? false)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">composer.lock</span>
                        @endif
                    </div>
                </div>
                @if($info['description'] ?? null)
                    <p class="text-xs text-gray-400">{{ $info['description'] }}</p>
                @endif
                <p class="text-xs text-gray-400 mt-1 font-mono">{{ $workingDir }}</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Package list --}}
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800">Packages</h3>
                    </div>

                    @if(empty($info['packages']))
                        <div class="px-6 py-8 text-center text-sm text-gray-400">No packages declared in composer.json.</div>
                    @else
                        {{-- Headers --}}
                        <div class="hidden sm:grid grid-cols-12 px-6 py-2 text-xs font-medium text-gray-400 uppercase tracking-wide border-b border-gray-100 bg-gray-50">
                            <div class="col-span-5">Package</div>
                            <div class="col-span-3">Required</div>
                            <div class="col-span-3">Installed</div>
                            <div class="col-span-1">Type</div>
                        </div>
                        <div class="divide-y divide-gray-50">
                            @foreach(collect($info['packages'])->sortBy('name') as $pkg)
                                <div class="grid grid-cols-12 items-center px-6 py-2.5 text-sm hover:bg-gray-50">
                                    <div class="col-span-5 font-mono text-gray-800 text-xs truncate" title="{{ $pkg['name'] }}">{{ $pkg['name'] }}</div>
                                    <div class="col-span-3 text-gray-400 text-xs font-mono">{{ $pkg['constraint'] }}</div>
                                    <div class="col-span-3 text-xs">
                                        @if($pkg['installed'])
                                            <span class="text-green-600 font-mono">{{ $pkg['installed'] }}</span>
                                        @else
                                            <span class="text-amber-500">not installed</span>
                                        @endif
                                    </div>
                                    <div class="col-span-1">
                                        <span class="text-xs {{ $pkg['type'] === 'require-dev' ? 'text-purple-500' : 'text-gray-400' }}">
                                            {{ $pkg['type'] === 'require-dev' ? 'dev' : 'prod' }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Commands panel --}}
                <div class="space-y-4">

                    {{-- Quick commands --}}
                    <div class="bg-white rounded-xl shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4">Run Command</h3>

                        <form action="{{ route('user.composer.run') }}" method="POST"
                              x-data="{ running: false }" @submit="running = true"
                              class="space-y-3">
                            @csrf
                            <input type="hidden" name="domain_id" value="{{ $selectedDomain->id }}">

                            {{-- Command selector --}}
                            <div x-data="{ cmd: 'install' }">
                                <label class="block text-xs font-medium text-gray-500 mb-2">Command</label>
                                <div class="flex flex-wrap gap-1.5 mb-3">
                                    @foreach(['install', 'update', 'dump-autoload'] as $cmd)
                                        <label class="relative cursor-pointer">
                                            <input type="radio" name="command" value="{{ $cmd }}"
                                                class="peer sr-only"
                                                x-model="cmd"
                                                @checked($cmd === 'install')>
                                            <div class="px-3 py-1.5 text-xs font-mono border border-gray-200 rounded-lg
                                                peer-checked:border-orange-500 peer-checked:bg-orange-50 peer-checked:text-orange-700
                                                hover:bg-gray-50 transition">
                                                {{ $cmd }}
                                            </div>
                                        </label>
                                    @endforeach
                                </div>

                                {{-- Options --}}
                                <div class="space-y-1.5">
                                    <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                                        <input type="checkbox" name="flags[]" value="--no-dev"
                                            class="rounded border-gray-300 text-orange-500 focus:ring-orange-400">
                                        <span class="font-mono">--no-dev</span>
                                    </label>
                                    <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                                        <input type="checkbox" name="flags[]" value="--optimize-autoloader"
                                            class="rounded border-gray-300 text-orange-500 focus:ring-orange-400">
                                        <span class="font-mono">--optimize-autoloader</span>
                                    </label>
                                    <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                                        <input type="checkbox" name="flags[]" value="--no-scripts"
                                            class="rounded border-gray-300 text-orange-500 focus:ring-orange-400">
                                        <span class="font-mono">--no-scripts</span>
                                    </label>
                                </div>

                                <button type="submit" :disabled="running"
                                    class="mt-4 w-full inline-flex items-center justify-center px-4 py-2.5 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition disabled:opacity-50">
                                    <template x-if="!running">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </template>
                                    <template x-if="running">
                                        <svg class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    </template>
                                    <span x-text="running ? 'Running...' : ('composer ' + cmd)">Run</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Require a package --}}
                    <div class="bg-white rounded-xl shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-3">Require Package</h3>
                        <form action="{{ route('user.composer.run') }}" method="POST"
                              x-data="{ running: false }" @submit="running = true"
                              class="space-y-3">
                            @csrf
                            <input type="hidden" name="domain_id" value="{{ $selectedDomain->id }}">
                            <input type="hidden" name="command" value="require">
                            <input type="text" name="packages"
                                placeholder="vendor/package or vendor/package:^2.0"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-xs font-mono focus:border-orange-500 focus:ring-orange-500"
                                value="{{ old('packages') }}">
                            <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer">
                                <input type="checkbox" name="flags[]" value="--no-dev"
                                    class="rounded border-gray-300 text-orange-500 focus:ring-orange-400">
                                <span class="font-mono">--dev</span>
                                <span class="text-gray-400">(add to require-dev)</span>
                            </label>
                            <button type="submit" :disabled="running"
                                class="w-full inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition disabled:opacity-50">
                                <span x-text="running ? 'Installing...' : 'composer require'">composer require</span>
                            </button>
                        </form>
                    </div>

                    {{-- Remove a package --}}
                    <div class="bg-white rounded-xl shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-3">Remove Package</h3>
                        <form action="{{ route('user.composer.run') }}" method="POST"
                              x-data="{ running: false }" @submit="running = true"
                              class="space-y-3">
                            @csrf
                            <input type="hidden" name="domain_id" value="{{ $selectedDomain->id }}">
                            <input type="hidden" name="command" value="remove">
                            <input type="text" name="packages"
                                placeholder="vendor/package"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-xs font-mono focus:border-orange-500 focus:ring-orange-500">
                            <button type="submit" :disabled="running"
                                class="w-full inline-flex items-center justify-center px-4 py-2 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition disabled:opacity-50">
                                <span x-text="running ? 'Removing...' : 'composer remove'">composer remove</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Output from last command --}}
        @if(session('composer_output') !== null)
            <div class="mt-6 bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-800">
                        Output — <span class="font-mono text-orange-600">composer {{ session('composer_command') }}</span>
                    </h3>
                </div>
                <pre class="p-4 bg-slate-900 text-slate-300 text-xs font-mono leading-relaxed overflow-x-auto max-h-96 overflow-y-auto whitespace-pre-wrap">{{ session('composer_output') ?: '(no output)' }}</pre>
            </div>
        @endif

    @else
        <div class="bg-white rounded-xl shadow-sm p-12 text-center text-gray-400 text-sm">
            Select a domain above to manage its Composer packages.
        </div>
    @endif
</x-user-layout>
