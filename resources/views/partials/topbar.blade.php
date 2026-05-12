@php
    // Account selector only shows in Hosting Mode (user.* routes). In Server
    // Mode (admin.*) it would be meaningless — admins manage the fleet, not
    // a single customer account.
    $tbInHostingMode = request()->routeIs('user.*');
    $tbCurrentAcct   = $tbInHostingMode ? Auth::user()->currentAccount() : null;
    $tbUserAccounts  = $tbInHostingMode
        ? Auth::user()->accessibleAccounts()->with('domains')->get()
            ->sortBy(fn($a) => strtolower($a->domains->whereNull('parent_id')->first()?->domain ?? $a->username))
            ->values()
        : collect();
@endphp
<header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 shrink-0">
    <!-- Page Title + Active Account -->
    <div class="flex items-center gap-4 min-w-0">
        @if (isset($header))
            <div>{{ $header }}</div>
        @endif

        @if($tbInHostingMode && $tbCurrentAcct)
            @if($tbUserAccounts->count() > 1)
                <div class="relative" x-data="{ open: false, search: '' }" @keydown.escape.window="open = false" @click.outside="open = false">
                    <button type="button" @click="open = !open; if (open) $nextTick(() => $refs.tbAcctSearch?.focus())"
                        class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg transition">
                        <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3"/></svg>
                        <span class="text-sm font-medium text-gray-800 truncate max-w-[180px]">{{ $tbCurrentAcct->domains->whereNull('parent_id')->first()?->domain ?? $tbCurrentAcct->username }}</span>
                        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-transition class="absolute left-0 top-full mt-1 w-72 bg-white border border-gray-200 rounded-lg shadow-lg z-50 p-2" style="display:none;">
                        <div class="relative mb-2">
                            <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="search" x-ref="tbAcctSearch" x-model="search"
                                placeholder="Search account…"
                                autocomplete="new-password"
                                autocorrect="off" autocapitalize="off" spellcheck="false"
                                data-lpignore="true" data-1p-ignore data-form-type="other" data-bwignore
                                name="tb-acct-filter-{{ uniqid() }}"
                                class="w-full bg-gray-50 border border-gray-200 rounded-md pl-8 pr-2 py-1.5 text-sm text-gray-800 placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div class="space-y-1 overflow-y-auto" style="max-height: 320px; scrollbar-width: thin;">
                            @foreach($tbUserAccounts as $acct)
                                @php
                                    $tbAcctDomain = $acct->domains->whereNull('parent_id')->first()?->domain ?? $acct->username;
                                    $tbHaystack  = strtolower($tbAcctDomain . ' ' . $acct->username);
                                @endphp
                                <form method="POST" action="{{ route('user.switch-account') }}"
                                      x-show="search === '' || @js($tbHaystack).includes(search.toLowerCase().trim())">
                                    @csrf
                                    <input type="hidden" name="account_id" value="{{ $acct->id }}">
                                    <button type="submit" class="w-full text-left px-3 py-2 rounded-md text-sm transition
                                        {{ $tbCurrentAcct->id === $acct->id ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                                        <div class="font-medium truncate">{{ $tbAcctDomain }}</div>
                                        <div class="text-sm opacity-75 truncate">{{ $acct->username }}</div>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3"/></svg>
                    <span class="text-sm font-medium text-gray-800">{{ $tbCurrentAcct->domains->whereNull('parent_id')->first()?->domain ?? $tbCurrentAcct->username }}</span>
                </div>
            @endif
        @endif
    </div>

    <!-- Right Side -->
    <div class="flex items-center space-x-4">
        @if(count(config('app.available_locales', [])) > 1)
        <!-- Language Switcher -->
        <x-dropdown align="right" width="36">
            <x-slot name="trigger">
                <button class="flex items-center gap-1.5 text-xs font-medium text-gray-500 hover:text-gray-800 transition px-2 py-1 rounded-lg hover:bg-gray-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" /></svg>
                    <span class="uppercase">{{ app()->getLocale() }}</span>
                </button>
            </x-slot>
            <x-slot name="content">
                @foreach(config('app.available_locales', []) as $code => $label)
                    <form method="POST" action="{{ route('user.locale') }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="locale" value="{{ $code }}">
                        <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition {{ app()->getLocale() === $code ? 'font-semibold text-indigo-600' : '' }}">
                            {{ $label }}
                        </button>
                    </form>
                @endforeach
            </x-slot>
        </x-dropdown>
        @endif

        {{-- License status badge — visible to admins --}}
        @if(Auth::user()->isAdmin())
            @php
                $licenseKey = config('opterius.license_key') ?: env('OPTERIUS_LICENSE_KEY', '');
                $licenseStatus = cache('license_status');
                $isValid = ! empty($licenseKey) && ($licenseStatus['valid'] ?? false);
                $planName = is_array($licenseStatus['plan'] ?? null)
                    ? ($licenseStatus['plan']['name'] ?? 'Free')
                    : ($licenseStatus['plan'] ?? 'Free');
            @endphp
            <a href="{{ route('admin.license.index') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg transition
                      {{ $isValid
                          ? 'text-green-700 bg-green-50 hover:bg-green-100 border border-green-200'
                          : 'text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200' }}">
                @if($isValid)
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                    {{ ucfirst($planName) }}
                @elseif(empty($licenseKey))
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    No License
                @else
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    Invalid License
                @endif
            </a>
        @endif

        @if(session('admin_id'))
            <form method="POST" action="{{ route('user.return-to-admin') }}">
                @csrf
                <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-amber-700 bg-amber-100 border border-amber-300 rounded-lg hover:bg-amber-200 transition">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" /></svg>
                    {{ __('common.return_to_admin') }}
                </button>
            </form>
        @endif
        <!-- User Dropdown -->
        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button class="flex items-center text-sm font-medium text-gray-600 hover:text-gray-900 transition">
                    @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                        <img class="w-8 h-8 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                    @else
                        <span>{{ Auth::user()->name }}</span>
                    @endif
                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="block px-4 py-2 text-xs text-gray-400">
                    {{ __('Manage Account') }}
                </div>

                <x-dropdown-link href="{{ route('profile.show') }}">
                    {{ __('Profile') }}
                </x-dropdown-link>

                <a href="https://opterius.com" target="_blank" rel="noopener"
                   class="block w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition">
                    Opterius website
                    <svg class="inline-block w-3 h-3 ml-1 -mt-0.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>

                <div class="border-t border-gray-200"></div>

                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf
                    <x-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                        {{ __('Log Out') }}
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</header>
