<x-user-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('user.postgres.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">Create PostgreSQL Database</h2>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('user.postgres.store') }}" method="POST">
        @csrf
        <div class="max-w-xl space-y-6">

            <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Account</label>
                    <select name="account_id"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" @selected(old('account_id') == $account->id)>
                                {{ $account->username }} ({{ $account->server->name }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Database Name</label>
                    <input type="text" name="db_name" value="{{ old('db_name') }}"
                        placeholder="mydb"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1.5 text-xs text-gray-400">Letters, numbers, underscores only. Your account prefix will be added automatically.</p>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
                <h4 class="text-sm font-semibold text-blue-800 mb-2">What gets created</h4>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>A PostgreSQL database with UTF-8 encoding</li>
                    <li>A dedicated database user with full access</li>
                    <li>The password is shown once — save it immediately</li>
                </ul>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                    class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Create Database
                </button>
                <a href="{{ route('user.postgres.index') }}"
                   class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</x-user-layout>
