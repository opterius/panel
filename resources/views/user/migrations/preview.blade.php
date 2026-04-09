<x-user-layout>
    <x-slot name="title">Preview Import</x-slot>

    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

        <div class="mb-6">
            <a href="{{ route('user.migrations.index') }}" class="text-sm text-slate-500 hover:text-slate-700">← Back to imports</a>
            <h1 class="text-2xl font-bold text-slate-900 mt-2">Preview Import</h1>
            <p class="text-slate-500 mt-1">
                We parsed your backup successfully. Choose what to import below — anything you uncheck will be skipped.
            </p>
        </div>

        @php
            $manifest = $migration->manifest ?? [];
            $domains    = $manifest['domains']   ?? [];
            $databases  = $manifest['databases'] ?? [];
            $emails     = $manifest['emails']    ?? [];
            $dnsZones   = $manifest['dns_zones'] ?? [];
            $crons      = $manifest['crons']     ?? [];
            $files      = $manifest['files']     ?? [];
        @endphp

        {{-- Source summary --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-5">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-slate-500">Source backup</div>
                    <div class="font-bold text-slate-900 text-lg">{{ $migration->main_domain ?? '—' }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">cPanel user: <code class="bg-slate-100 px-1.5 py-0.5 rounded font-mono">{{ $migration->original_username }}</code></div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('user.migrations.execute', $migration) }}" class="space-y-3">
            @csrf

            {{-- Files --}}
            <label class="block bg-white rounded-xl border border-slate-200 hover:border-orange-300 p-5 cursor-pointer transition">
                <div class="flex items-start gap-3">
                    <input type="checkbox" name="import_files" value="1" checked class="mt-1 rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                            <span class="font-semibold text-slate-900">Files</span>
                            <span class="text-xs text-slate-500">{{ count($files) ?: 'public_html' }}</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Restore website files into your <code class="bg-slate-100 px-1 rounded">public_html</code>. Existing files with the same name will be overwritten.</p>
                    </div>
                </div>
            </label>

            {{-- Databases --}}
            <label class="block bg-white rounded-xl border border-slate-200 hover:border-orange-300 p-5 cursor-pointer transition">
                <div class="flex items-start gap-3">
                    <input type="checkbox" name="import_databases" value="1" checked class="mt-1 rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                            <span class="font-semibold text-slate-900">Databases</span>
                            <span class="text-xs text-slate-500">{{ count($databases) }}</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Restore MySQL databases and their users. Database names are prefixed with your username automatically.</p>
                        @if (! empty($databases))
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach (array_slice($databases, 0, 8) as $db)
                                    <span class="text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-mono">{{ is_array($db) ? ($db['name'] ?? '?') : $db }}</span>
                                @endforeach
                                @if (count($databases) > 8)
                                    <span class="text-xs text-slate-400">+{{ count($databases) - 8 }} more</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </label>

            {{-- Email --}}
            <label class="block bg-white rounded-xl border border-slate-200 hover:border-orange-300 p-5 cursor-pointer transition">
                <div class="flex items-start gap-3">
                    <input type="checkbox" name="import_email" value="1" checked class="mt-1 rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span class="font-semibold text-slate-900">Email accounts</span>
                            <span class="text-xs text-slate-500">{{ count($emails) }}</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Restore email mailboxes including stored messages. Passwords from the source cPanel are preserved.</p>
                    </div>
                </div>
            </label>

            {{-- DNS --}}
            <label class="block bg-white rounded-xl border border-slate-200 hover:border-orange-300 p-5 cursor-pointer transition">
                <div class="flex items-start gap-3">
                    <input type="checkbox" name="import_dns" value="1" class="mt-1 rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg>
                            <span class="font-semibold text-slate-900">DNS records</span>
                            <span class="text-xs text-slate-500">{{ count($dnsZones) }} zones</span>
                            <span class="text-[10px] uppercase font-bold text-amber-600 bg-amber-100 px-1.5 py-0.5 rounded">off by default</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Import DNS zone records from cPanel. <strong>Disabled by default</strong> — turning this on may break the original site if it's still live elsewhere.</p>
                    </div>
                </div>
            </label>

            {{-- SSL --}}
            <label class="block bg-white rounded-xl border border-slate-200 hover:border-orange-300 p-5 cursor-pointer transition">
                <div class="flex items-start gap-3">
                    <input type="checkbox" name="import_ssl" value="1" checked class="mt-1 rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span class="font-semibold text-slate-900">SSL certificates</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Import any custom SSL certificates from the backup. Let's Encrypt certs will be re-issued automatically once DNS points to this server.</p>
                    </div>
                </div>
            </label>

            {{-- Cron --}}
            <label class="block bg-white rounded-xl border border-slate-200 hover:border-orange-300 p-5 cursor-pointer transition">
                <div class="flex items-start gap-3">
                    <input type="checkbox" name="import_cron" value="1" checked class="mt-1 rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="font-semibold text-slate-900">Cron jobs</span>
                            <span class="text-xs text-slate-500">{{ count($crons) }}</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Restore scheduled tasks from the source crontab.</p>
                    </div>
                </div>
            </label>

            <div class="pt-4 flex items-center justify-between">
                <a href="{{ route('user.migrations.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Cancel</a>
                <button type="submit" class="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Start Import
                </button>
            </div>
        </form>

    </div>
</x-user-layout>
