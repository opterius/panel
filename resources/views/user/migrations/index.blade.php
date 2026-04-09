<x-user-layout>
    <x-slot name="title">cPanel Import</x-slot>

    <div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

        @if (session('success'))
            <div class="mb-6 rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif

        <div class="mb-8 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Import from cPanel</h1>
                <p class="text-slate-500 mt-1">
                    Upload a cPanel backup file and we'll restore your files, databases, email accounts, and more — directly into your existing hosting account.
                </p>
            </div>
            <a href="{{ route('user.migrations.create') }}" class="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                New Import
            </a>
        </div>

        @if ($migrations->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
                <svg class="w-14 h-14 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <p class="text-slate-600 font-semibold">No imports yet</p>
                <p class="text-sm text-slate-500 mt-1">Upload a cPanel backup to get started.</p>
                <a href="{{ route('user.migrations.create') }}" class="inline-flex items-center gap-2 mt-5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                    Upload your first backup
                </a>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-5 py-3">Source domain</th>
                            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-5 py-3">Status</th>
                            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-5 py-3">Started</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($migrations as $m)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3">
                                    <div class="font-semibold text-slate-900">{{ $m->main_domain ?? '—' }}</div>
                                    <div class="text-xs text-slate-500">{{ $m->original_username }}</div>
                                </td>
                                <td class="px-5 py-3">
                                    @php
                                        $statusColors = [
                                            'parsing'    => 'bg-blue-100 text-blue-700',
                                            'previewing' => 'bg-amber-100 text-amber-700',
                                            'pending'    => 'bg-slate-100 text-slate-700',
                                            'running'    => 'bg-blue-100 text-blue-700',
                                            'completed'  => 'bg-green-100 text-green-700',
                                            'partial'    => 'bg-amber-100 text-amber-700',
                                            'failed'     => 'bg-red-100 text-red-700',
                                        ];
                                        $cls = $statusColors[$m->status] ?? 'bg-slate-100 text-slate-700';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $cls }}">
                                        {{ ucfirst($m->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-slate-600">
                                    {{ $m->created_at->diffForHumans() }}
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('user.migrations.show', $m) }}" class="text-orange-600 hover:text-orange-700 font-semibold">View →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>
</x-user-layout>
