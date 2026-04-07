<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Email Forwarders</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <!-- Domain Selector -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5">
            <form method="GET" action="{{ route('user.forwarders.index') }}" class="flex items-end gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Domain</label>
                    <select name="domain_id" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($domains as $domain)
                            <option value="{{ $domain->id }}" @selected($selectedDomain && $selectedDomain->id === $domain->id)>{{ $domain->domain }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">Manage</button>
            </form>
        </div>
    </div>

    @if($selectedDomain)
        <!-- Create Forwarder -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Create Forwarder</h3>
                <p class="text-sm text-gray-500 mt-1">Forward emails to another address. Use @ for catch-all.</p>
            </div>
            <form action="{{ route('user.forwarders.store') }}" method="POST" class="px-6 py-5">
                @csrf
                <input type="hidden" name="domain_id" value="{{ $selectedDomain->id }}">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                    <div class="sm:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Source</label>
                        <div class="flex">
                            <input type="text" name="source" placeholder="info or @ for catch-all"
                                class="flex-1 rounded-l-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="inline-flex items-center px-3 rounded-r-lg border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-xs font-mono">{{ '@' . $selectedDomain->domain }}</span>
                        </div>
                    </div>
                    <div class="sm:col-span-1 flex items-end justify-center pb-2.5">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </div>
                    <div class="sm:col-span-5">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Forward To</label>
                        <input type="email" name="destination" placeholder="user@gmail.com"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-2 flex items-end">
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                            Create
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Forwarders List -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Active Forwarders</h3>
            </div>
            @if(empty($forwarders))
                <div class="px-6 py-12 text-center text-sm text-gray-400">No forwarders for this domain.</div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($forwarders as $fwd)
                        <div class="flex items-center justify-between px-6 py-4">
                            <div class="flex items-center space-x-3 text-sm">
                                <span class="font-mono font-semibold text-gray-800">{{ $fwd['source'] }}</span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                <span class="font-mono text-indigo-600">{{ $fwd['destination'] }}</span>
                            </div>
                            <form action="{{ route('user.forwarders.destroy') }}" method="POST" onsubmit="return confirm('Delete this forwarder?')">
                                @csrf
                                <input type="hidden" name="domain_id" value="{{ $selectedDomain->id }}">
                                <input type="hidden" name="source" value="{{ $fwd['source'] }}">
                                <button type="submit" class="text-gray-400 hover:text-red-600 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</x-user-layout>
