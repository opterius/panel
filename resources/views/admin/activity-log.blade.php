<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">{{ __('activity.activity_log') }}</h2>
    </x-slot>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <form method="GET" action="{{ route('admin.activity-log.index') }}" class="px-6 py-4">
            <div class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-48">
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('common.search') }}</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('activity.search_descriptions') }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="w-40">
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('common.type') }}</label>
                    <select name="action"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach($actionTypes as $type)
                            <option value="{{ $type }}" @selected(request('action') === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    {{ __('common.filter') }}
                </button>
                @if(request()->hasAny(['search', 'action', 'user_id']))
                    <a href="{{ route('admin.activity-log.index') }}" class="text-sm text-gray-500 hover:text-gray-700 transition">{{ __('common.clear') }}</a>
                @endif
                <a href="{{ route('admin.activity-log.export', request()->all()) }}" class="inline-flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    CSV
                </a>
            </div>
        </form>
    </div>

    <!-- Log Entries -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        @if($logs->isEmpty())
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                <h3 class="mt-4 text-base font-medium text-gray-700">{{ __('activity.no_activity_yet') }}</h3>
                <p class="mt-2 text-sm text-gray-500">{{ __('activity.actions_appear_here') }}</p>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($logs as $log)
                    <div class="flex items-start space-x-4 px-6 py-4">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 {{ $log->actionColor() }}">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $log->actionIcon() }}" /></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-gray-800">
                                <span class="font-medium">{{ $log->user?->name ?? 'System' }}</span>
                                <span class="text-gray-500">{{ $log->description }}</span>
                            </div>
                            <div class="mt-1 flex items-center space-x-3 text-xs text-gray-400">
                                <span>{{ $log->created_at->diffForHumans() }}</span>
                                @if($log->entity_name)
                                    <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded text-gray-600">{{ $log->entity_name }}</span>
                                @endif
                                @if($log->ip_address)
                                    <span class="font-mono">{{ $log->ip_address }}</span>
                                @endif
                                <span class="text-gray-300">{{ $log->action }}</span>
                            </div>
                        </div>
                        <div class="text-xs text-gray-400 shrink-0">
                            {{ $log->created_at->format('M d, H:i') }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="px-6 py-4 border-t border-gray-100">
                {{ $logs->withQueryString()->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
