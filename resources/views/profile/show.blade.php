<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('profile.profile') }}
        </h2>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                @livewire('profile.update-profile-information-form')

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                <div class="mt-10 sm:mt-0">
                    @livewire('profile.update-password-form')
                </div>

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <div class="mt-10 sm:mt-0">
                    @livewire('profile.two-factor-authentication-form')
                </div>

                <x-section-border />
            @endif

            <div class="mt-10 sm:mt-0">
                @livewire('profile.logout-other-browser-sessions-form')
            </div>

            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                <x-section-border />

                <div class="mt-10 sm:mt-0">
                    @livewire('profile.delete-user-form')
                </div>
            @endif

            <x-section-border />

            <!-- Language Preference -->
            <div class="mt-10 sm:mt-0">
                <x-form-section submit="">
                    <x-slot name="title">{{ __('profile.language') }}</x-slot>
                    <x-slot name="description">{{ __('profile.select_language') }}</x-slot>

                    <x-slot name="form">
                        @if(session('status') === 'locale-updated')
                            <div class="col-span-6 text-sm text-green-600">{{ __('profile.language_updated') }}</div>
                        @endif

                        <div class="col-span-6 sm:col-span-4">
                            <x-label for="locale" value="{{ __('profile.language') }}" />
                            <form method="POST" action="{{ route('user.locale') }}" id="locale-form">
                                @csrf
                                @method('PATCH')
                                <select id="locale" name="locale" onchange="document.getElementById('locale-form').submit()"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                    @foreach(config('app.available_locales', []) as $code => $label)
                                        <option value="{{ $code }}" {{ auth()->user()->locale === $code ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </x-slot>

                    <x-slot name="actions"></x-slot>
                </x-form-section>
            </div>
        </div>
    </div>
</x-app-layout>
