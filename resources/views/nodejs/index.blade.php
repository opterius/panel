<x-user-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Node.js Apps</h2>
            <a href="{{ route('user.nodejs.create') }}"
               class="inline-flex items-center px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Deploy App
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    {{-- Runtime info --}}
    @if($nodeInfo['node'] ?? null)
        <div class="flex flex-wrap gap-3 mb-6">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 border border-green-200 rounded-lg text-xs font-medium text-green-700">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1.85c-.27 0-.55.07-.78.2L3.78 6.35C3.3 6.6 3 7.1 3 7.65v8.69c0 .56.3 1.06.78 1.31l7.44 4.3c.23.13.5.2.78.2s.55-.07.78-.2l7.44-4.3c.48-.25.78-.75.78-1.31V7.65c0-.55-.3-1.05-.78-1.3l-7.44-4.3c-.23-.13-.5-.2-.78-.2zm0 2.06l6.66 3.85v7.68L12 19.29l-6.66-3.85V7.76L12 3.91z"/></svg>
                Node {{ $nodeInfo['node'] }}
            </span>
            @if($nodeInfo['npm'] ?? null)
                <span class="inline-flex items-center px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-600">
                    npm {{ $nodeInfo['npm'] }}
                </span>
            @endif
            @if(($nodeInfo['pm2'] ?? 'not installed') !== 'not installed')
                <span class="inline-flex items-center px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-lg text-xs font-medium text-blue-600">
                    PM2 {{ $nodeInfo['pm2'] }}
                </span>
            @endif
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">Running Applications</h3>
            <p class="text-sm text-gray-500 mt-1">Managed by PM2. Apps restart automatically on server reboot.</p>
        </div>

        @if($apps->isEmpty())
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 mb-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1.85c-.27 0-.55.07-.78.2L3.78 6.35C3.3 6.6 3 7.1 3 7.65v8.69c0 .56.3 1.06.78 1.31l7.44 4.3c.23.13.5.2.78.2s.55-.07.78-.2l7.44-4.3c.48-.25.78-.75.78-1.31V7.65c0-.55-.3-1.05-.78-1.3l-7.44-4.3c-.23-.13-.5-.2-.78-.2zm0 2.06l6.66 3.85v7.68L12 19.29l-6.66-3.85V7.76L12 3.91z"/></svg>
                <h3 class="text-base font-medium text-gray-700">No Node.js apps yet</h3>
                <p class="mt-2 text-sm text-gray-500">Deploy your first Node.js application to get started.</p>
                <a href="{{ route('user.nodejs.create') }}"
                   class="mt-6 inline-flex items-center px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                    Deploy App
                </a>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($apps as $app)
                    <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                @if($app->status === 'running') bg-green-100 @elseif($app->status === 'error') bg-red-100 @else bg-gray-100 @endif">
                                <svg class="w-5 h-5 @if($app->status === 'running') text-green-600 @elseif($app->status === 'error') text-red-600 @else text-gray-400 @endif"
                                     viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 1.85c-.27 0-.55.07-.78.2L3.78 6.35C3.3 6.6 3 7.1 3 7.65v8.69c0 .56.3 1.06.78 1.31l7.44 4.3c.23.13.5.2.78.2s.55-.07.78-.2l7.44-4.3c.48-.25.78-.75.78-1.31V7.65c0-.55-.3-1.05-.78-1.3l-7.44-4.3c-.23-.13-.5-.2-.78-.2zm0 2.06l6.66 3.85v7.68L12 19.29l-6.66-3.85V7.76L12 3.91z"/>
                                </svg>
                            </div>
                            <div>
                                <a href="{{ route('user.nodejs.show', $app) }}"
                                   class="text-sm font-semibold text-green-700 hover:text-green-900 transition">{{ $app->name }}</a>
                                <div class="text-xs text-gray-400 mt-0.5">
                                    Port {{ $app->port }}
                                    @if($app->domain) &middot; {{ $app->domain->domain }} @endif
                                    &middot; <span class="font-mono">{{ $app->startup_command }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                @if($app->status === 'running') bg-green-100 text-green-700
                                @elseif($app->status === 'error') bg-red-100 text-red-700
                                @else bg-gray-100 text-gray-600 @endif">
                                {{ ucfirst($app->status) }}
                            </span>
                            <a href="{{ route('user.nodejs.show', $app) }}"
                               class="text-xs text-gray-500 hover:text-gray-700 transition">Manage →</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-user-layout>
