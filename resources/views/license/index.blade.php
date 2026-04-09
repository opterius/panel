<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">License</h2>
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

    <!-- License Status -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">License Status</h3>
        </div>
        <div class="px-6 py-6">
            <div class="flex items-start space-x-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0
                    @if($status['valid'] ?? false) bg-green-100
                    @else bg-red-100
                    @endif">
                    @if($status['valid'] ?? false)
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    @else
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                    @endif
                </div>
                <div class="flex-1">
                    <div class="flex items-center space-x-3">
                        <span class="text-lg font-semibold
                            @if($status['valid'] ?? false) text-green-700 @else text-red-700 @endif">
                            @if($status['valid'] ?? false)
                                License Active
                            @else
                                {{ ucfirst($status['reason'] ?? 'Invalid') }}
                            @endif
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                            @if($status['valid'] ?? false) bg-green-100 text-green-700
                            @else bg-red-100 text-red-700
                            @endif">
                            {{ ucfirst($status['plan']['name'] ?? 'Unknown') }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">{{ $status['message'] ?? '' }}</p>
                </div>
                <form action="{{ route('admin.license.refresh') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3 py-2 text-sm font-medium text-indigo-600 bg-white border border-indigo-300 rounded-lg hover:bg-indigo-50 transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Re-check
                    </button>
                </form>
            </div>

            <!-- Details grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-6 border-t border-gray-100">
                <div>
                    <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Plan</span>
                    <p class="mt-1 text-sm font-semibold text-gray-800">{{ ucfirst($status['plan']['name'] ?? 'Unknown') }}</p>
                </div>
                <div>
                    <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Max Domains</span>
                    <p class="mt-1 text-sm font-semibold text-gray-800">
                        {{ ($status['max_domains'] ?? 1) === 0 ? 'Unlimited' : ($status['max_domains'] ?? 1) }}
                    </p>
                </div>
                <div>
                    <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Expires</span>
                    <p class="mt-1 text-sm font-semibold text-gray-800">
                        @if(!empty($status['expires_at']))
                            {{ \Carbon\Carbon::parse($status['expires_at'])->format('M d, Y') }}
                        @else
                            —
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Status</span>
                    <p class="mt-1 text-sm font-semibold
                        @if($status['valid'] ?? false) text-green-600 @else text-red-600 @endif">
                        {{ ($status['valid'] ?? false) ? 'Valid' : 'Invalid' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- License Key -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">License Key</h3>
            <p class="text-sm text-gray-500 mt-1">Enter your Opterius license key. Get one at <a href="https://opterius.com" target="_blank" class="text-indigo-600 hover:text-indigo-800">opterius.com</a>.</p>
        </div>
        <form action="{{ route('admin.license.update') }}" method="POST" class="px-6 py-5">
            @csrf
            @method('PUT')
            <div class="flex items-end gap-4">
                <div class="flex-1">
                    <label for="license_key" class="block text-sm font-medium text-gray-700 mb-1.5">License Key</label>
                    <input type="text" name="license_key" id="license_key"
                        value="{{ config('opterius.license_key') }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX">
                    @error('license_key')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    Save Key
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
