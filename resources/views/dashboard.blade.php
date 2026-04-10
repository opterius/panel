<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">{{ __('dashboard.dashboard') }}</h2>
    </x-slot>

    {{-- License activation banner — shown until a valid license key is configured --}}
    @php
        $licenseKey = config('opterius.license_key') ?: env('OPTERIUS_LICENSE_KEY', '');
    @endphp
    @if(Auth::user()->isAdmin() && empty($licenseKey))
        <div class="mb-6 bg-amber-50 border border-amber-200 rounded-xl p-5 flex items-start gap-4">
            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="font-bold text-amber-900">Activate your free license</h3>
                <p class="text-sm text-amber-800 mt-1">
                    Your panel is running but not yet licensed. Get a free license key (5 hosting accounts included) to unlock all features.
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.license.index') }}" class="inline-flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        I have a license key
                    </a>
                    <a href="https://opterius.com/register" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-semibold text-amber-700 hover:text-amber-900 transition">
                        Get a free license →
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center space-x-4">
            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">{{ __('dashboard.domains') }}</div>
                <div class="text-2xl font-bold text-gray-900">{{ Auth::user()->isAdmin() ? \App\Models\Domain::count() : 0 }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center space-x-4">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">{{ __('dashboard.databases') }}</div>
                <div class="text-2xl font-bold text-gray-900">{{ Auth::user()->isAdmin() ? \App\Models\Database::count() : 0 }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center space-x-4">
            <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">{{ __('dashboard.accounts') }}</div>
                <div class="text-2xl font-bold text-gray-900">{{ Auth::user()->isAdmin() ? \App\Models\Account::count() : 0 }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center space-x-4">
            <div class="w-12 h-12 bg-rose-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">{{ __('dashboard.ssl_certificates') }}</div>
                <div class="text-2xl font-bold text-gray-900">{{ Auth::user()->isAdmin() ? \App\Models\SslCertificate::count() : 0 }}</div>
            </div>
        </div>
    </div>

    <!-- Server Resource Usage + Server Info -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Resource Usage -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-6">{{ __('dashboard.resource_usage') }}</h3>

            <div class="space-y-6">
                <!-- CPU -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-600">{{ __('dashboard.cpu') }}</span>
                        <span class="text-sm font-semibold text-gray-800">--</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3">
                        <div class="bg-indigo-500 h-3 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Memory -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-600">{{ __('dashboard.memory') }}</span>
                        <span class="text-sm font-semibold text-gray-800">--</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3">
                        <div class="bg-green-500 h-3 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Disk -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-600">{{ __('dashboard.disk') }}</span>
                        <span class="text-sm font-semibold text-gray-800">--</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3">
                        <div class="bg-amber-500 h-3 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Load Average -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-600">{{ __('dashboard.load_average') }}</span>
                        <span class="text-sm font-semibold text-gray-800">--</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3">
                        <div class="bg-rose-500 h-3 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <p class="text-xs text-gray-400 mt-6">{{ __('dashboard.agent_live_data') }}</p>
        </div>

        <!-- Server Info -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-6">{{ __('dashboard.server_info') }}</h3>

            <dl class="space-y-4">
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('dashboard.hostname') }}</dt>
                    <dd class="mt-1 text-sm text-gray-800">--</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('dashboard.ip_address') }}</dt>
                    <dd class="mt-1 text-sm text-gray-800">--</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('dashboard.operating_system') }}</dt>
                    <dd class="mt-1 text-sm text-gray-800">--</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('dashboard.uptime') }}</dt>
                    <dd class="mt-1 text-sm text-gray-800">--</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('dashboard.php_version') }}</dt>
                    <dd class="mt-1 text-sm text-gray-800">--</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('dashboard.mysql_version') }}</dt>
                    <dd class="mt-1 text-sm text-gray-800">--</dd>
                </div>
            </dl>

            <p class="text-xs text-gray-400 mt-6">{{ __('dashboard.agent_data') }}</p>
        </div>
    </div>

    <!-- Quick Actions + Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">{{ __('dashboard.quick_actions') }}</h3>

            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('domains.index') }}" class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    <span class="text-sm font-medium text-gray-700">{{ __('dashboard.add_domain') }}</span>
                </a>
                <a href="{{ route('databases.index') }}" class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    <span class="text-sm font-medium text-gray-700">{{ __('dashboard.create_database') }}</span>
                </a>
                <a href="{{ route('ssl.index') }}" class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    <span class="text-sm font-medium text-gray-700">{{ __('dashboard.issue_ssl') }}</span>
                </a>
                <a href="{{ route('filemanager.index') }}" class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                    <span class="text-sm font-medium text-gray-700">{{ __('dashboard.file_manager') }}</span>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">{{ __('dashboard.recent_activity') }}</h3>

            <div class="text-sm text-gray-400 py-8 text-center">
                {{ __('dashboard.no_activity') }}
            </div>
        </div>
    </div>
</x-app-layout>
