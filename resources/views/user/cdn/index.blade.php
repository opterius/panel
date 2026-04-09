<x-user-layout>
    <x-slot name="title">Content Delivery Network</x-slot>

    <div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

        @if (session('success'))
            <div class="mb-6 rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900">Content Delivery Network</h1>
            <p class="text-slate-500 mt-1">
                Speed up your sites by serving images, CSS, JavaScript, and other static assets from BunnyCDN's global edge network.
                URLs are rewritten automatically — no plugin or code change needed.
            </p>
        </div>

        @if (! $configured)
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 mb-6">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div>
                        <h3 class="font-bold text-amber-900">CDN integration not configured</h3>
                        <p class="text-sm text-amber-800 mt-1">The administrator hasn't set up a BunnyCDN API key yet. Once that's done, you'll be able to enable CDN per domain from this page.</p>
                    </div>
                </div>
            </div>
        @endif

        @if ($domains->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-500">
                You have no domains yet.
            </div>
        @else
            <div class="space-y-4">
                @foreach ($domains as $domain)
                    @php $zone = $domain->cdnZone; @endphp
                    <div class="bg-white rounded-2xl border border-slate-200 p-5">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $zone && $zone->enabled ? 'bg-orange-100' : 'bg-slate-100' }}">
                                        <svg class="w-5 h-5 {{ $zone && $zone->enabled ? 'text-orange-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-lg">{{ $domain->domain }}</div>
                                        <div class="text-xs text-slate-500 mt-0.5">
                                            {{ $domain->account->username }}
                                            @if ($zone && $zone->enabled)
                                                · CDN active via <span class="font-mono text-orange-600">{{ $zone->cdn_hostname }}</span>
                                            @else
                                                · CDN disabled
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                @if ($zone && $zone->enabled)
                                    <form method="POST" action="{{ route('user.cdn.purge', $domain) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold px-4 py-2 rounded-lg transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            Purge
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('user.cdn.disable', $domain) }}"
                                          onsubmit="return confirm('Disable CDN for {{ $domain->domain }}? This removes the BunnyCDN pull zone.')">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-700 text-sm font-semibold px-4 py-2 rounded-lg transition">
                                            Disable
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('user.cdn.enable', $domain) }}"
                                          x-data="{ creating: false }" @submit="creating = true">
                                        @csrf
                                        <button type="submit" :disabled="creating || ! @json($configured)"
                                                class="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 disabled:bg-slate-300 disabled:cursor-not-allowed text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            <span x-show="!creating">Enable CDN</span>
                                            <span x-show="creating">Creating zone…</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        @if ($zone && $zone->enabled)
                            {{-- Rewrite paths editor --}}
                            <div class="mt-5 pt-5 border-t border-slate-100">
                                <form method="POST" action="{{ route('user.cdn.paths', $domain) }}">
                                    @csrf
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Rewrite paths</label>
                                    <textarea name="paths" rows="3"
                                              class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-orange-500"
                                              placeholder="/wp-content/&#10;/wp-includes/">{{ implode("\n", $zone->rewrite_paths ?? []) }}</textarea>
                                    <div class="mt-2 flex items-center justify-between">
                                        <p class="text-xs text-slate-500">URLs starting with these paths will be rewritten to point to your CDN. One per line, must look like <code class="bg-slate-100 px-1 rounded">/folder/</code>.</p>
                                        <button type="submit" class="text-sm font-semibold text-orange-600 hover:text-orange-700">Save paths</button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm text-blue-800">
            <strong>How it works:</strong> When you enable CDN, the panel creates a BunnyCDN Pull Zone for your domain and configures Nginx to rewrite asset URLs in your HTML responses on the fly. Your site files are not modified — disabling CDN reverts to direct serving with one click.
        </div>

    </div>
</x-user-layout>
