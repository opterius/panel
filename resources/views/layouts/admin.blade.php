<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Opterius') }} — {{ __('common.admin') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <x-banner />

        <div class="min-h-screen flex bg-gray-50">
            @include('partials.admin-sidebar')

            <div class="flex-1 flex flex-col min-w-0">
                @include('partials.topbar')

                <main class="flex-1 p-6">
                    {{-- Billing status banner: shown above every admin page when
                         the owner's subscription needs attention. Cached via
                         LicenseService (24h) so this is not a hot path. --}}
                    @php
                        try {
                            $licenseSvc = app(\App\Services\LicenseService::class);
                            $subStatus  = $licenseSvc->subscriptionStatus();
                            $cancelEnd  = $licenseSvc->cancelAtPeriodEnd();
                            $periodEnd  = $licenseSvc->currentPeriodEnd();
                            $endHuman   = $periodEnd ? \Carbon\Carbon::parse($periodEnd)->format('M j, Y') : null;
                        } catch (\Throwable) {
                            $subStatus = $cancelEnd = $endHuman = null;
                        }
                    @endphp

                    @if ($subStatus === 'past_due')
                        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 flex items-start gap-3">
                            <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div class="flex-1">
                                <strong class="font-semibold text-red-900">Payment failed</strong>
                                <p class="text-sm text-red-800 mt-0.5">Your last invoice could not be charged. Stripe will retry automatically, but we recommend updating your payment method now to avoid service interruption.</p>
                            </div>
                            <a href="https://opterius.com/dashboard/billing" target="_blank" rel="noopener"
                               class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition">
                                Update payment
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>
                    @elseif ($cancelEnd && $endHuman)
                        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="flex-1">
                                <strong class="font-semibold text-amber-900">Subscription ending {{ $endHuman }}</strong>
                                <p class="text-sm text-amber-800 mt-0.5">Your subscription was cancelled. You'll keep full access until then, then drop to the Free plan. Your data and existing accounts are never deleted.</p>
                            </div>
                            <a href="https://opterius.com/dashboard/billing" target="_blank" rel="noopener"
                               class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg transition">
                                Resume subscription
                            </a>
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('modals')
        @livewireScripts
    </body>
</html>
