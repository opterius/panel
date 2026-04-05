<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">WordPress</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <p class="text-sm text-gray-500">Manage WordPress installations across your domains.</p>
        <a href="{{ route('user.wordpress.create') }}" class="inline-flex items-center px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
            Install WordPress
        </a>
    </div>

    <!-- WordPress Sites -->
    @if(empty($sites))
        <div class="bg-white rounded-xl shadow-sm px-6 py-16 text-center">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-blue-600" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"/><path d="M4.5 12L8.3 20.5L9.8 15.2L15.1 14.1L20.5 12L15.1 9.9L9.8 8.8L8.3 3.5z"/></svg>
            </div>
            <h3 class="text-base font-medium text-gray-700">No WordPress installations found</h3>
            <p class="mt-2 text-sm text-gray-500">Install WordPress on any of your domains with one click.</p>
            <a href="{{ route('user.wordpress.create') }}" class="mt-6 inline-flex items-center px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                Install WordPress
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($sites as $site)
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">{{ $site['site_title'] ?: $site['domain'] }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ $site['domain'] }}
                                    &middot; WordPress {{ $site['version'] }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            @if($site['update_available'] ?? false)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                    Update Available
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    Up to date
                                </span>
                            @endif

                            <a href="{{ $site['admin_url'] ?? '#' }}" target="_blank"
                               class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                WP Admin
                            </a>

                            @if($site['update_available'] ?? false)
                                @php
                                    $siteDomain = collect($domains)->firstWhere('domain', $site['domain']);
                                @endphp
                                @if($siteDomain)
                                    <form action="{{ route('user.wordpress.update') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="domain_id" value="{{ $siteDomain->id }}">
                                        <input type="hidden" name="path" value="{{ $site['path'] }}">
                                        <input type="hidden" name="type" value="all">
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition">
                                            Update All
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-user-layout>
