<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">My Dashboard</h2>
    </x-slot>

    @php
        $accountIds = auth()->user()->accessibleAccountIds();
        $myDomains = \App\Models\Domain::whereIn('account_id', $accountIds)->get();
        $myDatabases = \App\Models\Database::whereIn('account_id', $accountIds)->count();
        $myCerts = \App\Models\SslCertificate::whereHas('domain', fn($q) => $q->whereIn('account_id', $accountIds))->count();
        $myCrons = \App\Models\CronJob::whereIn('account_id', $accountIds)->count();
        $myEmails = \App\Models\EmailAccount::whereHas('domain', fn($q) => $q->whereIn('account_id', $accountIds))->count();
    @endphp

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-xs font-medium text-gray-400 uppercase">Domains</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $myDomains->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-xs font-medium text-gray-400 uppercase">Databases</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $myDatabases }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-xs font-medium text-gray-400 uppercase">Email</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $myEmails }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-xs font-medium text-gray-400 uppercase">SSL</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $myCerts }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-xs font-medium text-gray-400 uppercase">Cron Jobs</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $myCrons }}</div>
        </div>
    </div>

    <!-- Feature Icons Grid (cPanel style) -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
        <h3 class="text-base font-semibold text-gray-800 mb-6">Manage Your Hosting</h3>

        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-4">
            <a href="{{ route('user.domains.index') }}" class="group flex flex-col items-center p-4 rounded-xl hover:bg-indigo-50 transition">
                <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center group-hover:bg-indigo-200 transition">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                </div>
                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-indigo-700">Domains</span>
            </a>

            <a href="{{ route('user.filemanager.index') }}" class="group flex flex-col items-center p-4 rounded-xl hover:bg-blue-50 transition">
                <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center group-hover:bg-blue-200 transition">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                </div>
                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-blue-700">File Manager</span>
            </a>

            <a href="{{ route('user.databases.index') }}" class="group flex flex-col items-center p-4 rounded-xl hover:bg-purple-50 transition">
                <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center group-hover:bg-purple-200 transition">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
                </div>
                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-purple-700">Databases</span>
            </a>

            <a href="{{ route('user.emails.index') }}" class="group flex flex-col items-center p-4 rounded-xl hover:bg-orange-50 transition">
                <div class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center group-hover:bg-orange-200 transition">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                </div>
                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-orange-700">Email</span>
            </a>

            <a href="{{ route('user.ssl.index') }}" class="group flex flex-col items-center p-4 rounded-xl hover:bg-green-50 transition">
                <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center group-hover:bg-green-200 transition">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                </div>
                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-green-700">SSL</span>
            </a>

            <a href="{{ route('user.forwarders.index') }}" class="group flex flex-col items-center p-4 rounded-xl hover:bg-cyan-50 transition">
                <div class="w-12 h-12 rounded-xl bg-cyan-100 flex items-center justify-center group-hover:bg-cyan-200 transition">
                    <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                </div>
                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-cyan-700">Forwarders</span>
            </a>

            <a href="{{ route('user.cronjobs.index') }}" class="group flex flex-col items-center p-4 rounded-xl hover:bg-teal-50 transition">
                <div class="w-12 h-12 rounded-xl bg-teal-100 flex items-center justify-center group-hover:bg-teal-200 transition">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-teal-700">Cron Jobs</span>
            </a>

            <a href="{{ route('user.ftp.index') }}" class="group flex flex-col items-center p-4 rounded-xl hover:bg-sky-50 transition">
                <div class="w-12 h-12 rounded-xl bg-sky-100 flex items-center justify-center group-hover:bg-sky-200 transition">
                    <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                </div>
                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-sky-700">FTP</span>
            </a>

            <a href="{{ route('user.ssh.index') }}" class="group flex flex-col items-center p-4 rounded-xl hover:bg-gray-50 transition">
                <div class="w-12 h-12 rounded-xl bg-gray-200 flex items-center justify-center group-hover:bg-gray-300 transition">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-gray-800">SSH</span>
            </a>

            <a href="{{ route('user.wordpress.index') }}" class="group flex flex-col items-center p-4 rounded-xl hover:bg-blue-50 transition">
                <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center group-hover:bg-blue-200 transition">
                    <svg class="w-6 h-6 text-blue-600" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"/></svg>
                </div>
                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-blue-700">WordPress</span>
            </a>

            <a href="{{ route('user.laravel.index') }}" class="group flex flex-col items-center p-4 rounded-xl hover:bg-red-50 transition">
                <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center group-hover:bg-red-200 transition">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                </div>
                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-red-700">Laravel</span>
            </a>

            <a href="{{ route('user.dns.index', $myDomains->first() ?? 0) }}" class="group flex flex-col items-center p-4 rounded-xl hover:bg-rose-50 transition {{ $myDomains->isEmpty() ? 'opacity-50 pointer-events-none' : '' }}">
                <div class="w-12 h-12 rounded-xl bg-rose-100 flex items-center justify-center group-hover:bg-rose-200 transition">
                    <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                </div>
                <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-rose-700">DNS</span>
            </a>
        </div>
    </div>

    <!-- My Domains -->
    @if($myDomains->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">My Domains</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($myDomains as $domain)
                    <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-100 transition">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                @if($domain->status === 'active') bg-green-100
                                @elseif($domain->status === 'suspended') bg-amber-100
                                @else bg-gray-100
                                @endif">
                                <svg class="w-5 h-5
                                    @if($domain->status === 'active') text-green-600
                                    @elseif($domain->status === 'suspended') text-amber-600
                                    @else text-gray-400
                                    @endif"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">{{ $domain->domain }}</div>
                                <div class="text-xs text-gray-500">PHP {{ $domain->php_version }} &middot; {{ $domain->document_root }}</div>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                            @if($domain->status === 'active') bg-green-100 text-green-700
                            @elseif($domain->status === 'suspended') bg-amber-100 text-amber-700
                            @else bg-gray-100 text-gray-600
                            @endif">
                            {{ ucfirst($domain->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-user-layout>
