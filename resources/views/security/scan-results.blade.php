<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.security.index', ['server_id' => $server->id]) }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">Scan Results — {{ $server->name }}</h2>
        </div>
    </x-slot>

    <!-- Summary -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex items-center space-x-4">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center
                @if(($result['count'] ?? 0) === 0) bg-green-100 @else bg-red-100 @endif">
                @if(($result['count'] ?? 0) === 0)
                    <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                @else
                    <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                @endif
            </div>
            <div>
                <div class="text-xl font-bold @if(($result['count'] ?? 0) === 0) text-green-700 @else text-red-700 @endif">
                    {{ $result['count'] ?? 0 }} threats found
                </div>
                <div class="text-sm text-gray-500">Scanned: {{ $result['path'] ?? '' }}</div>
            </div>
        </div>
    </div>

    @if(!empty($result['threats']))
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="hidden sm:grid grid-cols-12 px-6 py-2 text-xs font-medium text-gray-400 uppercase tracking-wide border-b border-gray-100 bg-gray-50">
                <div class="col-span-1">Severity</div>
                <div class="col-span-2">Pattern</div>
                <div class="col-span-1">Source</div>
                <div class="col-span-8">File</div>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($result['threats'] as $threat)
                    <div class="grid grid-cols-12 items-center px-6 py-2.5 text-sm">
                        <div class="col-span-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold
                                @if($threat['severity'] === 'critical') bg-red-100 text-red-700
                                @elseif($threat['severity'] === 'high') bg-orange-100 text-orange-700
                                @elseif($threat['severity'] === 'medium') bg-yellow-100 text-yellow-700
                                @else bg-gray-100 text-gray-700 @endif">
                                {{ ucfirst($threat['severity']) }}
                            </span>
                        </div>
                        <div class="col-span-2 text-gray-800 font-medium">{{ $threat['pattern'] }}</div>
                        <div class="col-span-1 text-xs text-gray-400">{{ $threat['source'] }}</div>
                        <div class="col-span-8 font-mono text-xs text-gray-600 truncate">{{ $threat['file'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-admin-layout>
