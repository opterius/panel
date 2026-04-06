<x-admin-layout>
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

    <!-- Server Selector -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">{{ __('php.select_server') }}</h3>
            <p class="text-sm text-gray-500 mt-1">{{ __('php.select_server_hint') }}</p>
        </div>
        <div class="px-6 py-5">
            <form method="GET" action="{{ route('admin.php.index') }}" class="flex items-end gap-4">
                <div class="flex-1">
                    <label for="server_id" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('php.server') }}</label>
                    <select name="server_id" id="server_id"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($servers as $server)
                            <option value="{{ $server->id }}" @selected($selectedServer && $selectedServer->id === $server->id)>
                                {{ $server->name }} ({{ $server->ip_address }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    {{ __('common.manage') }}
                </button>
            </form>
        </div>
    </div>

    @if($selectedServer)
        <!-- Installed PHP Versions -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">{{ __('php.php_versions') }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ __('php.installed_on', ['server' => $selectedServer->name]) }}</p>
            </div>

            @if(empty($versions))
                <div class="px-6 py-12 text-center">
                    <p class="text-sm text-gray-500">{{ __('php.cannot_retrieve_versions') }}</p>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-6">
                    @foreach($versions as $ver)
                        <div class="border rounded-xl p-4 text-center
                            @if($ver['installed'] && $ver['active']) border-green-200 bg-green-50
                            @elseif($ver['installed']) border-yellow-200 bg-yellow-50
                            @else border-gray-200 bg-gray-50
                            @endif">
                            <div class="text-lg font-bold
                                @if($ver['installed'] && $ver['active']) text-green-700
                                @elseif($ver['installed']) text-yellow-700
                                @else text-gray-400
                                @endif">
                                PHP {{ $ver['version'] }}
                            </div>
                            <div class="text-xs mt-1">
                                @if($ver['installed'] && $ver['active'])
                                    <span class="text-green-600 font-medium">{{ __('php.installed_and_running') }}</span>
                                @elseif($ver['installed'])
                                    <span class="text-yellow-600 font-medium">{{ __('php.installed_fpm_stopped') }}</span>
                                @else
                                    <span class="text-gray-400">{{ __('php.not_installed') }}</span>
                                @endif
                            </div>
                            @if(!$ver['installed'])
                                <div x-data="{ confirmInstall: false }" class="mt-3">
                                    <button type="button" @click="confirmInstall = true" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition">
                                        {{ __('php.install_php') }}
                                    </button>
                                    <template x-teleport="body">
                                        <div x-show="confirmInstall" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                                            <div x-show="confirmInstall" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="confirmInstall = false"></div>
                                            <div class="fixed inset-0 flex items-center justify-center p-4">
                                                <div x-show="confirmInstall" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.stop @keydown.escape.window="confirmInstall = false" class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
                                                    <div class="p-6 pb-0">
                                                        <div class="flex items-start space-x-4">
                                                            <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
                                                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                                                            </div>
                                                            <div>
                                                                <h3 class="text-lg font-semibold text-gray-900">{{ __('php.install_php') }} {{ $ver['version'] }}</h3>
                                                                <p class="mt-1 text-sm text-gray-500">{{ __('php.install_php_confirm', ['version' => $ver['version']]) }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center justify-end space-x-3 px-6 py-5 mt-2">
                                                        <button type="button" @click="confirmInstall = false" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">{{ __('common.cancel') }}</button>
                                                        <form action="{{ route('admin.php.install') }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                                                            <input type="hidden" name="version" value="{{ $ver['version'] }}">
                                                            <button type="submit" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">{{ __('php.install_php') }}</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- PHP Extensions -->
        @php
            $installedVersionsList = collect($versions)->where('installed', true)->pluck('version')->toArray();
        @endphp
        @if(!empty($installedVersionsList))
            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">{{ __('php.php_extensions') }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ __('php.php_extensions_hint') }}</p>
                </div>
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <div class="flex flex-wrap gap-2">
                        @foreach($installedVersionsList as $ver)
                            <a href="{{ route('admin.php.index', ['server_id' => $selectedServer->id, 'php_version' => $ver]) }}"
                               class="px-4 py-1.5 text-sm font-medium rounded-lg transition
                                @if(($selectedVersion ?? '') === $ver) bg-indigo-600 text-white
                                @else bg-white border border-gray-200 text-gray-700 hover:bg-gray-50
                                @endif">
                                PHP {{ $ver }}
                            </a>
                        @endforeach
                    </div>
                </div>

                @if(!empty($extensions))
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 p-6">
                        @foreach($extensions as $ext)
                            <div class="flex items-center justify-between border rounded-lg px-3 py-2
                                @if($ext['installed']) border-green-200 bg-green-50 @else border-gray-200 bg-gray-50 @endif">
                                <span class="text-sm font-medium @if($ext['installed']) text-green-800 @else text-gray-500 @endif">
                                    {{ $ext['name'] }}
                                </span>
                                <form action="{{ route('admin.php.extension') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="server_id" value="{{ $selectedServer->id }}">
                                    <input type="hidden" name="version" value="{{ $selectedVersion }}">
                                    <input type="hidden" name="extension" value="{{ $ext['name'] }}">
                                    <input type="hidden" name="enable" value="{{ $ext['installed'] ? '0' : '1' }}">
                                    <button type="submit" class="text-xs font-medium px-2 py-1 rounded transition
                                        @if($ext['installed']) text-red-600 hover:bg-red-100
                                        @else text-green-600 hover:bg-green-100
                                        @endif">
                                        {{ $ext['installed'] ? __('common.disable') : __('common.enable') }}
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-6 py-8 text-center text-sm text-gray-400">
                        {{ __('php.select_version_to_view_extensions') }}
                    </div>
                @endif
            </div>
        @endif

        <!-- Per-Domain PHP Settings -->
        @if($domains->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">{{ __('php.domain_php_version') }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ __('php.domain_php_version_hint') }}</p>
                </div>
                <div class="divide-y divide-gray-100">
                    @php
                        $installedVersions = collect($versions)->where('installed', true)->pluck('version')->toArray();
                    @endphp
                    @foreach($domains as $domain)
                        <div class="flex items-center justify-between px-6 py-4" x-data="{ showConfig: false }">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-800">{{ $domain->domain }}</div>
                                    <div class="text-xs text-gray-500">{{ $domain->account->username }} &middot; Current: PHP {{ $domain->php_version }}</div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3">
                                <form action="{{ route('admin.php.switch') }}" method="POST" class="flex items-center space-x-2">
                                    @csrf
                                    <input type="hidden" name="domain_id" value="{{ $domain->id }}">
                                    <select name="new_version"
                                        class="rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach($installedVersions as $ver)
                                            <option value="{{ $ver }}" @selected($domain->php_version === $ver)>PHP {{ $ver }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="inline-flex items-center px-3 py-2 text-sm font-medium text-indigo-600 bg-white border border-indigo-300 rounded-lg hover:bg-indigo-50 transition">
                                        {{ __('php.switch_version') }}
                                    </button>
                                </form>

                                <button @click="showConfig = !showConfig" class="text-gray-400 hover:text-gray-600 transition" title="{{ __('php.php_configuration') }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Expandable PHP Config --}}
                        <div x-data="{ showConfig: false }" x-show="false" class="hidden">{{-- placeholder for Alpine scope --}}</div>
                    @endforeach
                </div>
            </div>

            <!-- PHP Config per Domain -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden" x-data="{ selectedDomain: '' }">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">{{ __('php.php_configuration') }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ __('php.php_configuration_hint') }}</p>
                </div>
                <form action="{{ route('admin.php.config') }}" method="POST" class="px-6 py-5 space-y-5">
                    @csrf
                    <div>
                        <label for="config_domain_id" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('php.domain') }}</label>
                        <select name="domain_id" id="config_domain_id"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($domains as $domain)
                                <option value="{{ $domain->id }}">{{ $domain->domain }} (PHP {{ $domain->php_version }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('php.memory_limit') }}</label>
                            <input type="text" name="memory_limit" value="256M" placeholder="e.g. 256M"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('php.upload_max_filesize') }}</label>
                            <input type="text" name="upload_max_filesize" value="64M" placeholder="e.g. 64M"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('php.post_max_size') }}</label>
                            <input type="text" name="post_max_size" value="64M" placeholder="e.g. 64M"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('php.max_execution_time') }}</label>
                            <input type="number" name="max_execution_time" value="30" placeholder="{{ __('common.seconds') }}"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('php.display_errors') }}</label>
                            <select name="display_errors"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="Off">{{ __('php.off_production') }}</option>
                                <option value="On">{{ __('php.on_development') }}</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        {{ __('php.save_configuration') }}
                    </button>
                </form>
            </div>
        @endif
    @endif
</x-admin-layout>
