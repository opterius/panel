<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">{{ __('dashboard.my_dashboard') }}</h2>
    </x-slot>

    @php
        // Switch to "current account" mode - only show data for the actively selected account
        $primaryAccount = auth()->user()->currentAccount();
        $accountIds = $primaryAccount ? [$primaryAccount->id] : [];
        $myDomains = \App\Models\Domain::whereIn('account_id', $accountIds)->whereNull('parent_id')->get();
        $mySubdomains = \App\Models\Domain::whereIn('account_id', $accountIds)->whereNotNull('parent_id')->count();
        $myDatabases = \App\Models\Database::whereIn('account_id', $accountIds)->count();
        $myCerts = \App\Models\SslCertificate::whereHas('domain', fn($q) => $q->whereIn('account_id', $accountIds))->count();
        $myCrons = \App\Models\CronJob::whereIn('account_id', $accountIds)->count();
        $myEmails = \App\Models\EmailAccount::whereHas('domain', fn($q) => $q->whereIn('account_id', $accountIds))->count();

        // Get stats from agent for the current account
        $stats = null;
        if ($primaryAccount) {
            $response = \App\Services\AgentService::for($primaryAccount->server)->post('/stats/account', [
                'username'  => $primaryAccount->username,
                'domains'   => $primaryAccount->domains->pluck('domain')->toArray(),
                'databases' => $primaryAccount->databases->pluck('name')->toArray(),
            ]);
            if ($response && $response->successful()) {
                $stats = $response->json('stats');
            }
        }

        $diskQuota = $primaryAccount?->disk_quota ?? 0;
        $diskUsed = $stats['disk_usage']['total_mb'] ?? 0;
        $diskPercent = $diskQuota > 0 ? min(100, round(($diskUsed / $diskQuota) * 100)) : 0;
        $bandwidthTotal = $stats['bandwidth']['total_mb'] ?? 0;
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column: Feature Icons -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl shadow-sm p-6">

            {{-- Domain --}}
            <div class="mb-6">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 px-1">{{ __('dashboard.domain') }}</h3>
                <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-1">
                    <a href="{{ route('user.domains.index') }}" class="group flex flex-col items-center p-3 rounded-xl hover:bg-indigo-50 transition">
                        <svg class="w-9 h-9 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                        <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-indigo-700">{{ __('dashboard.domain') }}</span>
                    </a>
                    @if($myDomains->isNotEmpty())
                        <a href="{{ route('user.subdomains.index') }}" class="group flex flex-col items-center p-3 rounded-xl hover:bg-sky-50 transition">
                            <svg class="w-9 h-9 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                            <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-sky-700">{{ __('dashboard.subdomains') }}</span>
                        </a>
                        <a href="{{ route('user.dns.index', $myDomains->first()) }}" class="group flex flex-col items-center p-3 rounded-xl hover:bg-rose-50 transition">
                            <svg class="w-9 h-9 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>
                            <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-rose-700">{{ __('dashboard.dns') }}</span>
                        </a>
                    @endif
                    <a href="{{ route('user.ssl.index') }}" class="group flex flex-col items-center p-3 rounded-xl hover:bg-green-50 transition">
                        <svg class="w-9 h-9 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-green-700">{{ __('dashboard.ssl') }}</span>
                    </a>
                    <a href="{{ route('user.aliases.index') }}" class="group flex flex-col items-center p-3 rounded-xl hover:bg-amber-50 transition">
                        <svg class="w-9 h-9 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                        <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-amber-700">{{ __('dashboard.aliases') }}</span>
                    </a>
                    <a href="{{ route('user.redirects.index') }}" class="group flex flex-col items-center p-3 rounded-xl hover:bg-pink-50 transition">
                        <svg class="w-9 h-9 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-pink-700">{{ __('dashboard.redirects') }}</span>
                    </a>
                </div>
            </div>

            {{-- Files --}}
            <div class="mb-6">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 px-1">{{ __('dashboard.files') }}</h3>
                <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-1">
                    <a href="{{ route('user.filemanager.index') }}" class="group flex flex-col items-center p-3 rounded-xl hover:bg-blue-50 transition">
                        <svg class="w-9 h-9 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                        <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-blue-700">{{ __('dashboard.file_manager') }}</span>
                    </a>
                    <a href="{{ route('user.ftp.index') }}" class="group flex flex-col items-center p-3 rounded-xl hover:bg-sky-50 transition">
                        <svg class="w-9 h-9 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                        <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-sky-700">{{ __('dashboard.ftp') }}</span>
                    </a>
                    <a href="{{ route('user.ssh.index') }}" class="group flex flex-col items-center p-3 rounded-xl hover:bg-gray-100 transition">
                        <svg class="w-9 h-9 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-gray-800">{{ __('dashboard.ssh') }}</span>
                    </a>
                </div>
            </div>

            {{-- Databases --}}
            <div class="mb-6">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 px-1">{{ __('dashboard.databases') }}</h3>
                <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-1">
                    <a href="{{ route('user.databases.index') }}" class="group flex flex-col items-center p-3 rounded-xl hover:bg-purple-50 transition">
                        <svg class="w-9 h-9 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
                        <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-purple-700">{{ __('dashboard.databases') }}</span>
                    </a>
                    <a href="{{ str_replace('SERVER_IP', $myDomains->first()?->account?->server?->ip_address ?? 'localhost', config('opterius.phpmyadmin_url', 'https://SERVER_IP:8081')) }}" target="_blank" class="group flex flex-col items-center p-3 rounded-xl hover:bg-amber-50 transition">
                        <svg class="w-9 h-9 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-amber-700">{{ __('dashboard.phpmyadmin') }}</span>
                    </a>
                </div>
            </div>

            {{-- Email --}}
            <div class="mb-6">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 px-1">{{ __('dashboard.email') }}</h3>
                <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-1">
                    <a href="{{ route('user.emails.index') }}" class="group flex flex-col items-center p-3 rounded-xl hover:bg-orange-50 transition">
                        <svg class="w-9 h-9 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-orange-700">{{ __('dashboard.email_accounts') }}</span>
                    </a>
                    <a href="{{ route('user.forwarders.index') }}" class="group flex flex-col items-center p-3 rounded-xl hover:bg-cyan-50 transition">
                        <svg class="w-9 h-9 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-cyan-700">{{ __('dashboard.forwarders') }}</span>
                    </a>
                    <a href="{{ route('user.autoresponders.index') }}" class="group flex flex-col items-center p-3 rounded-xl hover:bg-violet-50 transition">
                        <svg class="w-9 h-9 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                        <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-violet-700">{{ __('dashboard.autoresponders') }}</span>
                    </a>
                    <a href="{{ str_replace('SERVER_IP', $myDomains->first()?->account?->server?->ip_address ?? 'localhost', config('opterius.webmail_url', 'https://SERVER_IP:8080')) }}" target="_blank" class="group flex flex-col items-center p-3 rounded-xl hover:bg-orange-50 transition">
                        <svg class="w-9 h-9 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                        <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-orange-700">{{ __('dashboard.webmail') }}</span>
                    </a>
                </div>
            </div>

            {{-- Software --}}
            <div class="mb-6">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 px-1">{{ __('dashboard.software') }}</h3>
                <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-1">
                    <a href="{{ route('user.wordpress.index') }}" class="group flex flex-col items-center p-3 rounded-xl hover:bg-blue-50 transition">
                        <svg class="w-9 h-9 text-blue-600" viewBox="0 0 24 24" fill="currentColor"><path d="M21.469 6.825c.84 1.537 1.318 3.3 1.318 5.175 0 3.979-2.156 7.456-5.363 9.325l3.295-9.527c.615-1.539.82-2.771.82-3.864 0-.405-.027-.78-.07-1.109m-7.981.105c.647-.034 1.229-.1 1.229-.1.578-.068.51-.919-.068-.886 0 0-1.739.136-2.86.136-1.052 0-2.825-.136-2.825-.136-.579-.034-.646.852-.068.886 0 0 .549.066 1.13.1l1.68 4.605-2.37 7.08L5.554 6.93c.647-.034 1.229-.1 1.229-.1.578-.068.51-.919-.068-.886 0 0-1.739.136-2.86.136-.201 0-.438-.005-.689-.015C4.911 3.15 8.186 1.213 11.951 1.213c2.8 0 5.35 1.072 7.269 2.818-.046-.003-.091-.009-.141-.009-1.052 0-1.798.919-1.798 1.904 0 .886.51 1.636 1.054 2.522.408.715.886 1.636.886 2.964 0 .919-.354 1.985-.82 3.472l-1.075 3.586-3.894-11.575m-3.007 1.21l-3.357 9.755-2.96-8.115c-.133-.35-.257-.671-.38-.96A8.757 8.757 0 0 1 3.213 12c0-1.665.47-3.222 1.275-4.545m8.463 8.847l2.482-7.19 2.54 6.946c.017.04.036.078.054.114-1.589.666-3.32 1.038-5.138 1.038-.583 0-1.153-.044-1.712-.12" /></svg>
                        <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-blue-700">{{ __('dashboard.wordpress') }}</span>
                    </a>
                    <a href="{{ route('user.laravel.index') }}" class="group flex flex-col items-center p-3 rounded-xl hover:bg-red-50 transition">
                        <svg class="w-9 h-9 text-red-500" viewBox="0 0 24 24" fill="currentColor"><path d="M23.642 5.43a.364.364 0 01.014.1v5.149a.361.361 0 01-.181.311l-4.32 2.494v4.934a.36.36 0 01-.181.311l-9.033 5.215a.367.367 0 01-.086.036.369.369 0 01-.274-.036L.548 18.73A.361.361 0 01.364 18.42V2.881a.361.361 0 01.014-.1.357.357 0 01.04-.09.36.36 0 01.056-.063l.01-.01a.36.36 0 01.077-.054L4.93.387a.361.361 0 01.361 0l4.369 2.523a.361.361 0 01.18.311v9.648l3.806-2.198V5.523a.358.358 0 01.015-.1.36.36 0 01.095-.153l.01-.01a.363.363 0 01.077-.054l4.369-2.523a.361.361 0 01.36 0l4.37 2.523a.36.36 0 01.077.054l.01.01a.36.36 0 01.055.063.361.361 0 01.04.09z"/></svg>
                        <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-red-700">{{ __('dashboard.laravel') }}</span>
                    </a>
                </div>
            </div>

            {{-- Advanced --}}
            <div>
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 px-1">{{ __('dashboard.advanced') }}</h3>
                <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-1">
                    <a href="{{ route('user.php.index') }}" class="group flex flex-col items-center p-3 rounded-xl hover:bg-indigo-50 transition">
                        <svg class="w-9 h-9 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-indigo-700">{{ __('dashboard.php_version_label') }}</span>
                    </a>
                    <a href="{{ route('user.cronjobs.index') }}" class="group flex flex-col items-center p-3 rounded-xl hover:bg-teal-50 transition">
                        <svg class="w-9 h-9 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span class="mt-2 text-xs font-medium text-gray-600 group-hover:text-teal-700">{{ __('dashboard.cron_jobs') }}</span>
                    </a>
                </div>
            </div>

          </div>
        </div>

        <!-- Right Column: Account Summary & Stats -->
        <div class="space-y-5">

            {{-- Account Info --}}
            @if($primaryAccount)
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-800">{{ $myDomains->first()?->domain ?? $primaryAccount->username }}</div>
                            <div class="text-xs text-gray-500">{{ $primaryAccount->server->name }} &middot; PHP {{ $primaryAccount->php_version }}</div>
                        </div>
                    </div>

                    {{-- Disk Usage --}}
                    <div class="mb-4">
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="font-medium text-gray-700">{{ __('dashboard.disk_usage') }}</span>
                            <span class="text-gray-500">
                                {{ number_format($diskUsed, 1) }} MB
                                @if($diskQuota > 0) / {{ $diskQuota >= 1024 ? number_format($diskQuota / 1024, 1) . ' GB' : $diskQuota . ' MB' }} @else / {{ __('dashboard.unlimited') }} @endif
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div class="h-2.5 rounded-full transition-all {{ $diskPercent > 90 ? 'bg-red-500' : ($diskPercent > 70 ? 'bg-amber-500' : 'bg-indigo-500') }}"
                                 style="width: {{ $diskQuota > 0 ? $diskPercent : 0 }}%"></div>
                        </div>
                    </div>

                    {{-- Bandwidth --}}
                    <div class="mb-4">
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="font-medium text-gray-700">{{ __('dashboard.bandwidth') }}</span>
                            <span class="text-gray-500">
                                {{ $bandwidthTotal >= 1024 ? number_format($bandwidthTotal / 1024, 2) . ' GB' : number_format($bandwidthTotal, 1) . ' MB' }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div class="h-2.5 rounded-full bg-emerald-500" style="width: 0%"></div>
                        </div>
                    </div>

                    {{-- Quick Stats Grid --}}
                    <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-100">
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <div class="text-lg font-bold text-gray-900">{{ $myDomains->count() }}</div>
                            <div class="text-xs text-gray-500">{{ $myDomains->count() === 1 ? __('dashboard.domain') : __('dashboard.domains') }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <div class="text-lg font-bold text-gray-900">{{ $mySubdomains }}</div>
                            <div class="text-xs text-gray-500">{{ __('dashboard.subdomains') }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <div class="text-lg font-bold text-gray-900">{{ $myDatabases }}</div>
                            <div class="text-xs text-gray-500">{{ __('dashboard.databases') }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <div class="text-lg font-bold text-gray-900">{{ $myEmails }}</div>
                            <div class="text-xs text-gray-500">{{ __('dashboard.email_accounts') }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <div class="text-lg font-bold text-gray-900">{{ $myCerts }}</div>
                            <div class="text-xs text-gray-500">{{ __('dashboard.ssl_certificates') }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <div class="text-lg font-bold text-gray-900">{{ $myCrons }}</div>
                            <div class="text-xs text-gray-500">{{ __('dashboard.cron_jobs') }}</div>
                        </div>
                    </div>
                </div>

                {{-- Disk Breakdown --}}
                @if($stats)
                    <div class="bg-white rounded-xl shadow-sm p-5">
                        <h4 class="text-sm font-semibold text-gray-800 mb-4">{{ __('dashboard.storage_breakdown') }}</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 rounded-full bg-indigo-500"></div>
                                    <span class="text-sm text-gray-600">{{ __('dashboard.files_label') }}</span>
                                </div>
                                <span class="text-sm font-medium text-gray-800">{{ number_format($stats['disk_usage']['home_mb'] ?? 0, 1) }} MB</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                                    <span class="text-sm text-gray-600">{{ __('dashboard.email_label') }}</span>
                                </div>
                                <span class="text-sm font-medium text-gray-800">{{ number_format($stats['disk_usage']['email_mb'] ?? 0, 1) }} MB</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                                    <span class="text-sm text-gray-600">{{ __('dashboard.databases_label') }}</span>
                                </div>
                                <span class="text-sm font-medium text-gray-800">{{ number_format($stats['database_size_mb'] ?? 0, 1) }} MB</span>
                            </div>
                            <div class="pt-2 border-t border-gray-100 flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700">{{ __('dashboard.inodes') }}</span>
                                <span class="text-sm font-medium text-gray-800">{{ number_format($stats['inode_count'] ?? 0) }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Package Info --}}
                @if($primaryAccount->package)
                    <div class="bg-white rounded-xl shadow-sm p-5">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">{{ __('dashboard.package') }}</h4>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm text-gray-600">{{ $primaryAccount->package->name }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                PHP {{ $primaryAccount->php_version }}
                            </span>
                        </div>
                        <div class="space-y-2 text-xs text-gray-500">
                            <div class="flex justify-between">
                                <span>{{ __('dashboard.disk_quota') }}</span>
                                <span class="font-medium text-gray-700">{{ $primaryAccount->package->diskQuotaLabel() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>{{ __('dashboard.bandwidth') }}</span>
                                <span class="font-medium text-gray-700">{{ $primaryAccount->package->bandwidthLabel() }}</span>
                            </div>
                            @if($primaryAccount->package->max_databases !== null)
                                <div class="flex justify-between">
                                    <span>{{ __('dashboard.databases') }}</span>
                                    <span class="font-medium text-gray-700">{{ $primaryAccount->package->limitLabel($primaryAccount->package->max_databases) }}</span>
                                </div>
                            @endif
                            @if($primaryAccount->package->max_email_accounts !== null)
                                <div class="flex justify-between">
                                    <span>{{ __('dashboard.email_accounts') }}</span>
                                    <span class="font-medium text-gray-700">{{ $primaryAccount->package->limitLabel($primaryAccount->package->max_email_accounts) }}</span>
                                </div>
                            @endif
                            @if($primaryAccount->package->max_subdomains !== null)
                                <div class="flex justify-between">
                                    <span>{{ __('dashboard.subdomains') }}</span>
                                    <span class="font-medium text-gray-700">{{ $primaryAccount->package->limitLabel($primaryAccount->package->max_subdomains) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endif

        </div>
    </div>
</x-user-layout>
