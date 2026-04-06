<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">URL Redirects</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <div class="mb-6">
        <p class="text-sm text-gray-500">Redirect URLs to different destinations. Useful for moved pages or shortened links.</p>
    </div>

    @foreach($domains as $domain)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-5">
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="text-sm font-semibold text-gray-800">{{ $domain->domain }}</div>
                <div class="text-xs text-gray-500">{{ $domain->redirects->count() }} redirect(s)</div>
            </div>

            {{-- Existing Redirects --}}
            @if($domain->redirects->isNotEmpty())
                <div class="divide-y divide-gray-50">
                    @foreach($domain->redirects as $redirect)
                        <div class="px-6 py-3 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-mono font-medium
                                    {{ $redirect->type === '301' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $redirect->type }}
                                </span>
                                <span class="text-sm font-mono text-gray-700">{{ $redirect->source_path }}</span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                                <span class="text-sm text-gray-500 truncate max-w-xs">{{ $redirect->destination_url }}</span>
                            </div>
                            <form action="{{ route('user.redirects.destroy', $redirect) }}" method="POST"
                                  onsubmit="return confirm('Remove this redirect?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Add Redirect Form --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                <form action="{{ route('user.redirects.store') }}" method="POST" class="flex items-end gap-3">
                    @csrf
                    <input type="hidden" name="domain_id" value="{{ $domain->id }}">
                    <div class="w-40">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Source Path</label>
                        <input type="text" name="source_path"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="/old-page">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Destination URL</label>
                        <input type="text" name="destination_url"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="https://example.com/new-page">
                    </div>
                    <div class="w-24">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                        <select name="type" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="301">301</option>
                            <option value="302">302</option>
                        </select>
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        Add
                    </button>
                </form>
            </div>
        </div>
    @endforeach
</x-user-layout>
