<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Packages</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <p class="text-sm text-gray-500">Define resource limits and PHP versions for hosting accounts.</p>
        <a href="{{ route('packages.create') }}"
           class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            New Package
        </a>
    </div>

    @if($packages->isEmpty())
        <div class="bg-white rounded-xl shadow-sm p-16 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
            </svg>
            <h3 class="mt-4 text-base font-medium text-gray-700">No packages yet</h3>
            <p class="mt-2 text-sm text-gray-500">Create packages to quickly assign PHP version, disk quota, and limits when creating accounts.</p>
            <a href="{{ route('packages.create') }}"
               class="mt-6 inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Create First Package
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($packages as $package)
                <div class="bg-white rounded-xl shadow-sm overflow-hidden flex flex-col">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <h3 class="font-semibold text-gray-800">{{ $package->name }}</h3>
                            @if($package->is_default)
                                <span class="px-2 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-700 rounded-full">Default</span>
                            @endif
                        </div>
                        <span class="text-xs text-gray-400">{{ $package->accounts_count }} {{ Str::plural('account', $package->accounts_count) }}</span>
                    </div>

                    @if($package->description)
                        <div class="px-5 pt-3 text-sm text-gray-500">{{ $package->description }}</div>
                    @endif

                    <div class="px-5 py-4 grid grid-cols-2 gap-3 flex-1">
                        <div class="bg-gray-50 rounded-lg px-3 py-2.5">
                            <div class="text-xs text-gray-400 mb-0.5">PHP Versions</div>
                            <div class="text-sm font-semibold text-gray-700">
                                {{ implode(', ', $package->php_versions ?? []) }}
                                <span class="text-xs text-gray-400 font-normal">(default: {{ $package->default_php_version }})</span>
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg px-3 py-2.5">
                            <div class="text-xs text-gray-400 mb-0.5">Disk Quota</div>
                            <div class="text-sm font-semibold text-gray-700">{{ $package->diskQuotaLabel() }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg px-3 py-2.5">
                            <div class="text-xs text-gray-400 mb-0.5">Bandwidth</div>
                            <div class="text-sm font-semibold text-gray-700">{{ $package->bandwidthLabel() }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg px-3 py-2.5">
                            <div class="text-xs text-gray-400 mb-0.5">Subdomains</div>
                            <div class="text-sm font-semibold text-gray-700">{{ $package->limitLabel($package->max_subdomains) }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg px-3 py-2.5">
                            <div class="text-xs text-gray-400 mb-0.5">Databases</div>
                            <div class="text-sm font-semibold text-gray-700">{{ $package->limitLabel($package->max_databases) }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg px-3 py-2.5">
                            <div class="text-xs text-gray-400 mb-0.5">Email Accounts</div>
                            <div class="text-sm font-semibold text-gray-700">{{ $package->limitLabel($package->max_email_accounts) }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg px-3 py-2.5 col-span-2">
                            <div class="text-xs text-gray-400 mb-0.5">Features</div>
                            <div class="text-sm font-semibold text-gray-700 flex gap-3">
                                @if($package->ssl_enabled)
                                    <span class="text-green-600">SSL</span>
                                @endif
                                @if($package->cron_jobs_enabled)
                                    <span class="text-green-600">Cron</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between">
                        <a href="{{ route('packages.edit', $package) }}"
                           class="text-sm text-indigo-600 hover:text-indigo-800 font-medium transition">
                            Edit
                        </a>
                        @if($package->accounts_count === 0)
                            <x-delete-modal
                                :action="route('packages.destroy', $package)"
                                title="Delete Package"
                                message="This will permanently delete the '{{ $package->name }}' package. This cannot be undone."
                                :confirm-password="false">
                                <x-slot name="trigger">
                                    <button type="button" class="text-sm text-red-500 hover:text-red-700 font-medium transition">Delete</button>
                                </x-slot>
                            </x-delete-modal>
                        @else
                            <span class="text-xs text-gray-400">In use</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-app-layout>
