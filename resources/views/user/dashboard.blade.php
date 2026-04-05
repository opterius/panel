<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">My Dashboard</h2>
    </x-slot>

    @php
        $myDomains = \App\Models\Domain::whereHas('account', fn($q) => $q->where('user_id', Auth::id()))->count();
        $myDatabases = \App\Models\Database::whereHas('account', fn($q) => $q->where('user_id', Auth::id()))->count();
        $myCerts = \App\Models\SslCertificate::whereHas('domain.account', fn($q) => $q->where('user_id', Auth::id()))->count();
        $myCrons = \App\Models\CronJob::whereHas('account', fn($q) => $q->where('user_id', Auth::id()))->count();
    @endphp

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center space-x-4">
            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">Domains</div>
                <div class="text-2xl font-bold text-gray-900">{{ $myDomains }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center space-x-4">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">Databases</div>
                <div class="text-2xl font-bold text-gray-900">{{ $myDatabases }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center space-x-4">
            <div class="w-12 h-12 bg-rose-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">SSL Certificates</div>
                <div class="text-2xl font-bold text-gray-900">{{ $myCerts }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center space-x-4">
            <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">Cron Jobs</div>
                <div class="text-2xl font-bold text-gray-900">{{ $myCrons }}</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-base font-semibold text-gray-800 mb-4">Quick Actions</h3>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <a href="{{ route('user.domains.create') }}" class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                <span class="text-sm font-medium text-gray-700">Add Domain</span>
            </a>
            <a href="{{ route('user.databases.create') }}" class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                <span class="text-sm font-medium text-gray-700">Create Database</span>
            </a>
            <a href="{{ route('user.ssl.index') }}" class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                <span class="text-sm font-medium text-gray-700">Issue SSL</span>
            </a>
            <a href="{{ route('user.filemanager.index') }}" class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                <span class="text-sm font-medium text-gray-700">File Manager</span>
            </a>
        </div>
    </div>
</x-user-layout>
