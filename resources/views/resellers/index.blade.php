<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Resellers</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm">
        <div class="flex justify-between items-center px-6 py-5 border-b border-gray-100">
            <div>
                <h3 class="text-base font-semibold text-gray-800">All Resellers</h3>
                <p class="text-sm text-gray-500 mt-1">Manage reseller accounts and their resource limits.</p>
            </div>
            <a href="{{ route('admin.resellers.create') }}" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                Add Reseller
            </a>
        </div>

        @if($resellers->isEmpty())
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                <h3 class="mt-4 text-base font-medium text-gray-700">No resellers yet</h3>
                <p class="mt-2 text-sm text-gray-500">Create your first reseller account.</p>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($resellers as $reseller)
                    <a href="{{ route('admin.resellers.show', $reseller) }}" class="flex items-center justify-between px-6 py-4 hover:bg-gray-100 transition">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">{{ $reseller->name }}</div>
                                <div class="text-xs text-gray-500">{{ $reseller->email }} &middot; {{ $reseller->accounts_count }} accounts &middot; Max: {{ $reseller->reseller_max_accounts ?: 'Unlimited' }}</div>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-admin-layout>
