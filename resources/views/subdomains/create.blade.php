<x-user-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('user.domains.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">Create Subdomain for {{ $domain->domain }}</h2>
        </div>
    </x-slot>

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('user.subdomains.store', $domain) }}" method="POST"
          x-data="{
              subdomain: '{{ old('subdomain') }}',
              customPath: false,
              documentRoot: ''
          }">
        @csrf

        <div class="max-w-2xl space-y-6">

            {{-- Subdomain Name --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-indigo-600">1</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Subdomain Name</h3>
                            <p class="text-sm text-gray-500">Enter the subdomain prefix.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <div class="flex items-center gap-2">
                        <input type="text" name="subdomain" x-model="subdomain"
                            class="flex-1 rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="blog">
                        <span class="text-sm text-gray-500 font-medium">.{{ $domain->domain }}</span>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">
                        Preview: <span class="font-mono text-gray-600" x-text="(subdomain || 'sub') + '.{{ $domain->domain }}'"></span>
                    </p>
                    @error('subdomain')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Document Root --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-indigo-600">2</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Document Root</h3>
                            <p class="text-sm text-gray-500">Where the subdomain's files will be stored.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center space-x-2 text-sm">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                            <span class="font-mono text-gray-600" x-text="'{{ $domain->document_root }}/' + (subdomain || 'sub')">{{ $domain->document_root }}/sub</span>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Default: inside the parent domain's public_html folder.</p>
                    </div>

                    <label class="flex items-center space-x-3">
                        <input type="checkbox" x-model="customPath"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">Use a custom document root</span>
                    </label>

                    <div x-show="customPath" x-collapse>
                        <input type="text" name="document_root" x-model="documentRoot"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="{{ $domain->document_root }}/blog">
                        <p class="mt-1.5 text-xs text-gray-400">Must be within the account's home directory.</p>
                        @error('document_root')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Summary --}}
            <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-5">
                <h4 class="text-sm font-semibold text-indigo-800 mb-3">Summary</h4>
                <div class="grid grid-cols-2 gap-4 text-sm text-indigo-700">
                    <div>
                        <span class="text-indigo-400 block text-xs mb-0.5">Subdomain</span>
                        <span class="font-medium font-mono" x-text="(subdomain || 'sub') + '.{{ $domain->domain }}'"></span>
                    </div>
                    <div>
                        <span class="text-indigo-400 block text-xs mb-0.5">Document Root</span>
                        <span class="font-medium font-mono text-xs" x-text="customPath && documentRoot ? documentRoot : '{{ $domain->document_root }}/' + (subdomain || 'sub')"></span>
                    </div>
                    <div>
                        <span class="text-indigo-400 block text-xs mb-0.5">PHP</span>
                        <span class="font-medium">PHP {{ $domain->php_version }}</span>
                    </div>
                    <div>
                        <span class="text-indigo-400 block text-xs mb-0.5">Account</span>
                        <span class="font-medium">{{ $domain->account->username }}</span>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center space-x-3">
                <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    Create Subdomain
                </button>
                <a href="{{ route('user.domains.index') }}" class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</x-user-layout>
