<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">{{ __('panel_hostname.title') }}</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {!! session('success') !!}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">{{ __('panel_hostname.current_label') }}</h3>
        </div>
        <div class="px-6 py-5 space-y-2 text-sm">
            <div>
                <span class="text-gray-500">{{ __('panel_hostname.current_url') }}:</span>
                <span class="font-mono text-gray-900 ml-2">{{ $currentUrl }}</span>
            </div>
            @if($isIpBased)
                <div class="mt-3 inline-flex items-center px-3 py-1.5 rounded-full bg-amber-50 text-amber-700 text-xs font-medium">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                    {{ __('panel_hostname.using_ip_warning') }}
                </div>
            @else
                <div class="mt-3 inline-flex items-center px-3 py-1.5 rounded-full bg-green-50 text-green-700 text-xs font-medium">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    {{ __('panel_hostname.using_hostname') }}
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">{{ __('panel_hostname.set_new_label') }}</h3>
            <p class="text-sm text-gray-500 mt-1">{{ __('panel_hostname.set_new_hint') }}</p>
        </div>

        <form action="{{ route('admin.panel-hostname.update') }}" method="POST" class="px-6 py-5 space-y-5"
              x-data="{ submitting: false }"
              @submit="submitting = true">
            @csrf

            <div>
                <label for="hostname" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('panel_hostname.hostname_label') }}</label>
                <input type="text" name="hostname" id="hostname"
                    value="{{ old('hostname') }}"
                    placeholder="panel.example.com"
                    required
                    autocomplete="off"
                    class="w-full rounded-lg border-gray-300 shadow-sm font-mono text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <p class="mt-1.5 text-xs text-gray-500">{{ __('panel_hostname.hostname_hint') }}</p>
                @error('hostname')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('panel_hostname.email_label') }}</label>
                <input type="email" name="email" id="email"
                    value="{{ old('email', auth()->user()->email) }}"
                    placeholder="you@example.com"
                    required
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <p class="mt-1.5 text-xs text-gray-500">{{ __('panel_hostname.email_hint') }}</p>
                @error('email')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="rounded-lg bg-gray-50 border border-gray-200 px-4 py-3 text-xs text-gray-600 space-y-1">
                <p class="font-medium text-gray-800">{{ __('panel_hostname.checklist_title') }}</p>
                <ul class="list-disc list-inside space-y-0.5 ml-1">
                    <li>{{ __('panel_hostname.checklist_dns') }}</li>
                    <li>{{ __('panel_hostname.checklist_port80') }}</li>
                    <li>{{ __('panel_hostname.checklist_redirect') }}</li>
                </ul>
            </div>

            <div class="flex items-center justify-end pt-2">
                <button type="submit"
                    :disabled="submitting"
                    class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <svg x-show="!submitting" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    <svg x-show="submitting" x-cloak class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                    <span x-text="submitting ? '{{ __('panel_hostname.applying') }}' : '{{ __('panel_hostname.apply') }}'"></span>
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
