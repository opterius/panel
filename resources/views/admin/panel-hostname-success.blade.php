<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('panel_hostname.success_title') }}</title>
    <meta http-equiv="refresh" content="6;url={{ $newUrl }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full overflow-hidden">
        <div class="px-8 py-10 text-center">
            <div class="mx-auto w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-5">
                <svg class="w-9 h-9 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ __('panel_hostname.success_title') }}</h1>
            <p class="text-sm text-gray-600 mb-6">
                {{ __('panel_hostname.success_intro', ['hostname' => $hostname]) }}
            </p>

            <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 mb-6 text-left text-sm">
                <div class="text-xs text-gray-500 mb-1">{{ __('panel_hostname.new_url_label') }}</div>
                <div class="font-mono text-indigo-600 break-all">{{ $newUrl }}</div>
            </div>

            <a href="{{ $newUrl }}"
               class="inline-flex items-center justify-center w-full px-5 py-3 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                {{ __('panel_hostname.open_new_url') }}
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>

            <p class="text-xs text-gray-400 mt-4">{{ __('panel_hostname.success_autoredirect') }}</p>
        </div>

        <div class="bg-amber-50 border-t border-amber-200 px-8 py-4 text-xs text-amber-800">
            <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p>{{ __('panel_hostname.success_old_url_note') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
