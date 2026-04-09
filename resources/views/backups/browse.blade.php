<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.backups.index', ['server_id' => $backup->server_id]) }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">Browse Backup</h2>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

        @if (session('success'))
            <div class="mb-6 rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif
        @if (session('error') || $error)
            <div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') ?? $error }}</div>
        @endif

        {{-- Backup info --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-slate-900">{{ $backup->filename }}</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ $backup->username }} · {{ $backup->type }} · {{ number_format($backup->size_mb, 1) }} MB · created {{ $backup->created_at->diffForHumans() }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Breadcrumbs --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-4">
            <div class="flex items-center gap-1 text-sm text-slate-600">
                <a href="{{ route('admin.backups.browse', $backup) }}" class="hover:text-orange-600 font-semibold">root</a>
                @foreach ($crumbs as $crumb)
                    <span class="text-slate-400">/</span>
                    <a href="{{ route('admin.backups.browse', ['backup' => $backup, 'path' => $crumb['path']]) }}" class="hover:text-orange-600">
                        {{ $crumb['name'] }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Restore form wraps the file list --}}
        <form method="POST" action="{{ route('admin.backups.restore-files', $backup) }}">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-4">

                {{-- Restore controls --}}
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50 flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[300px]">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Restore destination (document root)</label>
                        <input type="text" name="document_root" required
                               value="/home/{{ $backup->username }}/public_html"
                               class="w-full rounded-lg border-slate-300 text-sm font-mono focus:border-orange-500 focus:ring-orange-500">
                        <p class="text-xs text-slate-500 mt-1">Files will be extracted with their relative paths under this directory.</p>
                    </div>
                    <div>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="overwrite" value="1"
                                   class="rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                            <span class="text-sm font-semibold text-slate-700">Overwrite existing files</span>
                        </label>
                    </div>
                    <div>
                        <button type="submit"
                                class="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Restore Selected
                        </button>
                    </div>
                </div>

                {{-- File list --}}
                @if (empty($entries))
                    <div class="p-12 text-center text-slate-500 text-sm">
                        @if ($error)
                            Could not read backup contents.
                        @else
                            This directory is empty.
                        @endif
                    </div>
                @else
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200 text-xs">
                            <tr>
                                <th class="w-12 px-4 py-2.5">
                                    <input type="checkbox" id="select-all"
                                           class="rounded border-slate-300 text-orange-500 focus:ring-orange-500"
                                           onclick="document.querySelectorAll('input[name=&quot;files[]&quot;]').forEach(c => c.checked = this.checked)">
                                </th>
                                <th class="text-left text-slate-500 uppercase font-semibold tracking-wide px-4 py-2.5">Name</th>
                                <th class="text-right text-slate-500 uppercase font-semibold tracking-wide px-4 py-2.5">Size</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($entries as $entry)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2">
                                        @unless ($entry['is_dir'])
                                            <input type="checkbox" name="files[]" value="{{ $entry['path'] }}"
                                                   class="rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                                        @endunless
                                    </td>
                                    <td class="px-4 py-2">
                                        @if ($entry['is_dir'])
                                            <a href="{{ route('admin.backups.browse', ['backup' => $backup, 'path' => $entry['path'] . '/']) }}"
                                               class="inline-flex items-center gap-2 text-slate-900 hover:text-orange-600 font-semibold">
                                                <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 24 24"><path d="M10 4H4c-1.11 0-2 .89-2 2v12c0 1.097.903 2 2 2h16c1.097 0 2-.903 2-2V8a2 2 0 00-2-2h-8l-2-2z"/></svg>
                                                {{ $entry['name'] }}/
                                            </a>
                                        @else
                                            <span class="inline-flex items-center gap-2 text-slate-700">
                                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                {{ $entry['name'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right text-xs text-slate-500 font-mono">
                                        @if ($entry['is_dir'])
                                            <span class="text-slate-300">—</span>
                                        @else
                                            {{ number_format($entry['size'] / 1024, 1) }} KB
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

        </form>
    </div>
</x-admin-layout>
