<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">{{ __('php.php_versions') }}</h2>
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

    <div class="mb-6">
        <p class="text-sm text-gray-500">{{ __('php.manage_php_versions') }}</p>
    </div>

    @if($domains->isEmpty())
        <div class="bg-white rounded-xl shadow-sm px-6 py-16 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            <h3 class="mt-4 text-base font-medium text-gray-700">{{ __('php.no_domains') }}</h3>
            <p class="mt-2 text-sm text-gray-500">{{ __('php.no_domains_hint') }}</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($domains as $domain)
                @php
                    $cfg = $configs[$domain->id] ?? [];
                    $disabledFuncs = array_filter(array_map('trim', explode(',', $cfg['disable_functions'] ?? 'exec,passthru,shell_exec,system,proc_open,popen')));
                @endphp
                <div class="bg-white rounded-xl shadow-sm overflow-hidden" x-data="{ switching: false, settings: false }">
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">{{ $domain->domain }}</div>
                                <div class="text-xs text-gray-500">{{ $domain->account->username }}</div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold bg-indigo-100 text-indigo-700">
                                PHP {{ $domain->php_version }}
                            </span>
                            <button @click="settings = !settings; switching = false" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium transition">
                                <span x-text="settings ? '{{ __('common.cancel') }}' : 'Settings'"></span>
                            </button>
                            @if($domain->account->package?->php_switch_enabled ?? false)
                                <button @click="switching = !switching; settings = false" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium transition">
                                    <span x-text="switching ? '{{ __('common.cancel') }}' : '{{ __('php.change') }}'"></span>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- PHP Settings (php.ini overrides) --}}
                    <div x-show="settings" x-collapse class="px-6 py-5 bg-gray-50 border-t border-gray-100">
                        <form action="{{ route('user.php.config') }}" method="POST">
                            @csrf
                            <input type="hidden" name="domain_id" value="{{ $domain->id }}">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">memory_limit</label>
                                    <input type="text" name="memory_limit" value="{{ $cfg['memory_limit'] ?? '256M' }}" placeholder="256M"
                                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">max_execution_time</label>
                                    <input type="number" name="max_execution_time" value="{{ $cfg['max_execution_time'] ?? '30' }}" placeholder="30"
                                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">upload_max_filesize</label>
                                    <input type="text" name="upload_max_filesize" value="{{ $cfg['upload_max_filesize'] ?? '64M' }}" placeholder="64M"
                                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">post_max_size</label>
                                    <input type="text" name="post_max_size" value="{{ $cfg['post_max_size'] ?? '64M' }}" placeholder="64M"
                                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">max_input_vars</label>
                                    <input type="number" name="max_input_vars" value="{{ $cfg['max_input_vars'] ?? '1000' }}" placeholder="1000"
                                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">display_errors</label>
                                    <select name="display_errors"
                                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="Off" @selected(($cfg['display_errors'] ?? 'Off') === 'Off')>Off</option>
                                        <option value="On" @selected(($cfg['display_errors'] ?? 'Off') === 'On')>On</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">allow_url_fopen</label>
                                    <select name="allow_url_fopen"
                                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="On" @selected(($cfg['allow_url_fopen'] ?? 'On') === 'On')>On</option>
                                        <option value="Off" @selected(($cfg['allow_url_fopen'] ?? 'On') === 'Off')>Off</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-xs font-medium text-gray-600 mb-2">Enabled functions <span class="text-gray-400 font-normal">(check to allow; unchecked = disabled)</span></label>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                    @foreach($toggleableFuncs as $fn)
                                        <label class="inline-flex items-center text-sm">
                                            <input type="checkbox" name="enabled_funcs[]" value="{{ $fn }}"
                                                @checked(!in_array($fn, $disabledFuncs))
                                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="ml-2 font-mono text-xs text-gray-700">{{ $fn }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                                    Save Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    @if(!($domain->account->package?->php_switch_enabled ?? false))
                        <div class="px-6 py-3 bg-gray-50 border-t border-gray-100">
                            <p class="text-xs text-gray-500">{{ __('php.switch_not_available') }}</p>
                        </div>
                    @endif

                    {{-- Version Switcher --}}
                    <div x-show="switching" x-collapse class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                        <form action="{{ route('user.php.switch') }}" method="POST">
                            @csrf
                            <input type="hidden" name="domain_id" value="{{ $domain->id }}">
                            <p class="text-sm text-gray-600 mb-3">{{ __('php.select_new_version') }}</p>
                            <div class="flex flex-wrap gap-2 mb-4">
                                @foreach($versions as $ver)
                                    @php
                                        $allowed = true;
                                        if ($domain->account->package) {
                                            $pkgVersions = $domain->account->package->php_versions ?? [];
                                            $allowed = empty($pkgVersions) || in_array($ver, $pkgVersions);
                                        }
                                        $isCurrent = $ver === $domain->php_version;
                                    @endphp
                                    @if($allowed)
                                        @if($isCurrent)
                                            <div class="px-4 py-2 border border-gray-300 bg-gray-100 text-gray-500 rounded-lg text-sm font-medium opacity-60 cursor-not-allowed">
                                                PHP {{ $ver }} ({{ __('php.current') }})
                                            </div>
                                        @else
                                            <label class="relative">
                                                <input type="radio" name="new_version" value="{{ $ver }}" class="peer sr-only">
                                                <div class="px-4 py-2 border border-gray-200 rounded-lg cursor-pointer text-sm font-medium
                                                    peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-700
                                                    hover:bg-gray-50 transition">
                                                    PHP {{ $ver }}
                                                </div>
                                            </label>
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                                {{ __('php.switch_version') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-user-layout>
