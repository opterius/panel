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

                    {{-- Catch-all (multi-tenant SaaS) --}}
                    @php
                        $hasWildcard = ($domain->sslCertificate?->type === 'wildcard' && $domain->sslCertificate?->status === 'active')
                                       || !empty($domain->wildcard_active);
                        $defaultRoot = $domain->document_root ?: '/home/'.$domain->account?->username.'/'.$domain->domain.'/public_html';
                    @endphp
                    <div class="px-6 py-3 border-t border-gray-100 bg-violet-50/30"
                         x-data="{ open: false }">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-gray-800">
                                    Catch-all subdomains
                                    @if($domain->catchall_subdomains)
                                        <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-sm font-medium bg-violet-100 text-violet-700">Active</span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    Serve every <code class="font-mono">*.{{ $domain->domain }}</code> subdomain from a single nginx vhost — no per-tenant record. Designed for multi-tenant SaaS (e.g. <code class="font-mono">acme1.{{ $domain->domain }}</code>).
                                </p>
                                <p class="text-sm text-gray-500 mt-1">
                                    Manually-created subdomains keep their own document root — nginx exact-match wins, the catch-all only handles tenants without a record.
                                </p>
                                @if($domain->catchall_subdomains && $domain->catchall_document_root)
                                    <p class="text-sm text-violet-700 mt-2">
                                        Catch-all serves from <code class="font-mono bg-violet-100 px-1 rounded">{{ $domain->catchall_document_root }}</code>
                                    </p>
                                @elseif($domain->catchall_subdomains)
                                    <p class="text-sm text-violet-700 mt-2">
                                        Catch-all serves from the main domain's document root.
                                    </p>
                                @endif
                                @if(!$hasWildcard)
                                    <p class="text-sm text-amber-700 mt-2 flex items-start gap-1">
                                        <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
                                        <span>Issue a wildcard SSL first — without it, HTTPS will warn on every tenant subdomain.</span>
                                    </p>
                                @endif
                            </div>
                            <div class="shrink-0 flex items-center gap-2">
                                @if($domain->catchall_subdomains)
                                    <button type="button" @click="open = true"
                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-violet-600 text-white hover:bg-violet-700 transition"
                                        title="Change document root for *.{{ $domain->domain }}">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Edit
                                    </button>
                                    <form action="{{ route('user.domains.catchall', $domain) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="enabled" value="0">
                                        <button type="submit"
                                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-white text-violet-700 border border-violet-300 hover:bg-violet-50 transition">
                                            Disable
                                        </button>
                                    </form>
                                @else
                                    <button type="button" @click="open = true"
                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-violet-600 text-white hover:bg-violet-700 transition">
                                        Enable catch-all
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- Enable/edit dialog --}}
                        <template x-teleport="body">
                            <div x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                                <div x-show="open" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="open = false"></div>
                                <div class="fixed inset-0 flex items-center justify-center p-4">
                                    <div x-show="open" @click.stop @keydown.escape.window="open = false"
                                         class="bg-white rounded-xl shadow-2xl w-full max-w-xl overflow-hidden">
                                        <form action="{{ route('user.domains.catchall', $domain) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="enabled" value="1">
                                            <div class="p-6">
                                                <h3 class="text-lg font-semibold text-gray-900">
                                                    {{ $domain->catchall_subdomains ? 'Edit catch-all for' : 'Enable catch-all for' }} *.{{ $domain->domain }}
                                                </h3>
                                                <p class="mt-1 text-sm text-gray-500">
                                                    Every tenant subdomain will be served from the document root below. Use the main site's root, or pick a different folder (e.g. a separate SaaS app).
                                                </p>
                                                <div class="mt-4">
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Document root</label>
                                                    <input type="text" name="document_root"
                                                           value="{{ $domain->catchall_document_root ?: $defaultRoot }}"
                                                           class="w-full font-mono text-sm rounded-lg border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                                           placeholder="/home/{{ $domain->account?->username }}/{{ $domain->domain }}/saas-app/public">
                                                    <p class="mt-1 text-sm text-gray-500">Absolute path on the server. Must already exist. Changing this rewrites the vhost and reloads nginx.</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50">
                                                <button type="button" @click="open = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-violet-600 hover:bg-violet-700 rounded-lg">
                                                    {{ $domain->catchall_subdomains ? 'Update' : 'Enable catch-all' }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </template>
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
