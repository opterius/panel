<x-user-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('user.filemanager.index', ['account_id' => $account->id, 'path' => dirname($path)]) }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">Edit File</h2>
            <span class="text-sm text-gray-500 font-mono">{{ $path }}</span>
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

    <form action="{{ route('user.filemanager.write') }}" method="POST">
        @csrf
        <input type="hidden" name="account_id" value="{{ $account->id }}">
        <input type="hidden" name="path" value="{{ $path }}">

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                <span class="text-sm font-medium text-gray-700">{{ basename($path) }}</span>
                <div class="flex items-center space-x-3">
                    <span class="text-xs text-gray-400">{{ number_format(strlen($content)) }} bytes</span>
                    <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Save
                    </button>
                </div>
            </div>

            <textarea name="content" rows="30"
                class="w-full border-0 font-mono text-sm leading-relaxed p-6 focus:ring-0 resize-y"
                spellcheck="false">{{ $content }}</textarea>
        </div>
    </form>
</x-user-layout>
