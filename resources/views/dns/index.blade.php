<x-user-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('user.domains.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">DNS Zone: {{ $domain->domain }}</h2>
        </div>
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

    <!-- DNS Records Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">DNS Records</h3>
            <p class="text-sm text-gray-500 mt-1">Manage DNS records for {{ $domain->domain }}.</p>
        </div>

        @if(empty($records))
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                <p class="mt-3 text-sm text-gray-500">No DNS zone found. Create a zone first or check if PowerDNS is installed.</p>
            </div>
        @else
            <!-- Table header -->
            <div class="hidden sm:grid grid-cols-12 px-6 py-2 text-xs font-medium text-gray-400 uppercase tracking-wide border-b border-gray-100 bg-gray-50">
                <div class="col-span-1">Type</div>
                <div class="col-span-4">Name</div>
                <div class="col-span-4">Content</div>
                <div class="col-span-1">TTL</div>
                <div class="col-span-1">Priority</div>
                <div class="col-span-1 text-right">Action</div>
            </div>

            <div class="divide-y divide-gray-50">
                @foreach($records as $record)
                    <div class="grid grid-cols-12 items-center px-6 py-2.5 hover:bg-gray-100 transition text-sm">
                        <div class="col-span-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold
                                @switch($record['type'])
                                    @case('A') bg-blue-100 text-blue-700 @break
                                    @case('AAAA') bg-blue-100 text-blue-700 @break
                                    @case('CNAME') bg-purple-100 text-purple-700 @break
                                    @case('MX') bg-orange-100 text-orange-700 @break
                                    @case('TXT') bg-green-100 text-green-700 @break
                                    @case('NS') bg-indigo-100 text-indigo-700 @break
                                    @case('SOA') bg-gray-100 text-gray-700 @break
                                    @default bg-gray-100 text-gray-700
                                @endswitch">
                                {{ $record['type'] }}
                            </span>
                        </div>
                        <div class="col-span-4 font-mono text-xs text-gray-800 truncate">{{ $record['name'] }}</div>
                        <div class="col-span-4 font-mono text-xs text-gray-600 truncate">{{ $record['content'] }}</div>
                        <div class="col-span-1 text-xs text-gray-500">{{ $record['ttl'] }}</div>
                        <div class="col-span-1 text-xs text-gray-500">{{ $record['priority'] ?: '—' }}</div>
                        <div class="col-span-1 text-right">
                            @if($record['type'] !== 'SOA' && $record['type'] !== 'NS')
                                <div x-data="{ confirmDelete: false }" class="inline relative">
                                    <button type="button" @click="confirmDelete = true" class="text-gray-400 hover:text-red-600 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                    <template x-teleport="body">
                                        <div x-show="confirmDelete" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                                            <div x-show="confirmDelete" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="confirmDelete = false"></div>
                                            <div class="fixed inset-0 flex items-center justify-center p-4">
                                                <div x-show="confirmDelete" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.stop @keydown.escape.window="confirmDelete = false" class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
                                                    <div class="p-6 pb-0">
                                                        <div class="flex items-start space-x-4">
                                                            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                                                            </div>
                                                            <div>
                                                                <h3 class="text-lg font-semibold text-gray-900">Delete DNS Record</h3>
                                                                <p class="mt-1 text-sm text-gray-500">Are you sure you want to delete this {{ $record['type'] }} record?</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center justify-end space-x-3 px-6 py-5 mt-2">
                                                        <button type="button" @click="confirmDelete = false" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">Cancel</button>
                                                        <form action="{{ route('user.dns.delete-record', $domain) }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="record_id" value="{{ $record['id'] }}">
                                                            <button type="submit" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Add Record Form -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">Add Record</h3>
        </div>
        <form action="{{ route('user.dns.add-record', $domain) }}" method="POST" class="px-6 py-5"
              x-data="{ type: 'A' }">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-6 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
                    <select name="type" x-model="type"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="A">A</option>
                        <option value="AAAA">AAAA</option>
                        <option value="CNAME">CNAME</option>
                        <option value="MX">MX</option>
                        <option value="TXT">TXT</option>
                        <option value="SRV">SRV</option>
                        <option value="CAA">CAA</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Name</label>
                    <input type="text" name="name"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="{{ $domain->domain }}">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Content</label>
                    <input type="text" name="content"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500"
                        :placeholder="type === 'A' ? '1.2.3.4' : type === 'CNAME' ? 'target.example.com' : type === 'MX' ? 'mail.example.com' : type === 'TXT' ? 'v=spf1 ...' : ''">
                    @error('content')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-end space-x-3">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">TTL</label>
                        <input type="number" name="ttl" value="3600"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <div class="mt-4 flex items-center space-x-4">
                <div x-show="type === 'MX' || type === 'SRV'" class="w-24">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Priority</label>
                    <input type="number" name="priority" value="10"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="flex-1" x-show="type !== 'MX' && type !== 'SRV'">
                    <input type="hidden" name="priority" value="0">
                </div>
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    Add Record
                </button>
            </div>
        </form>
    </div>
</x-user-layout>
