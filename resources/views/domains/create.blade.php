<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('domains.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">Add Domain</h2>
        </div>
    </x-slot>

    @if($accounts->isEmpty())
        <div class="max-w-2xl">
            <div class="bg-white rounded-xl shadow-sm p-6 text-center py-16">
                <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                <h3 class="mt-4 text-base font-medium text-gray-700">No accounts available</h3>
                <p class="mt-2 text-sm text-gray-500">You need to create an account before adding a domain.</p>
                <a href="{{ route('accounts.create') }}" class="mt-6 inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    Create Account
                </a>
            </div>
        </div>
    @else
        <form action="{{ route('domains.store') }}" method="POST"
              x-data="{
                  domain: '{{ old('domain') }}',
                  selectedAccountId: '{{ old('account_id', $accounts->first()->id) }}',
                  phpVersion: '{{ old('php_version', config('opterius.default_php_version')) }}'
              }">
            @csrf

            <div class="max-w-2xl space-y-6">

                {{-- Section 1: Account --}}
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600">1</span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-800">Account</h3>
                                <p class="text-sm text-gray-500">Select the hosting account for this domain.</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-5">
                        <label for="account_id" class="block text-sm font-medium text-gray-700 mb-1.5">Account</label>
                        <select name="account_id" id="account_id" x-model="selectedAccountId"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" @selected(old('account_id') == $account->id)>
                                    {{ $account->username }} ({{ $account->server->name }} &mdash; {{ $account->server->ip_address }})
                                </option>
                            @endforeach
                        </select>
                        @error('account_id')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Section 2: Domain --}}
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600">2</span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-800">Domain Name</h3>
                                <p class="text-sm text-gray-500">Enter the domain you want to host.</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-5">
                        <label for="domain" class="block text-sm font-medium text-gray-700 mb-1.5">Domain</label>
                        <input type="text" name="domain" id="domain" x-model="domain"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="e.g. example.com">
                        <p class="mt-1.5 text-xs text-gray-400">Enter the full domain name without http:// or www.</p>
                        @error('domain')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Section 3: PHP Version --}}
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600">3</span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-800">PHP Version</h3>
                                <p class="text-sm text-gray-500">Select the PHP version for this domain.</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-5">
                        <div class="flex flex-wrap gap-3">
                            @foreach(config('opterius.php_versions') as $ver)
                                <label class="relative">
                                    <input type="radio" name="php_version" value="{{ $ver }}" class="peer sr-only"
                                        x-model="phpVersion"
                                        @checked(old('php_version', config('opterius.default_php_version')) === $ver)>
                                    <div class="px-4 py-2.5 border border-gray-200 rounded-lg cursor-pointer text-sm font-medium
                                        peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-700
                                        hover:bg-gray-50 transition">
                                        PHP {{ $ver }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('php_version')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Summary --}}
                <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-5">
                    <h4 class="text-sm font-semibold text-indigo-800 mb-3">Summary</h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm text-indigo-700">
                        <div>
                            <span class="text-indigo-400 block text-xs mb-0.5">Domain</span>
                            <span class="font-medium" x-text="domain || '—'">—</span>
                        </div>
                        <div>
                            <span class="text-indigo-400 block text-xs mb-0.5">PHP</span>
                            <span class="font-medium" x-text="'PHP ' + phpVersion">—</span>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center space-x-3">
                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        Add Domain
                    </button>
                    <a href="{{ route('domains.index') }}" class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </a>
                </div>

            </div>
        </form>
    @endif
</x-app-layout>
