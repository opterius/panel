<aside class="w-64 bg-gray-900 text-gray-300 flex flex-col shrink-0">
    <!-- Logo -->
    <div class="h-16 flex items-center px-6 border-b border-gray-800">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center">
            <img src="{{ asset('img/logo.png') }}" alt="Opterius">
        </a>
    </div>

    <!-- Switcher -->
    <div class="px-3 py-3 border-b border-gray-800">
        <div class="flex items-center rounded-lg bg-gray-800 p-1">
            <span class="flex-1 text-center py-1.5 text-xs font-semibold rounded-md bg-indigo-600 text-white">
                {{ __('common.server_mode') }}
            </span>
            <a href="{{ route('user.dashboard') }}" class="flex-1 text-center py-1.5 text-xs font-medium text-gray-400 hover:text-white transition rounded-md">
                {{ __('common.hosting_mode') }}
            </a>
        </div>
    </div>

    @php $user = Auth::user(); @endphp

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <x-sidebar-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z" /></svg>
            </x-slot>
            {{ __('dashboard.dashboard') }}
        </x-sidebar-link>

        @if($user->isAdmin())
            <x-sidebar-link href="{{ route('admin.monitor.index') }}" :active="request()->routeIs('admin.monitor.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                </x-slot>
                {{ __('common.monitor') }}
            </x-sidebar-link>

            <x-sidebar-link href="{{ route('admin.alerts.index') }}" :active="request()->routeIs('admin.alerts.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                </x-slot>
                {{ __('common.alerts') }}
            </x-sidebar-link>

            <x-sidebar-link href="{{ route('admin.security.index') }}" :active="request()->routeIs('admin.security.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                </x-slot>
                {{ __('common.security') }}
            </x-sidebar-link>
        @endif

        @if($user->isAdmin() || $user->resellerCan('activity.view'))
            <x-sidebar-link href="{{ route('admin.activity-log.index') }}" :active="request()->routeIs('admin.activity-log.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                </x-slot>
                {{ __('activity.activity_log') }}
            </x-sidebar-link>
        @endif

        @if($user->isAdmin() || $user->resellerCan('backup.manage'))
            <x-sidebar-link href="{{ route('admin.backups.index') }}" :active="request()->routeIs('admin.backups.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                </x-slot>
                {{ __('backups.backups') }}
            </x-sidebar-link>
        @endif

        <div class="pt-4 pb-2 px-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('servers.server') }}</span>
        </div>

        @if($user->isAdmin())
            <x-sidebar-link href="{{ route('admin.servers.index') }}" :active="request()->routeIs('admin.servers.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>
                </x-slot>
                {{ __('servers.servers') }}
            </x-sidebar-link>
        @endif

        <x-sidebar-link href="{{ route('admin.accounts.index') }}" :active="request()->routeIs('admin.accounts.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </x-slot>
            {{ __('accounts.accounts') }}
        </x-sidebar-link>

        @if($user->isAdmin())
            <x-sidebar-link href="{{ route('admin.resellers.index') }}" :active="request()->routeIs('admin.resellers.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </x-slot>
                {{ __('common.resellers') }}
            </x-sidebar-link>
        @endif

        <div class="pt-4 pb-2 px-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('common.configuration') }}</span>
        </div>

        @if($user->isAdmin())
            <x-sidebar-link href="{{ route('admin.services.index') }}" :active="request()->routeIs('admin.services.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                </x-slot>
                {{ __('common.services') }}
            </x-sidebar-link>
        @endif

        @if($user->isAdmin() || $user->resellerCan('packages.manage'))
            <x-sidebar-link href="{{ route('admin.packages.index') }}" :active="request()->routeIs('admin.packages.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                </x-slot>
                {{ __('packages.packages') }}
            </x-sidebar-link>
        @endif

        @if($user->isAdmin())
            <x-sidebar-link href="{{ route('admin.dns-templates.index') }}" :active="request()->routeIs('admin.dns-templates.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z" /></svg>
                </x-slot>
                {{ __('dns.dns_templates') }}
            </x-sidebar-link>

            <x-sidebar-link href="{{ route('admin.ip-addresses.index') }}" :active="request()->routeIs('admin.ip-addresses.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9c0 1.657-4.03 3-9 3s-9-1.343-9-3m18 0c0-1.657-4.03-3-9-3s-9 1.343-9 3m9 9a9 9 0 01-9-9m9 9c-1.657 0-3-4.03-3-9s1.343-9 3-9" /></svg>
                </x-slot>
                {{ __('ip_addresses.ip_addresses') }}
            </x-sidebar-link>

            <x-sidebar-link href="{{ route('admin.php.index') }}" :active="request()->routeIs('admin.php.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </x-slot>
                {{ __('php.php_versions') }}
            </x-sidebar-link>

            <x-sidebar-link href="{{ route('admin.spam-filter.index') }}" :active="request()->routeIs('admin.spam-filter.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" /></svg>
                </x-slot>
                {{ __('common.spam_filter') }}
            </x-sidebar-link>

            <x-sidebar-link href="{{ route('admin.ssl-overview.index') }}" :active="request()->routeIs('admin.ssl-overview.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                </x-slot>
                SSL Overview
            </x-sidebar-link>

            <x-sidebar-link href="{{ route('admin.email-settings.index') }}" :active="request()->routeIs('admin.email-settings.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                </x-slot>
                {{ __('emails.email_settings') }}
            </x-sidebar-link>

            <x-sidebar-link href="{{ route('admin.system-settings.index') }}" :active="request()->routeIs('admin.system-settings.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </x-slot>
                {{ __('system-settings.page_title') }}
            </x-sidebar-link>
        @endif

        <div class="pt-4 pb-2 px-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('common.settings') }}</span>
        </div>

        @if($user->isAdmin() || $user->resellerCan('migration.import'))
            <x-sidebar-link href="{{ route('admin.migrations.index') }}" :active="request()->routeIs('admin.migrations.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                </x-slot>
                {{ __('migrations.cpanel_migrations') }}
            </x-sidebar-link>
        @endif

        @if($user->isAdmin() || $user->resellerCan('api_keys.manage'))
            <x-sidebar-link href="{{ route('admin.api-keys.index') }}" :active="request()->routeIs('admin.api-keys.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                </x-slot>
                {{ __('api.api_keys') }}
            </x-sidebar-link>
        @endif

        @if($user->isAdmin())
            <x-sidebar-link href="{{ route('admin.license.index') }}" :active="request()->routeIs('admin.license.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                </x-slot>
                {{ __('common.license') }}
            </x-sidebar-link>

            <x-sidebar-link href="{{ route('admin.updates.index') }}" :active="request()->routeIs('admin.updates.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                </x-slot>
                {{ __('common.updates') }}
            </x-sidebar-link>

            <x-sidebar-link href="{{ route('admin.panel-hostname.index') }}" :active="request()->routeIs('admin.panel-hostname.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </x-slot>
                {{ __('panel_hostname.nav_label') }}
            </x-sidebar-link>
        @endif
    </nav>

    <div class="px-4 py-3 border-t border-gray-800 text-xs text-gray-500">
        <a href="{{ route('admin.updates.index') }}" class="hover:text-gray-300 transition">Opterius Panel v{{ config('opterius.version', '1.0.0') }}</a>
    </div>
</aside>
