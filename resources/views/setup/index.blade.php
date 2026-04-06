<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Opterius Panel — Setup</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-lg" x-data="{
        password: '',
        get passwordStrength() {
            const p = this.password;
            if (!p) return { score: 0, label: '', color: '' };
            let score = 0;
            if (p.length >= 8) score++;
            if (p.length >= 12) score++;
            if (/[a-z]/.test(p) && /[A-Z]/.test(p)) score++;
            if (/[0-9]/.test(p)) score++;
            if (/[^a-zA-Z0-9]/.test(p)) score++;
            if (score <= 1) return { score: 1, label: 'Weak', color: 'bg-red-500' };
            if (score <= 2) return { score: 2, label: 'Fair', color: 'bg-orange-500' };
            if (score <= 3) return { score: 3, label: 'Good', color: 'bg-yellow-500' };
            if (score <= 4) return { score: 4, label: 'Strong', color: 'bg-green-500' };
            return { score: 5, label: 'Very Strong', color: 'bg-green-600' };
        }
    }">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center space-x-3">
                <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center">
                    <span class="text-white font-bold text-xl">O</span>
                </div>
                <span class="text-2xl font-bold text-gray-900">Opterius Panel</span>
            </div>
            <p class="mt-3 text-gray-500">Welcome! Let's set up your admin account.</p>
        </div>

        <!-- Setup Form -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="px-8 py-6 bg-indigo-600">
                <h2 class="text-lg font-semibold text-white">Create Admin Account</h2>
                <p class="text-indigo-200 text-sm mt-1">This will be the main administrator of your hosting panel.</p>
            </div>

            <form action="{{ route('setup.store') }}" method="POST" class="px-8 py-6 space-y-5">
                @csrf

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="John Doe">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="admin@yourdomain.com">
                    <p class="mt-1 text-xs text-gray-400">This will be your login username.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                    <input type="password" name="password" x-model="password" required
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Min 8 characters">
                    <div class="mt-2 flex items-center space-x-3">
                        <div class="flex space-x-1 flex-1">
                            <template x-for="i in 5">
                                <div class="h-1.5 flex-1 rounded-full transition-all duration-300"
                                    :class="passwordStrength.score >= i ? passwordStrength.color : 'bg-gray-200'"></div>
                            </template>
                        </div>
                        <span class="text-xs font-medium"
                            :class="{
                                'text-red-500': passwordStrength.score === 1,
                                'text-orange-500': passwordStrength.score === 2,
                                'text-yellow-600': passwordStrength.score === 3,
                                'text-green-500': passwordStrength.score >= 4,
                                'text-gray-400': !passwordStrength.score
                            }"
                            x-text="passwordStrength.label || ''"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Repeat password">
                </div>

                <button type="submit"
                    :disabled="passwordStrength.score < 3"
                    class="w-full inline-flex items-center justify-center px-6 py-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    Complete Setup
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">
            Opterius Panel v{{ config('opterius.version', '1.0.0') }}
        </p>
    </div>

</body>
</html>
