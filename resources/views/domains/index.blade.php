<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Domains</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg text-sm">
            {{ session('warning') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Header -->
    <div class="mb-6">
        <p class="text-sm text-gray-500">Each account has one main domain. You can create subdomains under each domain.</p>
    </div>

    @if($domains->isEmpty())
        <div class="bg-white rounded-xl shadow-sm px-6 py-16 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
            <h3 class="mt-4 text-base font-medium text-gray-700">No domains yet</h3>
            <p class="mt-2 text-sm text-gray-500">Domains are created when a hosting account is set up.</p>
        </div>
    @else
        <div class="space-y-5">
            @foreach($domains as $domain)
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <!-- Domain Header -->
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center
                                @if($domain->status === 'active') bg-green-100
                                @elseif($domain->status === 'error') bg-red-100
                                @elseif($domain->status === 'suspended') bg-yellow-100
                                @else bg-gray-100
                                @endif">
                                <svg class="w-5 h-5
                                    @if($domain->status === 'active') text-green-600
                                    @elseif($domain->status === 'error') text-red-600
                                    @elseif($domain->status === 'suspended') text-yellow-600
                                    @else text-gray-400
                                    @endif"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">{{ $domain->domain }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ $domain->server->name }} &middot; {{ $domain->account->username }} &middot; PHP {{ $domain->php_version }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            @if($domain->sslCertificate && $domain->sslCertificate->status === 'active')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                    SSL
                                </span>
                            @endif

                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                @if($domain->status === 'active') bg-green-100 text-green-700
                                @elseif($domain->status === 'error') bg-red-100 text-red-700
                                @elseif($domain->status === 'suspended') bg-yellow-100 text-yellow-700
                                @else bg-gray-100 text-gray-600
                                @endif">
                                {{ ucfirst($domain->status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="px-6 py-4 border-b border-gray-100">
                        <div class="grid grid-cols-4 sm:grid-cols-4 md:grid-cols-8 gap-3">
                            {{-- File Manager --}}
                            <a href="{{ route('user.filemanager.index', ['domain' => $domain->id]) }}"
                               class="group flex flex-col items-center p-2 rounded-xl hover:bg-blue-50 transition">
                                <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                                <span class="mt-1 text-xs font-medium text-gray-600 group-hover:text-blue-700">Files</span>
                            </a>

                            {{-- Databases --}}
                            <a href="{{ route('user.databases.index', ['domain' => $domain->id]) }}"
                               class="group flex flex-col items-center p-2 rounded-xl hover:bg-purple-50 transition">
                                <svg class="w-7 h-7 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
                                <span class="mt-1 text-xs font-medium text-gray-600 group-hover:text-purple-700">Databases</span>
                            </a>

                            {{-- SSL --}}
                            <a href="{{ route('user.ssl.index', ['domain' => $domain->id]) }}"
                               class="group flex flex-col items-center p-2 rounded-xl hover:bg-green-50 transition">
                                <svg class="w-7 h-7 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                <span class="mt-1 text-xs font-medium text-gray-600 group-hover:text-green-700">SSL</span>
                            </a>

                            {{-- Email --}}
                            <a href="{{ route('user.emails.index') }}"
                               class="group flex flex-col items-center p-2 rounded-xl hover:bg-orange-50 transition">
                                <svg class="w-7 h-7 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                <span class="mt-1 text-xs font-medium text-gray-600 group-hover:text-orange-700">Email</span>
                            </a>

                            {{-- Cron Jobs --}}
                            <a href="{{ route('user.cronjobs.index', ['domain' => $domain->id]) }}"
                               class="group flex flex-col items-center p-2 rounded-xl hover:bg-teal-50 transition">
                                <svg class="w-7 h-7 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span class="mt-1 text-xs font-medium text-gray-600 group-hover:text-teal-700">Cron</span>
                            </a>

                            {{-- Subdomains --}}
                            <a href="{{ route('user.subdomains.create', $domain) }}"
                               class="group flex flex-col items-center p-2 rounded-xl hover:bg-sky-50 transition">
                                <svg class="w-7 h-7 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                <span class="mt-1 text-xs font-medium text-gray-600 group-hover:text-sky-700">Subdomains</span>
                            </a>

                            {{-- DNS --}}
                            <a href="{{ route('user.dns.index', $domain) }}"
                               class="group flex flex-col items-center p-2 rounded-xl hover:bg-rose-50 transition">
                                <svg class="w-7 h-7 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>
                                <span class="mt-1 text-xs font-medium text-gray-600 group-hover:text-rose-700">DNS</span>
                            </a>

                            {{-- PHP Settings --}}
                            <a href="{{ route('admin.php.index', ['server_id' => $domain->server_id]) }}"
                               class="group flex flex-col items-center p-2 rounded-xl hover:bg-indigo-50 transition">
                                <svg class="w-7 h-7 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <span class="mt-1 text-xs font-medium text-gray-600 group-hover:text-indigo-700">PHP</span>
                            </a>
                        </div>
                    </div>

                    <!-- Subdomains List -->
                    @if($domain->subdomains->isNotEmpty())
                        <div class="px-6 py-3">
                            <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Subdomains</h4>
                            <div class="divide-y divide-gray-50">
                                @foreach($domain->subdomains as $sub)
                                    <div class="flex items-center justify-between py-2.5">
                                        <div class="flex items-center space-x-3">
                                            <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                            <span class="text-sm text-gray-700">{{ $sub->domain }}</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                @if($sub->status === 'active') bg-green-100 text-green-700
                                                @elseif($sub->status === 'error') bg-red-100 text-red-700
                                                @else bg-gray-100 text-gray-600
                                                @endif">
                                                {{ ucfirst($sub->status) }}
                                            </span>
                                        </div>
                                        <x-delete-modal
                                            :action="route('user.domains.destroy', $sub)"
                                            title="Delete Subdomain"
                                            message="This will remove the subdomain {{ $sub->domain }} and its Nginx configuration."
                                            :confirm-password="true">
                                            <x-slot name="trigger">
                                                <button type="button" class="text-gray-400 hover:text-red-600 transition" title="Delete subdomain">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </x-slot>
                                        </x-delete-modal>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-user-layout>
