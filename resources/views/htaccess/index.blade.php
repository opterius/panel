<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">.htaccess Support</h2>
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

    <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-xl p-5 mb-6">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <div class="text-sm leading-relaxed">
                <p class="font-medium mb-1">When should I enable .htaccess?</p>
                <ul class="list-disc ml-5 space-y-0.5 text-blue-700">
                    <li><strong>Keep it off (default)</strong> — Nginx serves PHP directly. Faster, uses less memory, recommended for custom/modern apps.</li>
                    <li><strong>Turn it on</strong> — Apache handles PHP so <code class="bg-blue-100 px-1 rounded">.htaccess</code> files are read. Enable for WordPress, Joomla, Drupal, legacy apps that rely on <code class="bg-blue-100 px-1 rounded">mod_rewrite</code>.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="divide-y divide-gray-100">
            @forelse($domains as $domain)
                <div class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                            {{ $domain->htaccess_enabled ? 'bg-orange-100' : 'bg-gray-100' }}">
                            <svg class="w-4 h-4 {{ $domain->htaccess_enabled ? 'text-orange-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" /></svg>
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-800 truncate">{{ $domain->domain }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                @if($domain->htaccess_enabled)
                                    Apache backend active — <code class="bg-gray-100 px-1 rounded">.htaccess</code> / mod_rewrite supported
                                @else
                                    Nginx direct — faster, no <code class="bg-gray-100 px-1 rounded">.htaccess</code> processing
                                @endif
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('user.domains.toggle-htaccess', $domain) }}"
                          x-data="{ loading: false }" @submit="loading = true"
                          class="flex-shrink-0">
                        @csrf
                        <button type="submit" :disabled="loading"
                            class="relative inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium transition disabled:opacity-50
                                {{ $domain->htaccess_enabled
                                    ? 'bg-orange-100 text-orange-700 hover:bg-orange-200'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            <span class="w-7 h-4 rounded-full transition-colors flex items-center px-0.5
                                {{ $domain->htaccess_enabled ? 'bg-orange-500' : 'bg-gray-300' }}">
                                <span class="w-3 h-3 bg-white rounded-full shadow transition-transform
                                    {{ $domain->htaccess_enabled ? 'translate-x-3' : 'translate-x-0' }}"></span>
                            </span>
                            <span x-text="loading ? 'Applying…' : '{{ $domain->htaccess_enabled ? 'Enabled' : 'Disabled' }}'">
                                {{ $domain->htaccess_enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </button>
                    </form>
                </div>
            @empty
                <div class="px-6 py-10 text-center text-sm text-gray-500">
                    No domains yet.
                </div>
            @endforelse
        </div>
    </div>
</x-user-layout>
