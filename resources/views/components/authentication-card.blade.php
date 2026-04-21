<div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-900">
    <div>
        {{ $logo }}
    </div>

    <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-gray-800 border border-gray-700 shadow-xl overflow-hidden sm:rounded-xl text-gray-200">
        {{ $slot }}
    </div>

    <p class="mt-6 text-xs text-gray-500">&copy; {{ date('Y') }} Opterius</p>
</div>
