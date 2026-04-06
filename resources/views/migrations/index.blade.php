<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">{{ __('migrations.cpanel_migrations') }}</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <p class="text-sm text-gray-500">{{ __('migrations.import_from_cpanel') }}</p>
        <a href="{{ route('admin.migrations.create') }}" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
            {{ __('migrations.import_backup') }}
        </a>
    </div>

    @if($migrations->isEmpty())
        <div class="bg-white rounded-xl shadow-sm px-6 py-16 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
            <h3 class="mt-4 text-base font-medium text-gray-700">{{ __('migrations.no_migrations_yet') }}</h3>
            <p class="mt-2 text-sm text-gray-500">{{ __('migrations.import_first_cpanel_backup') }}</p>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('migrations.domain') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('migrations.username') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('migrations.server') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('common.status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('migrations.date') }}</th>
                        <th class="px-6 py-3 w-24"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($migrations as $m)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $m->main_domain ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ $m->target_username ?? $m->original_username ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $m->server->name }}</td>
                            <td class="px-6 py-4">
                                @php $colors = ['completed' => 'green', 'running' => 'blue', 'parsing' => 'blue', 'previewing' => 'amber', 'partial' => 'amber', 'failed' => 'red', 'pending' => 'gray']; $c = $colors[$m->status] ?? 'gray'; @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-700">
                                    @if($m->status === 'running')
                                        <svg class="w-3 h-3 mr-1 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    @endif
                                    {{ ucfirst($m->status) }}
                                    @if($m->status === 'running') ({{ $m->progress }}%) @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $m->created_at->diffForHumans() }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-3">
                                    @if($m->status === 'previewing')
                                        <a href="{{ route('admin.migrations.preview', $m) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">{{ __('migrations.configure') }}</a>
                                    @elseif($m->status === 'running')
                                        <a href="{{ route('admin.migrations.show', $m) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">{{ __('migrations.view') }}</a>
                                    @else
                                        <a href="{{ route('admin.migrations.show', $m) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">{{ __('migrations.view') }}</a>
                                    @endif

                                    @if(!in_array($m->status, ['running', 'parsing']))
                                        <x-delete-modal
                                            :action="route('admin.migrations.destroy', $m)"
                                            :title="__('migrations.delete_migration_record')"
                                            :message="__('migrations.delete_record_confirmation', ['domain' => $m->main_domain ?? 'this account'])"
                                            :confirm-password="false">
                                            <x-slot name="trigger">
                                                <button type="button" class="text-gray-400 hover:text-red-600 transition" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </x-slot>
                                        </x-delete-modal>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-admin-layout>
