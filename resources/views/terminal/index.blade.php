<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Web Terminal</h2>
    </x-slot>

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <div class="mb-6">
        <p class="text-sm text-gray-500">Open an SSH terminal session in your browser. SSH access must be enabled for the account.</p>
    </div>

    @if($accounts->isEmpty())
        <div class="bg-white rounded-xl shadow-sm px-6 py-16 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            <h3 class="mt-4 text-base font-medium text-gray-700">No SSH-enabled accounts</h3>
            <p class="mt-2 text-sm text-gray-500">Enable SSH access for an account first.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($accounts as $account)
                <form action="{{ route('user.terminal.connect') }}" method="POST">
                    @csrf
                    <input type="hidden" name="account_id" value="{{ $account->id }}">
                    <button type="submit" class="w-full bg-white rounded-xl shadow-sm p-5 text-left hover:bg-gray-50 transition group">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-900 flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800 group-hover:text-indigo-700">{{ $account->username }}</div>
                                <div class="text-xs text-gray-500">{{ $account->server->name }} &middot; {{ $account->domains->first()?->domain }}</div>
                            </div>
                        </div>
                    </button>
                </form>
            @endforeach
        </div>
    @endif
</x-user-layout>
