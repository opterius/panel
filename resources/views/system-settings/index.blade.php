<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">{{ __('system-settings.page_title') }}</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <p class="text-sm text-gray-500 mb-6">{{ __('system-settings.page_subtitle') }}</p>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        {{-- ── Sidebar: categories ──────────────────────────────────────── --}}
        <aside class="lg:col-span-1">
            <nav class="bg-white rounded-xl shadow-sm overflow-hidden">
                @foreach($categories as $slug => $cat)
                    <a href="{{ route('admin.system-settings.index', ['category' => $slug]) }}"
                       class="flex items-center gap-3 px-4 py-3 border-b border-gray-50 last:border-b-0 transition
                              {{ $category === $slug ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $cat['icon'] }}"/>
                        </svg>
                        <span class="text-sm">{{ __('system-settings.cat_' . $slug) }}</span>
                        @if(!$cat['ready'])
                            <span class="ml-auto text-[10px] uppercase tracking-wider text-amber-600 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded">Soon</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </aside>

        {{-- ── Right panel: category content ────────────────────────────── --}}
        <main class="lg:col-span-3">
            <div class="bg-white rounded-xl shadow-sm p-6 sm:p-8">

                @if(!$categories[$category]['ready'])
                    {{-- Coming soon placeholder for unimplemented categories --}}
                    <div class="text-center py-16">
                        <svg class="mx-auto w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="text-base font-medium text-gray-700 mb-1">{{ __('system-settings.coming_soon') }}</h3>
                        <p class="text-sm text-gray-500 max-w-md mx-auto">{{ __('system-settings.coming_soon_text') }}</p>
                    </div>

                @elseif($category === 'domains')
                    {{-- ── Domains category ─────────────────────────────── --}}
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ __('system-settings.domains_title') }}</h3>
                    <p class="text-sm text-gray-500 mb-6">{{ __('system-settings.domains_subtitle') }}</p>

                    <form action="{{ route('admin.system-settings.update', ['category' => 'domains']) }}" method="POST">
                        @csrf

                        {{-- Setting #1: default_php_version --}}
                        <div class="mb-6">
                            <label for="default_php_version" class="block text-sm font-medium text-gray-700 mb-1.5">
                                {{ __('system-settings.default_php_version_label') }}
                            </label>
                            <p class="text-xs text-gray-500 mb-2">{{ __('system-settings.default_php_version_hint') }}</p>
                            <select name="default_php_version" id="default_php_version"
                                    class="w-full max-w-xs rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($extra['php_versions'] as $version)
                                    <option value="{{ $version }}" @selected($extra['default_php_version'] === $version)>
                                        PHP {{ $version }}
                                    </option>
                                @endforeach
                            </select>
                            @error('default_php_version')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="pt-4 border-t border-gray-100">
                            <button type="submit"
                                    class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                                {{ __('system-settings.save') }}
                            </button>
                        </div>
                    </form>

                @elseif($category === 'integrations')
                    {{-- ── Integrations category ─────────────────────────── --}}
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">Third-Party Integrations</h3>
                    <p class="text-sm text-gray-500 mb-6">API credentials for external services that the panel uses on behalf of customers.</p>

                    <form action="{{ route('admin.system-settings.update', ['category' => 'integrations']) }}" method="POST">
                        @csrf

                        {{-- ── BunnyCDN ────────────────────────────────────── --}}
                        <div class="rounded-xl border border-gray-200 p-5 mb-5">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h4 class="font-bold text-gray-900">BunnyCDN</h4>
                                    <p class="text-xs text-gray-500 mt-0.5">Image and asset CDN. Customers can enable per-domain acceleration powered by your BunnyCDN account.</p>
                                </div>
                                @if (! empty($settings['bunnycdn_api_key']))
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 bg-green-100 px-2.5 py-1 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Configured
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-gray-600 bg-gray-100 px-2.5 py-1 rounded-full">
                                        Not configured
                                    </span>
                                @endif
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">API Key</label>
                                    <input type="password" name="bunnycdn_api_key" autocomplete="off"
                                           value="{{ $settings['bunnycdn_api_key'] ?? '' }}"
                                           placeholder="Paste your BunnyCDN API key"
                                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                                    <p class="text-xs text-gray-500 mt-1">
                                        Get your key from <a href="https://dash.bunny.net/account/api-keys" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:underline">dash.bunny.net/account/api-keys</a>. Leave empty to clear.
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Zone Name Prefix</label>
                                    <input type="text" name="bunnycdn_prefix" maxlength="32" pattern="[a-z0-9-]*"
                                           value="{{ $settings['bunnycdn_prefix'] ?? 'opterius' }}"
                                           class="w-full max-w-sm rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <p class="text-xs text-gray-500 mt-1">Used to namespace pull zone names so they don't collide with other BunnyCDN customers. Lowercase letters, digits, and hyphens only.</p>
                                </div>
                            </div>
                        </div>

                        {{-- ── MaxMind GeoLite2 ─────────────────────────────── --}}
                        <div class="rounded-xl border border-gray-200 p-5 mb-5">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h4 class="font-bold text-gray-900">MaxMind GeoLite2</h4>
                                    <p class="text-xs text-gray-500 mt-0.5">Free IP-to-country database. Powers the country flags in the visitor analytics dashboard. Without this, country stats are blank.</p>
                                </div>
                                @if (! empty($settings['maxmind_license_key']))
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 bg-green-100 px-2.5 py-1 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Configured
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-gray-600 bg-gray-100 px-2.5 py-1 rounded-full">
                                        Not configured
                                    </span>
                                @endif
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Account ID</label>
                                    <input type="text" name="maxmind_account_id" autocomplete="off"
                                           value="{{ $settings['maxmind_account_id'] ?? '' }}"
                                           placeholder="123456"
                                           class="w-full max-w-sm rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">License Key</label>
                                    <input type="password" name="maxmind_license_key" autocomplete="off"
                                           value="{{ $settings['maxmind_license_key'] ?? '' }}"
                                           placeholder="Paste your MaxMind license key"
                                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                                    <p class="text-xs text-gray-500 mt-1">
                                        Sign up free at <a href="https://www.maxmind.com/en/geolite2/signup" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:underline">maxmind.com/en/geolite2/signup</a>, then generate a license key under Account → Manage License Keys.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 pt-4 border-t border-gray-100 flex items-center justify-between gap-3">
                                <p class="text-xs text-gray-500">After saving credentials, click Download to install the database on every server.</p>
                                <button type="button"
                                        onclick="document.getElementById('maxmind-download-form').submit();"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Download GeoLite2
                                </button>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100">
                            <button type="submit"
                                    class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                                {{ __('system-settings.save') }}
                            </button>
                        </div>
                    </form>

                    {{-- Separate form for the download trigger so it doesn't try to save the form fields too --}}
                    <form id="maxmind-download-form" action="{{ route('admin.system-settings.maxmind-download') }}" method="POST" class="hidden">
                        @csrf
                    </form>
                @endif

            </div>
        </main>
    </div>
</x-admin-layout>
