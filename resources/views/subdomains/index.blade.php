<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">{{ __('domains.subdomains') }}</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg text-sm">{{ session('warning') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <div class="mb-6">
        <p class="text-sm text-gray-500">Manage subdomains for each of your domains. Subdomains share files with the parent domain's account.</p>
    </div>

    @if($domains->isEmpty())
        <div class="bg-white rounded-xl shadow-sm px-6 py-16 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
            <h3 class="mt-4 text-base font-medium text-gray-700">No domains</h3>
            <p class="mt-2 text-sm text-gray-500">You need a domain before you can create subdomains.</p>
        </div>
    @else
        <div class="space-y-5">
            @foreach($domains as $domain)
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    {{-- Domain Header --}}
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">{{ $domain->domain }}</div>
                                <div class="text-xs text-gray-500">{{ $domain->subdomains->count() }} subdomain(s)</div>
                            </div>
                        </div>
                        <a href="{{ route('user.subdomains.create', $domain) }}"
                           class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                            Add Subdomain
                        </a>
                    </div>

                    {{-- Subdomains list --}}
                    @if($domain->subdomains->isNotEmpty())
                        <div class="divide-y divide-gray-50">
                            @foreach($domain->subdomains as $sub)
                                <div class="px-6 py-3 flex items-center justify-between">
                                    <div class="flex items-center space-x-3 min-w-0 flex-1">
                                        <svg class="w-4 h-4 text-sky-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm font-medium text-gray-800 truncate">{{ $sub->domain }}</div>
                                            <div class="text-xs text-gray-500 truncate font-mono">{{ $sub->document_root }}</div>
                                        </div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium shrink-0
                                            @if($sub->status === 'active') bg-green-100 text-green-700
                                            @elseif($sub->status === 'error') bg-red-100 text-red-700
                                            @else bg-gray-100 text-gray-600
                                            @endif">
                                            {{ ucfirst($sub->status) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-2 ml-4">
                                        <a href="{{ route('user.filemanager.index', ['path' => $sub->document_root]) }}"
                                           class="text-gray-400 hover:text-indigo-600 transition" title="File Manager">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                                        </a>
                                        <x-delete-modal
                                            :action="route('user.subdomains.destroy', $sub)"
                                            title="Delete Subdomain"
                                            :message="'This will delete the subdomain ' . $sub->domain . ' and its Nginx vhost. The parent domain is not affected.'"
                                            :confirm-text="$sub->domain"
                                            :confirm-password="true">
                                            <x-slot name="trigger">
                                                <button type="button" class="text-gray-400 hover:text-red-600 transition" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </x-slot>
                                        </x-delete-modal>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="px-6 py-4 text-center text-sm text-gray-400 italic">
                            No subdomains yet
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-user-layout>
