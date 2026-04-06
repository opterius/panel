<aside class="w-64 bg-gray-900 text-gray-300 flex flex-col shrink-0">
    <!-- Logo -->
    <div class="h-16 flex items-center px-6 border-b border-gray-800">
        <a href="{{ route('user.dashboard') }}" class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-sm">O</span>
            </div>
            <span class="text-white font-semibold text-lg">Opterius</span>
        </a>
    </div>

    <!-- Switcher (only for admin/reseller users) -->
    @if(Auth::user()->isAdmin() || Auth::user()->isReseller())
        <div class="px-3 py-3 border-b border-gray-800">
            <div class="flex items-center rounded-lg bg-gray-800 p-1">
                <a href="{{ route('admin.dashboard') }}" class="flex-1 text-center py-1.5 text-xs font-medium text-gray-400 hover:text-white transition rounded-md">
                    Server Mode
                </a>
                <span class="flex-1 text-center py-1.5 text-xs font-semibold rounded-md bg-indigo-600 text-white">
                    Hosting Mode
                </span>
            </div>
        </div>
    @endif

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <x-sidebar-link href="{{ route('user.dashboard') }}" :active="request()->routeIs('user.dashboard')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z" /></svg>
            </x-slot>
            Dashboard
        </x-sidebar-link>

        <div class="pt-4 pb-2 px-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Hosting</span>
        </div>

        <x-sidebar-link href="{{ route('user.domains.index') }}" :active="request()->routeIs('user.domains.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
            </x-slot>
            Domains
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.wordpress.index') }}" :active="request()->routeIs('user.wordpress.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"/></svg>
            </x-slot>
            WordPress
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.laravel.index') }}" :active="request()->routeIs('user.laravel.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
            </x-slot>
            Laravel
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.databases.index') }}" :active="request()->routeIs('user.databases.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
            </x-slot>
            Databases
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.ssl.index') }}" :active="request()->routeIs('user.ssl.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
            </x-slot>
            SSL Certificates
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.emails.index') }}" :active="request()->routeIs('user.emails.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
            </x-slot>
            Email
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.forwarders.index') }}" :active="request()->routeIs('user.forwarders.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
            </x-slot>
            Forwarders
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.filemanager.index') }}" :active="request()->routeIs('user.filemanager.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
            </x-slot>
            File Manager
        </x-sidebar-link>

        <div class="pt-4 pb-2 px-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Tools</span>
        </div>

        <x-sidebar-link href="{{ route('user.ftp.index') }}" :active="request()->routeIs('user.ftp.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
            </x-slot>
            FTP Accounts
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.ssh.index') }}" :active="request()->routeIs('user.ssh.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </x-slot>
            SSH Access
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.cronjobs.index') }}" :active="request()->routeIs('user.cronjobs.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </x-slot>
            Cron Jobs
        </x-sidebar-link>
    </nav>

    <div class="px-4 py-3 border-t border-gray-800 text-xs text-gray-500">
        <div>Opterius Panel v{{ config('opterius.version', '1.0.0') }}</div>
    </div>
</aside>
