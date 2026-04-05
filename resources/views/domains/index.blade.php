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

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <p class="text-sm text-gray-500">Manage domains and virtual hosts across your servers.</p>
        </div>
        <a href="{{ route('user.domains.create') }}" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
            Add Domain
        </a>
    </div>

    @if($domains->isEmpty())
        <div class="bg-white rounded-xl shadow-sm px-6 py-16 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
            <h3 class="mt-4 text-base font-medium text-gray-700">No domains yet</h3>
            <p class="mt-2 text-sm text-gray-500">Add your first domain to start hosting.</p>
            <a href="{{ route('user.domains.create') }}" class="mt-6 inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Add Domain
            </a>
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

                            <x-delete-modal
                                :action="route('user.domains.destroy', $domain)"
                                title="Remove Domain"
                                message="This will delete the Nginx vhost and PHP-FPM pool for {{ $domain->domain }} on the server."
                                :confirm-password="true">
                                <x-slot name="trigger">
                                    <button type="button" class="text-gray-400 hover:text-red-600 transition" title="Remove domain">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </x-slot>
                            </x-delete-modal>
                        </div>
                    </div>

                    <!-- Module Icons -->
                    <div class="px-6 py-4">
                        <div class="grid grid-cols-4 sm:grid-cols-4 md:grid-cols-8 gap-3">
                            {{-- File Manager --}}
                            <a href="{{ route('user.filemanager.index', ['domain' => $domain->id]) }}"
                               class="group flex flex-col items-center p-3 rounded-xl hover:bg-indigo-50 transition">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center group-hover:bg-blue-200 transition">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                                </div>
                                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-indigo-700">Files</span>
                            </a>

                            {{-- Databases --}}
                            <a href="{{ route('user.databases.index', ['domain' => $domain->id]) }}"
                               class="group flex flex-col items-center p-3 rounded-xl hover:bg-indigo-50 transition">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center group-hover:bg-purple-200 transition">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
                                </div>
                                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-indigo-700">Databases</span>
                            </a>

                            {{-- SSL --}}
                            <a href="{{ route('user.ssl.index', ['domain' => $domain->id]) }}"
                               class="group flex flex-col items-center p-3 rounded-xl hover:bg-indigo-50 transition">
                                <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center group-hover:bg-green-200 transition">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                </div>
                                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-indigo-700">SSL</span>
                            </a>

                            {{-- Email --}}
                            <a href="{{ route('user.emails.index') }}"
                               class="group flex flex-col items-center p-3 rounded-xl hover:bg-indigo-50 transition">
                                <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center group-hover:bg-orange-200 transition">
                                    <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                </div>
                                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-indigo-700">Email</span>
                            </a>

                            {{-- Cron Jobs --}}
                            <a href="{{ route('user.cronjobs.index', ['domain' => $domain->id]) }}"
                               class="group flex flex-col items-center p-3 rounded-xl hover:bg-indigo-50 transition">
                                <div class="w-8 h-8 rounded-lg bg-teal-100 flex items-center justify-center group-hover:bg-teal-200 transition">
                                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-indigo-700">Cron</span>
                            </a>

                            {{-- Subdomains --}}
                            <a href="{{ route('user.subdomains.create', $domain) }}"
                               class="group flex flex-col items-center p-3 rounded-xl hover:bg-indigo-50 transition">
                                <div class="w-8 h-8 rounded-lg bg-sky-100 flex items-center justify-center group-hover:bg-sky-200 transition">
                                    <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                </div>
                                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-indigo-700">Subdomains</span>
                            </a>

                            {{-- DNS --}}
                            <a href="{{ route('user.dns.index', $domain) }}"
                               class="group flex flex-col items-center p-3 rounded-xl hover:bg-indigo-50 transition">
                                <div class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center group-hover:bg-rose-200 transition">
                                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                                </div>
                                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-indigo-700">DNS</span>
                            </a>

                            {{-- PHP Settings --}}
                            <a href="{{ route('admin.php.index', ['server_id' => $domain->server_id]) }}"
                               class="group flex flex-col items-center p-3 rounded-xl hover:bg-indigo-50 transition">
                                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center group-hover:bg-indigo-200 transition">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>
                                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-indigo-700">PHP</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-user-layout>
