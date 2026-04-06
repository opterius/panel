<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.resellers.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">{{ $reseller->name }}</h2>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">Reseller</span>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Resource Usage -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        @foreach($usage as $resource => $data)
            @php
                $pct = $data['limit'] > 0 ? min(100, round($data['used'] / $data['limit'] * 100)) : 0;
                $color = $pct > 90 ? 'red' : ($pct > 70 ? 'yellow' : 'green');
            @endphp
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-xs font-medium text-gray-400 uppercase">{{ ucfirst($resource) }}</div>
                <div class="mt-1 text-2xl font-bold text-gray-900">{{ $data['used'] }}</div>
                <div class="text-xs text-gray-500">/ {{ $data['limit'] ?: 'Unlimited' }}</div>
                @if($data['limit'] > 0)
                    <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                        <div class="bg-{{ $color }}-500 h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                    </div>
                @endif
            </div>
        @endforeach

        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-xs font-medium text-gray-400 uppercase">Disk</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">—</div>
            <div class="text-xs text-gray-500">/ {{ $reseller->reseller_max_disk ? ($reseller->reseller_max_disk >= 1024 ? round($reseller->reseller_max_disk / 1024, 1) . ' GB' : $reseller->reseller_max_disk . ' MB') : 'Unlimited' }}</div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-xs font-medium text-gray-400 uppercase">Bandwidth</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">—</div>
            <div class="text-xs text-gray-500">/ {{ $reseller->reseller_max_bandwidth ? ($reseller->reseller_max_bandwidth >= 1048576 ? round($reseller->reseller_max_bandwidth / 1048576, 1) . ' TB' : round($reseller->reseller_max_bandwidth / 1024, 1) . ' GB') : 'Unlimited' }}</div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-wrap gap-3 mb-6">
        <a href="{{ route('admin.resellers.edit', $reseller) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-600 bg-white border border-indigo-300 rounded-lg hover:bg-indigo-50 transition">
            Edit Limits
        </a>
        <form action="{{ route('admin.login-as', $reseller) }}" method="POST">
            @csrf
            <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-amber-600 bg-white border border-amber-300 rounded-lg hover:bg-amber-50 transition">
                Login as Reseller
            </button>
        </form>
    </div>

    <!-- Reseller Details -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
        <h3 class="text-base font-semibold text-gray-800 mb-5">Reseller Details</h3>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase">Name</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $reseller->name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase">Email</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $reseller->email }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase">Created</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $reseller->created_at->format('M d, Y') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-400 uppercase">Accounts</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $reseller->accounts_count }}</dd>
            </div>
        </dl>
    </div>

    <!-- Reseller's Accounts -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">Accounts</h3>
        </div>
        @if($accounts->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-gray-400">
                No accounts created by this reseller yet.
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($accounts as $account)
                    <a href="{{ route('admin.accounts.show', $account) }}" class="flex items-center justify-between px-6 py-4 hover:bg-gray-100 transition">
                        <div>
                            <div class="text-sm font-semibold text-gray-800">{{ $account->username }}</div>
                            <div class="text-xs text-gray-500">{{ $account->server->name }} &middot; {{ $account->domains->count() }} domains</div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Danger Zone -->
    <div class="mt-8 bg-white rounded-xl shadow-sm p-6 border border-red-100">
        <h3 class="text-base font-semibold text-red-600 mb-2">Danger Zone</h3>
        <p class="text-sm text-gray-500 mb-4">Deleting this reseller will remove their account. Their client accounts will remain but become unassigned.</p>
        <x-delete-modal
            :action="route('admin.resellers.destroy', $reseller)"
            title="Delete Reseller"
            message="This will delete the reseller account for {{ $reseller->name }} ({{ $reseller->email }})."
            :confirm-password="true">
            <x-slot name="trigger">
                <button type="button" class="inline-flex items-center px-4 py-2.5 bg-white text-red-600 text-sm font-medium border border-red-300 rounded-lg hover:bg-red-50 transition">
                    Delete Reseller
                </button>
            </x-slot>
        </x-delete-modal>
    </div>
</x-admin-layout>
