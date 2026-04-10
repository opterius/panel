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
                                {{ ucfirst(is_string($status['reason'] ?? null) ? $status['reason'] : 'Invalid') }}
                            @endif
                        </span>
                        @php
                            // The license server may return `plan` as either a plain
                            // string (old API) or an object with slug/name/etc (new
                            // API). Normalise to a display string here.
                            $planRaw = $status['plan'] ?? 'unknown';
                            if (is_array($planRaw)) {
                                $planLabel = $planRaw['name']
                                    ?? $planRaw['slug']
                                    ?? $planRaw['title']
                                    ?? 'Unknown';
                            } else {
                                $planLabel = (string) $planRaw;
                            }
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                            @if($status['valid'] ?? false) bg-green-100 text-green-700
                            @else bg-red-100 text-red-700
                            @endif">
                            {{ ucfirst($planLabel) }}
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
                    <p class="mt-1 text-sm font-semibold text-gray-800">{{ ucfirst($planLabel) }}</p>
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
        </div>

        @if(empty(config('opterius.license_key')))
            {{-- No license — show helper text with registration links --}}
            <div class="px-6 py-6">
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6">
                    <h4 class="font-bold text-blue-900 mb-2">How to get your free license key</h4>
                    <ol class="list-decimal list-inside space-y-2 text-sm text-blue-800">
                        <li>Create a free account on <a href="https://opterius.com/register" target="_blank" rel="noopener noreferrer" class="font-semibold text-blue-600 hover:text-blue-800 underline">opterius.com/register</a></li>
                        <li>Verify your email address</li>
                        <li>Go to <strong>My Licenses</strong> in your dashboard</li>
                        <li>Click <strong>Add License</strong> — a free key will be generated</li>
                        <li>Copy the key and paste it below</li>
                    </ol>
                    <div class="mt-4 flex flex-wrap items-center gap-4">
                        <a href="https://opterius.com/register" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            Create Free Account
                        </a>
                        <a href="https://opterius.com/login" target="_blank" rel="noopener noreferrer"
                           class="text-sm font-semibold text-blue-700 hover:text-blue-900 transition">
                            Already registered? Log in →
                        </a>
                    </div>
                    <p class="mt-4 text-xs text-blue-700">
                        The free plan includes <strong>5 hosting accounts</strong> with all features enabled. No credit card required.
                    </p>
                </div>
            </div>
        @endif

        <form action="{{ route('admin.license.update') }}" method="POST" class="px-6 py-5 {{ empty(config('opterius.license_key')) ? 'pt-0' : '' }}">
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
