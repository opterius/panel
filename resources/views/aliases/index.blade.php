<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Domain Aliases</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <div class="mb-6">
        <p class="text-sm text-gray-500">Domain aliases point additional domains to the same website content.</p>
    </div>

    @foreach($domains as $domain)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-5">
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="text-sm font-semibold text-gray-800">{{ $domain->domain }}</div>
                <div class="text-xs text-gray-500">{{ $domain->aliases->count() }} alias(es)</div>
            </div>

            {{-- Existing Aliases --}}
            @if($domain->aliases->isNotEmpty())
                <div class="divide-y divide-gray-50">
                    @foreach($domain->aliases as $alias)
                        <div class="px-6 py-3 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                <span class="text-sm text-gray-700">{{ $alias->alias_domain }}</span>
                                <span class="text-xs text-gray-400">→ {{ $domain->domain }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $alias->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($alias->status) }}
                                </span>
                            </div>
                            <form action="{{ route('user.aliases.destroy', $alias) }}" method="POST"
                                  onsubmit="return confirm('Remove this alias?')">
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

            {{-- Add Alias Form --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                <form action="{{ route('user.aliases.store') }}" method="POST" class="flex items-end gap-3">
                    @csrf
                    <input type="hidden" name="domain_id" value="{{ $domain->id }}">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Alias Domain</label>
                        <input type="text" name="alias_domain"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="e.g. example.net">
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        Add Alias
                    </button>
                </form>
            </div>
        </div>
    @endforeach

    @error('alias_domain')
        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
    @enderror
</x-user-layout>
