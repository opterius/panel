<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Dashboard</h2>
    </x-slot>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center space-x-4">
            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">Domains</div>
                <div class="text-2xl font-bold text-gray-900">{{ Auth::user()->isAdmin() ? \App\Models\Domain::count() : 0 }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center space-x-4">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">Databases</div>
                <div class="text-2xl font-bold text-gray-900">{{ Auth::user()->isAdmin() ? \App\Models\Database::count() : 0 }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center space-x-4">
            <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">Accounts</div>
                <div class="text-2xl font-bold text-gray-900">{{ Auth::user()->isAdmin() ? \App\Models\Account::count() : 0 }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center space-x-4">
            <div class="w-12 h-12 bg-rose-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-500">SSL Certificates</div>
                <div class="text-2xl font-bold text-gray-900">{{ Auth::user()->isAdmin() ? \App\Models\SslCertificate::count() : 0 }}</div>
            </div>
        </div>
    </div>

    <!-- Server Resource Usage + Server Info -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Resource Usage -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-6">Resource Usage</h3>

            <div class="space-y-6">
                <!-- CPU -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-600">CPU</span>
                        <span class="text-sm font-semibold text-gray-800">--</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3">
                        <div class="bg-indigo-500 h-3 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Memory -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-600">Memory</span>
                        <span class="text-sm font-semibold text-gray-800">--</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3">
                        <div class="bg-green-500 h-3 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Disk -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-600">Disk</span>
                        <span class="text-sm font-semibold text-gray-800">--</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3">
                        <div class="bg-amber-500 h-3 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Load Average -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-600">Load Average</span>
                        <span class="text-sm font-semibold text-gray-800">--</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3">
                        <div class="bg-rose-500 h-3 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <p class="text-xs text-gray-400 mt-6">Live data will be available after connecting the agent.</p>
        </div>

        <!-- Server Info -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-6">Server Info</h3>

            <dl class="space-y-4">
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Hostname</dt>
                    <dd class="mt-1 text-sm text-gray-800">--</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">IP Address</dt>
                    <dd class="mt-1 text-sm text-gray-800">--</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Operating System</dt>
                    <dd class="mt-1 text-sm text-gray-800">--</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Uptime</dt>
                    <dd class="mt-1 text-sm text-gray-800">--</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">PHP Version</dt>
                    <dd class="mt-1 text-sm text-gray-800">--</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">MySQL Version</dt>
                    <dd class="mt-1 text-sm text-gray-800">--</dd>
                </div>
            </dl>

            <p class="text-xs text-gray-400 mt-6">Data from agent.</p>
        </div>
    </div>

    <!-- Quick Actions + Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">Quick Actions</h3>

            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('domains.index') }}" class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    <span class="text-sm font-medium text-gray-700">Add Domain</span>
                </a>
                <a href="{{ route('databases.index') }}" class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    <span class="text-sm font-medium text-gray-700">Create Database</span>
                </a>
                <a href="{{ route('ssl.index') }}" class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    <span class="text-sm font-medium text-gray-700">Issue SSL</span>
                </a>
                <a href="{{ route('filemanager.index') }}" class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                    <span class="text-sm font-medium text-gray-700">File Manager</span>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">Recent Activity</h3>

            <div class="text-sm text-gray-400 py-8 text-center">
                No activity yet. Add a domain to get started.
            </div>
        </div>
    </div>
</x-app-layout>
