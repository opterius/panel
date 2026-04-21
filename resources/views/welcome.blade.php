<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Opterius Panel</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-gray-900 text-white min-h-screen flex flex-col">

    <!-- Nav -->
    <nav class="flex items-center justify-between px-8 py-5">
        <div class="flex items-center space-x-3">
            <img src="{{ asset('img/logo.png') }}" alt="Opterius" class="w-10 h-10 rounded-xl object-contain">
            <span class="text-xl font-bold">Opterius</span>
        </div>
        <div class="flex items-center space-x-4">
            @auth
                <a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-300 hover:text-white transition">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="text-sm font-medium text-gray-300 hover:text-white transition">Log in</a>
            @endauth
        </div>
    </nav>

    <!-- Hero -->
    <main class="flex-1 flex items-center justify-center px-8">
        <div class="text-center max-w-2xl">
            <div class="inline-flex items-center px-3 py-1 rounded-full bg-orange-500/20 border border-orange-500/30 text-orange-400 text-xs font-medium mb-6">
                v{{ config('opterius.version', '1.0.0') }}
            </div>

            <h1 class="text-5xl sm:text-6xl font-bold leading-tight">
                <span class="text-white">Opterius</span>
                <span style="color:#ff6900;">Panel</span>
            </h1>

            <p class="mt-6 text-lg text-gray-400 leading-relaxed">
                Modern hosting panel for your server.<br>
                Manage domains, email, databases, SSL, and more.
            </p>

            <div class="mt-10 flex items-center justify-center space-x-4">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center px-8 py-3.5 text-white text-sm font-semibold rounded-xl transition shadow-lg"
                       style="background-color:#ff6900; box-shadow: 0 10px 15px -3px rgba(255, 105, 0, 0.25);"
                       onmouseover="this.style.backgroundColor='#e65d00'"
                       onmouseout="this.style.backgroundColor='#ff6900'">
                        Go to Dashboard
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center px-8 py-3.5 text-white text-sm font-semibold rounded-xl transition shadow-lg"
                       style="background-color:#ff6900; box-shadow: 0 10px 15px -3px rgba(255, 105, 0, 0.25);"
                       onmouseover="this.style.backgroundColor='#e65d00'"
                       onmouseout="this.style.backgroundColor='#ff6900'">
                        Log in to Panel
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </a>
                @endauth
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="px-8 py-6 text-center">
        <p class="text-sm text-gray-600">&copy; {{ date('Y') }} Opterius. All rights reserved.</p>
    </footer>

</body>
</html>
