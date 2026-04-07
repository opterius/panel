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
                    {{ __('common.server_mode') }}
                </a>
                <span class="flex-1 text-center py-1.5 text-xs font-semibold rounded-md bg-indigo-600 text-white">
                    {{ __('common.hosting_mode') }}
                </span>
            </div>
        </div>
    @endif

    @php
        $userAccounts = Auth::user()->accessibleAccounts()->with('server', 'domains')->get();
        $currentAcct = Auth::user()->currentAccount();
    @endphp

    <!-- Account Switcher (only if user has multiple accounts) -->
    @if($userAccounts->count() > 1 && $currentAcct)
        <div class="px-3 py-3 border-b border-gray-800" x-data="{ open: false }">
            <button @click="open = !open" class="w-full bg-gray-800 hover:bg-gray-700 rounded-lg px-3 py-2 text-left transition">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2 min-w-0">
                        <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3" /></svg>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs text-gray-500 leading-tight">Active Account</div>
                            <div class="text-sm font-medium text-white truncate">{{ $currentAcct->domains->whereNull('parent_id')->first()?->domain ?? $currentAcct->username }}</div>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 transition-transform shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </div>
            </button>

            <div x-show="open" x-collapse class="mt-2 space-y-1">
                @foreach($userAccounts as $acct)
                    <form method="POST" action="{{ route('user.switch-account') }}">
                        @csrf
                        <input type="hidden" name="account_id" value="{{ $acct->id }}">
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-md text-sm transition
                            {{ $currentAcct->id === $acct->id ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                            <div class="font-medium truncate">{{ $acct->domains->whereNull('parent_id')->first()?->domain ?? $acct->username }}</div>
                            <div class="text-xs opacity-75 truncate">{{ $acct->username }}</div>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <x-sidebar-link href="{{ route('user.dashboard') }}" :active="request()->routeIs('user.dashboard')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z" /></svg>
            </x-slot>
            {{ __('dashboard.dashboard') }}
        </x-sidebar-link>

        {{-- Domains --}}
        <div class="pt-4 pb-2 px-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('domains.domain') }}</span>
        </div>

        <x-sidebar-link href="{{ route('user.domains.index') }}" :active="request()->routeIs('user.domains.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
            </x-slot>
            {{ __('domains.domains') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.domains.index') }}" :active="request()->routeIs('user.subdomains.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
            </x-slot>
            {{ __('domains.subdomains') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.aliases.index') }}" :active="request()->routeIs('user.aliases.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
            </x-slot>
            {{ __('domains.domain_aliases') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.redirects.index') }}" :active="request()->routeIs('user.redirects.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
            </x-slot>
            {{ __('redirects.redirects') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.ssl.index') }}" :active="request()->routeIs('user.ssl.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
            </x-slot>
            {{ __('ssl.ssl_certificates') }}
        </x-sidebar-link>

        {{-- Files --}}
        <div class="pt-4 pb-2 px-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('common.files') }}</span>
        </div>

        <x-sidebar-link href="{{ route('user.filemanager.index') }}" :active="request()->routeIs('user.filemanager.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
            </x-slot>
            {{ __('common.file_manager') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.ftp.index') }}" :active="request()->routeIs('user.ftp.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
            </x-slot>
            {{ __('ftp.ftp_accounts') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.ssh.index') }}" :active="request()->routeIs('user.ssh.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </x-slot>
            {{ __('accounts.ssh_access') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.terminal.index') }}" :active="request()->routeIs('user.terminal.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
            </x-slot>
            {{ __('common.web_terminal') }}
        </x-sidebar-link>

        {{-- Databases --}}
        <div class="pt-4 pb-2 px-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('databases.databases') }}</span>
        </div>

        <x-sidebar-link href="{{ route('user.databases.index') }}" :active="request()->routeIs('user.databases.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
            </x-slot>
            {{ __('databases.databases') }}
        </x-sidebar-link>

        {{-- Email --}}
        <div class="pt-4 pb-2 px-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('common.email') }}</span>
        </div>

        <x-sidebar-link href="{{ route('user.emails.index') }}" :active="request()->routeIs('user.emails.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
            </x-slot>
            {{ __('emails.email_accounts') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.forwarders.index') }}" :active="request()->routeIs('user.forwarders.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
            </x-slot>
            {{ __('common.forwarders') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.autoresponders.index') }}" :active="request()->routeIs('user.autoresponders.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
            </x-slot>
            {{ __('common.autoresponders') }}
        </x-sidebar-link>

        {{-- Software --}}
        <div class="pt-4 pb-2 px-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('common.software') }}</span>
        </div>

        <x-sidebar-link href="{{ route('user.wordpress.index') }}" :active="request()->routeIs('user.wordpress.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M21.469 6.825c.84 1.537 1.318 3.3 1.318 5.175 0 3.979-2.156 7.456-5.363 9.325l3.295-9.527c.615-1.539.82-2.771.82-3.864 0-.405-.027-.78-.07-1.109m-7.981.105c.647-.034 1.229-.1 1.229-.1.578-.068.51-.919-.068-.886 0 0-1.739.136-2.86.136-1.052 0-2.825-.136-2.825-.136-.579-.034-.646.852-.068.886 0 0 .549.066 1.13.1l1.68 4.605-2.37 7.08L5.554 6.93c.647-.034 1.229-.1 1.229-.1.578-.068.51-.919-.068-.886 0 0-1.739.136-2.86.136-.201 0-.438-.005-.689-.015C4.911 3.15 8.186 1.213 11.951 1.213c2.8 0 5.35 1.072 7.269 2.818-.046-.003-.091-.009-.141-.009-1.052 0-1.798.919-1.798 1.904 0 .886.51 1.636 1.054 2.522.408.715.886 1.636.886 2.964 0 .919-.354 1.985-.82 3.472l-1.075 3.586-3.894-11.575m-3.007 1.21l-3.357 9.755-2.96-8.115c-.133-.35-.257-.671-.38-.96A8.757 8.757 0 0 1 3.213 12c0-1.665.47-3.222 1.275-4.545m8.463 8.847l2.482-7.19 2.54 6.946c.017.04.036.078.054.114-1.589.666-3.32 1.038-5.138 1.038-.583 0-1.153-.044-1.712-.12" /></svg>
            </x-slot>
            {{ __('common.wordpress') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.laravel.index') }}" :active="request()->routeIs('user.laravel.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M23.642 5.43a.364.364 0 01.014.1v5.149a.361.361 0 01-.181.311l-4.32 2.494v4.934a.36.36 0 01-.181.311l-9.033 5.215a.367.367 0 01-.086.036.369.369 0 01-.274-.036L.548 18.73A.361.361 0 01.364 18.42V2.881a.361.361 0 01.014-.1.357.357 0 01.04-.09.36.36 0 01.056-.063l.01-.01a.36.36 0 01.077-.054L4.93.387a.361.361 0 01.361 0l4.369 2.523a.361.361 0 01.18.311v9.648l3.806-2.198V5.523a.358.358 0 01.015-.1.36.36 0 01.095-.153l.01-.01a.363.363 0 01.077-.054l4.369-2.523a.361.361 0 01.36 0l4.37 2.523a.36.36 0 01.077.054l.01.01a.36.36 0 01.055.063.361.361 0 01.04.09z"/></svg>
            </x-slot>
            {{ __('common.laravel') }}
        </x-sidebar-link>

        {{-- Advanced --}}
        <div class="pt-4 pb-2 px-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('common.advanced') }}</span>
        </div>

        <x-sidebar-link href="{{ route('user.nginx-directives.index') }}" :active="request()->routeIs('user.nginx-directives.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
            </x-slot>
            {{ __('nginx.nginx_directives') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.php.index') }}" :active="request()->routeIs('user.php.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </x-slot>
            {{ __('php.php_versions') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.cronjobs.index') }}" :active="request()->routeIs('user.cronjobs.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </x-slot>
            {{ __('cron.cron_jobs') }}
        </x-sidebar-link>
    </nav>

    <div class="px-4 py-3 border-t border-gray-800 text-xs text-gray-500">
        <div>Opterius Panel v{{ config('opterius.version', '1.0.0') }}</div>
    </div>
</aside>
