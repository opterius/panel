<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Opterius Panel') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles

        {{-- Dark-themed overrides for the Jetstream auth form components
             (x-label / x-input / x-button) used inside the authentication card.
             The base components stay light so profile pages aren't affected. --}}
        <style>
            .auth-dark label { color: #cbd5e1 !important; }
            .auth-dark input[type="text"],
            .auth-dark input[type="email"],
            .auth-dark input[type="password"],
            .auth-dark input[type="number"],
            .auth-dark textarea,
            .auth-dark select {
                background-color: #0f172a !important;
                border-color: #334155 !important;
                color: #f1f5f9 !important;
            }
            .auth-dark input::placeholder { color: #64748b !important; }
            .auth-dark input:focus, .auth-dark textarea:focus, .auth-dark select:focus {
                border-color: #ff6900 !important;
                box-shadow: 0 0 0 2px rgba(255, 105, 0, 0.25) !important;
            }
            .auth-dark input[type="checkbox"] {
                background-color: #0f172a !important;
                border-color: #475569 !important;
            }
            .auth-dark input[type="checkbox"]:checked {
                background-color: #ff6900 !important;
                border-color: #ff6900 !important;
            }
            .auth-dark a { color: #fb923c; }
            .auth-dark a:hover { color: #f97316; }
            .auth-dark button[type="submit"] {
                background-color: #ff6900 !important;
                color: #fff !important;
                border-radius: 0.5rem !important;
                padding: 0.55rem 1rem !important;
                letter-spacing: normal !important;
                text-transform: none !important;
                font-size: 0.875rem !important;
            }
            .auth-dark button[type="submit"]:hover { background-color: #e65d00 !important; }
            .auth-dark .text-gray-600 { color: #cbd5e1 !important; }
            .auth-dark .text-gray-700 { color: #e2e8f0 !important; }
        </style>
    </head>
    <body>
        <div class="font-sans antialiased auth-dark">
            {{ $slot }}
        </div>

        @livewireScripts
    </body>
</html>
