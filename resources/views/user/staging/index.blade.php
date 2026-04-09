<x-user-layout>
    <x-slot name="title">Staging Environments</x-slot>

    <div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

        @if (session('success'))
            <div class="mb-6 rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900">Staging Environments</h1>
            <p class="text-slate-500 mt-1">
                Spin up an instant copy of any site at <span class="font-mono text-slate-700">staging.your-domain.com</span> for testing changes safely before pushing to production.
            </p>
        </div>

        @if ($domains->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-500">
                You have no domains yet.
            </div>
        @else
            <div class="space-y-4">
                @foreach ($domains as $domain)
                    @php $clone = $domain->stagingClones->first(); @endphp
                    <div class="bg-white rounded-2xl border border-slate-200 p-5">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-slate-900 text-lg">{{ $domain->domain }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    {{ $domain->account->username }} · PHP {{ $domain->php_version }}
                                </div>
                            </div>

                            @if ($clone)
                                <div class="flex items-center gap-3">
                                    <a href="https://{{ $clone->domain }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-sm font-semibold px-4 py-2 rounded-lg transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        {{ $clone->domain }}
                                    </a>
                                    <form method="POST" action="{{ route('user.staging.destroy', $clone) }}"
                                          onsubmit="return confirm('Delete the staging environment {{ $clone->domain }}? This removes the subdomain, files, and database.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-700 font-semibold">Delete</button>
                                    </form>
                                </div>
                            @else
                                <form method="POST" action="{{ route('user.staging.store', $domain) }}"
                                      x-data="{ creating: false }" @submit="creating = true">
                                    @csrf
                                    <button type="submit" :disabled="creating"
                                            class="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 disabled:bg-orange-300 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        <span x-show="!creating">Create Staging</span>
                                        <span x-show="creating">Cloning…</span>
                                    </button>
                                </form>
                            @endif
                        </div>

                        @if ($clone)
                            <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-500 grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <div class="text-xs uppercase font-semibold text-slate-400 mb-0.5">Created</div>
                                    <div class="text-slate-700">{{ $clone->created_at->diffForHumans() }}</div>
                                </div>
                                <div>
                                    <div class="text-xs uppercase font-semibold text-slate-400 mb-0.5">Document root</div>
                                    <div class="text-slate-700 font-mono truncate">{{ $clone->document_root }}</div>
                                </div>
                                <div>
                                    <div class="text-xs uppercase font-semibold text-slate-400 mb-0.5">Status</div>
                                    <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm text-blue-800">
            <strong>How it works:</strong> Creating a staging environment clones the production files via rsync, creates a separate database with the production data, and patches <span class="font-mono">wp-config.php</span> / <span class="font-mono">.env</span> with the new credentials. WordPress sites also get a database search-replace if <span class="font-mono">wp-cli</span> is installed.
        </div>

    </div>
</x-user-layout>
