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

        <x-sidebar-link href="{{ route('user.subdomains.index') }}" :active="request()->routeIs('user.subdomains.*')">
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
            MySQL
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.postgres.index') }}" :active="request()->routeIs('user.postgres.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M17.128 0a10.134 10.134 0 00-2.755.38 7.124 7.124 0 00-2.016-.291c-1.176.012-2.306.356-3.231.967a9.933 9.933 0 00-1.685-.218l-.032.005c-1.347.013-4.423 1.396-4.423 6.847a12.418 12.418 0 001.161 5.19l.054.12a16.46 16.46 0 00-.55 4.122c.001 2.826.752 4.21 1.684 4.51.387.124.773.186 1.158.186 1.387 0 2.629-.832 3.388-1.678.44.047.896.07 1.362.07 2.32 0 4.49-.647 6.037-1.874a6.5 6.5 0 002.127-2.706 7.3 7.3 0 001.54-2.16c.744-1.668.744-3.622.744-4.514C21.494 2.695 19.451 0 17.128 0zm-3.84 21.522c-.633.827-1.552 1.498-2.68 1.498-.284 0-.567-.042-.848-.132-.613-.197-1.107-1.264-1.107-3.564 0-1.354.19-2.745.517-4.004a9.39 9.39 0 001.37.697c.738.305 1.54.504 2.38.586-.195.917-.42 1.808-.68 2.651a9.94 9.94 0 01-1.075 2.268zm-.993-5.33c-.876-.09-1.704-.299-2.457-.614a8.49 8.49 0 01-1.327-.703c.07-.176.145-.352.225-.527.375-.819.81-1.596 1.29-2.33a18.9 18.9 0 001.677.079c.558 0 1.1-.022 1.624-.063.07.945.085 1.905.045 2.865-.353.091-.707.18-1.077.293zm.53-4.51a19.3 19.3 0 01-1.584.063 18.93 18.93 0 01-1.684-.08 12.47 12.47 0 011.13-1.313c.503-.52 1.03-.998 1.583-1.425.517.438 1.013.908 1.486 1.41.22.237.43.48.63.724-.5.045-1.02.068-1.561.12zm1.561.021zm-5.81-1.31a14.16 14.16 0 01-1.257-5.02c0-4.647 2.5-5.866 3.535-5.866l.004-.001c.388.012.775.07 1.148.157a10.59 10.59 0 00-.754 2.193c-.3 1.458-.378 2.972-.3 4.535-.47.388-.918.81-1.342 1.264-.362.39-.701.8-1.034 1.22v.518zm2.65-10.58c.553-.346 1.187-.538 1.844-.553.654 0 1.297.17 1.86.518-.562.131-1.11.312-1.636.535a13.73 13.73 0 00-1.453.788 8.07 8.07 0 00-.615-1.288zm-.45 11.218c.044-.963.028-1.928-.043-2.876a11.35 11.35 0 001.585-.127c.467.676.893 1.382 1.27 2.116.23.454.43.917.605 1.386a12.05 12.05 0 01-3.417-.499zm5.47.47a7.82 7.82 0 01-.427-1.035 14.07 14.07 0 00-1.235-2.083 9.76 9.76 0 001.083-.355c.627-.254 1.21-.578 1.735-.965-.103 1.634-.491 3.065-1.156 4.437zm1.487-5.88a8.52 8.52 0 01-1.812.978 10.82 10.82 0 00-.543-1.097 14.24 14.24 0 00-1.268-1.97 8.85 8.85 0 001.588-.552c.51-.236.983-.528 1.412-.877.178.497.277 1.02.277 1.582 0 .652-.218 1.305-.655 1.936zm-6.638-8.25a13.65 13.65 0 001.585-.813c.525-.33 1.087-.624 1.683-.87a10.43 10.43 0 012.36-.625c1.554 0 3.037 1.986 3.406 4.796a7.9 7.9 0 01-1.27.827 9.27 9.27 0 01-1.556.573 6.93 6.93 0 00-1.407-1.977 10.58 10.58 0 00-1.695-1.41 7.2 7.2 0 00-3.106-.5z"/></svg>
            </x-slot>
            PostgreSQL
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

        <x-sidebar-link href="{{ route('user.cms.index', 'joomla') }}" :active="request()->is('*/cms/joomla*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M13.196 1.608a5.344 5.344 0 00-3.776.02 3.233 3.233 0 01.496 1.742 3.255 3.255 0 01-3.255 3.254 3.233 3.233 0 01-1.741-.496 5.363 5.363 0 000 7.564l3.96-3.961a1.785 1.785 0 010-2.524 1.781 1.781 0 012.52 0l2.797 2.797 2.796-2.797a1.781 1.781 0 012.52 0 1.785 1.785 0 010 2.524l-3.96 3.96a5.363 5.363 0 007.564 0 5.363 5.363 0 000-7.564l-3.96 3.961a1.785 1.785 0 010-2.524l-2.797-2.797zm-9.784 9.784l-1.804 1.804A5.363 5.363 0 003.172 20.7a5.344 5.344 0 003.776-.021 3.233 3.233 0 01-.496-1.742 3.255 3.255 0 013.255-3.254 3.233 3.233 0 011.741.496 5.363 5.363 0 000-7.564l-3.96 3.96a1.785 1.785 0 010 2.524 1.781 1.781 0 01-2.52 0z"/></svg>
            </x-slot>
            {{ __('common.joomla') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.cms.index', 'drupal') }}" :active="request()->is('*/cms/drupal*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M15.78 5.113C14.09 3.425 12.48 1.815 11.998 0c-.48 1.815-2.09 3.424-3.778 5.113-2.534 2.53-5.463 5.46-5.463 9.496a9.241 9.241 0 009.241 9.241 9.241 9.241 0 009.241-9.241c0-4.037-2.928-6.966-5.459-9.496zM7.16 19.145a5.29 5.29 0 01-1.57-3.68 5.29 5.29 0 013.245-4.862c1.55-.665 2.61-.788 3.628-2.36.41 1.5.16 2.464-.47 3.672-.895 1.713-.856 2.886-.316 4.07a4.23 4.23 0 01-1.04.132 4.26 4.26 0 01-3.477-1.772zm5.71 2.002c-.87 0-1.636-.404-2.133-1.035-.437-.553-.498-1.06-.374-1.57.22-.885.877-1.55 1.26-2.47.39.92 1.038 1.585 1.263 2.47.123.51.06 1.017-.374 1.57a2.624 2.624 0 01-1.642 1.035zm3.83-2.002a4.26 4.26 0 01-3.476 1.772 4.23 4.23 0 01-1.041-.132c.54-1.184.58-2.357-.316-4.07-.63-1.208-.88-2.173-.47-3.672 1.017 1.572 2.078 1.695 3.628 2.36a5.29 5.29 0 013.245 4.862 5.29 5.29 0 01-1.57 3.68z"/></svg>
            </x-slot>
            {{ __('common.drupal') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.cms.index', 'magento') }}" :active="request()->is('*/cms/magento*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12.002 0L1.636 6v12l2.545 1.47v-12l7.82-4.515 7.82 4.516v12L22.366 18V6zm1.272 20.06l-1.272.735-1.271-.734V8.029L8.185 9.498v12L12 23.53l3.814-2.03v-12l-2.544-1.47v12.03z"/></svg>
            </x-slot>
            {{ __('common.magento') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.cms.index', 'prestashop') }}" :active="request()->is('*/cms/prestashop*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.383 0 0 5.383 0 12s5.383 12 12 12 12-5.383 12-12S18.617 0 12 0zm-.005 4.43c2.573 0 4.66 2.087 4.66 4.661 0 2.573-2.087 4.66-4.66 4.66-2.574 0-4.661-2.087-4.661-4.66s2.087-4.66 4.66-4.66zm5.527 14.69H6.478l1.808-4.69h7.427l1.809 4.69z"/></svg>
            </x-slot>
            {{ __('common.prestashop') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.nodejs.index') }}" :active="request()->routeIs('user.nodejs.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1.85c-.27 0-.55.07-.78.2L3.78 6.35C3.3 6.6 3 7.1 3 7.65v8.69c0 .56.3 1.06.78 1.31l7.44 4.3c.23.13.5.2.78.2s.55-.07.78-.2l7.44-4.3c.48-.25.78-.75.78-1.31V7.65c0-.55-.3-1.05-.78-1.3l-7.44-4.3c-.23-.13-.5-.2-.78-.2zm0 2.06l6.66 3.85v7.68L12 19.29l-6.66-3.85V7.76L12 3.91z"/></svg>
            </x-slot>
            Node.js
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.composer.index') }}" :active="request()->routeIs('user.composer.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </x-slot>
            Composer
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('user.git.index') }}" :active="request()->routeIs('user.git.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
            </x-slot>
            Git
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
