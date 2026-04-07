<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">{{ __('system-settings.page_title') }}</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <p class="text-sm text-gray-500 mb-6">{{ __('system-settings.page_subtitle') }}</p>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        {{-- ── Sidebar: categories ──────────────────────────────────────── --}}
        <aside class="lg:col-span-1">
            <nav class="bg-white rounded-xl shadow-sm overflow-hidden">
                @foreach($categories as $slug => $cat)
                    <a href="{{ route('admin.system-settings.index', ['category' => $slug]) }}"
                       class="flex items-center gap-3 px-4 py-3 border-b border-gray-50 last:border-b-0 transition
                              {{ $category === $slug ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $cat['icon'] }}"/>
                        </svg>
                        <span class="text-sm">{{ __('system-settings.cat_' . $slug) }}</span>
                        @if(!$cat['ready'])
                            <span class="ml-auto text-[10px] uppercase tracking-wider text-amber-600 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded">Soon</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </aside>

        {{-- ── Right panel: category content ────────────────────────────── --}}
        <main class="lg:col-span-3">
            <div class="bg-white rounded-xl shadow-sm p-6 sm:p-8">

                @if(!$categories[$category]['ready'])
                    {{-- Coming soon placeholder for unimplemented categories --}}
                    <div class="text-center py-16">
                        <svg class="mx-auto w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="text-base font-medium text-gray-700 mb-1">{{ __('system-settings.coming_soon') }}</h3>
                        <p class="text-sm text-gray-500 max-w-md mx-auto">{{ __('system-settings.coming_soon_text') }}</p>
                    </div>

                @elseif($category === 'domains')
                    {{-- ── Domains category ─────────────────────────────── --}}
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ __('system-settings.domains_title') }}</h3>
                    <p class="text-sm text-gray-500 mb-6">{{ __('system-settings.domains_subtitle') }}</p>

                    <form action="{{ route('admin.system-settings.update', ['category' => 'domains']) }}" method="POST">
                        @csrf

                        {{-- Setting #1: default_php_version --}}
                        <div class="mb-6">
                            <label for="default_php_version" class="block text-sm font-medium text-gray-700 mb-1.5">
                                {{ __('system-settings.default_php_version_label') }}
                            </label>
                            <p class="text-xs text-gray-500 mb-2">{{ __('system-settings.default_php_version_hint') }}</p>
                            <select name="default_php_version" id="default_php_version"
                                    class="w-full max-w-xs rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($extra['php_versions'] as $version)
                                    <option value="{{ $version }}" @selected($extra['default_php_version'] === $version)>
                                        PHP {{ $version }}
                                    </option>
                                @endforeach
                            </select>
                            @error('default_php_version')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="pt-4 border-t border-gray-100">
                            <button type="submit"
                                    class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                                {{ __('system-settings.save') }}
                            </button>
                        </div>
                    </form>
                @endif

            </div>
        </main>
    </div>
</x-admin-layout>
