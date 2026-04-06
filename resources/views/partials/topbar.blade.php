<header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 shrink-0">
    <!-- Page Title -->
    <div>
        @if (isset($header))
            {{ $header }}
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
