<x-user-layout>
    <x-slot name="title">Upload cPanel Backup</x-slot>

    <div class="max-w-3xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

        <div class="mb-6">
            <a href="{{ route('user.migrations.index') }}" class="text-sm text-slate-500 hover:text-slate-700">← Back to imports</a>
            <h1 class="text-2xl font-bold text-slate-900 mt-2">Upload cPanel Backup</h1>
            <p class="text-slate-500 mt-1">
                Upload a full cPanel backup file. We'll parse it and let you choose what to import on the next screen.
            </p>
        </div>

        @if (session('error'))
            <div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif

        @error('backup')
            <div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('user.migrations.store') }}" enctype="multipart/form-data" class="bg-white rounded-2xl border border-slate-200 p-6"
              x-data="{ filename: null, uploading: false }">
            @csrf

            <label class="block">
                <input type="file" name="backup" accept=".tar.gz,.tgz,.tar,.zip" required
                       @change="filename = $event.target.files[0]?.name"
                       class="block w-full text-sm text-slate-600
                              file:mr-4 file:py-3 file:px-5
                              file:rounded-lg file:border-0
                              file:bg-orange-500 file:text-white file:font-semibold
                              hover:file:bg-orange-600 cursor-pointer">
            </label>

            <p class="text-xs text-slate-500 mt-3">
                Accepted formats: <code class="bg-slate-100 px-1 rounded">.tar.gz</code>, <code class="bg-slate-100 px-1 rounded">.tgz</code>, <code class="bg-slate-100 px-1 rounded">.tar</code>, <code class="bg-slate-100 px-1 rounded">.zip</code>. Maximum file size: 5 GB.
            </p>

            <div x-show="filename" class="mt-4 rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="text-slate-700 font-mono" x-text="filename"></span>
            </div>

            <div class="mt-6 pt-6 border-t border-slate-100 flex items-center justify-between">
                <p class="text-xs text-slate-500">The upload may take several minutes for large backups. Please don't close this tab.</p>
                <button type="submit"
                        @click="uploading = true"
                        :disabled="uploading"
                        class="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 disabled:bg-orange-300 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
                    <svg x-show="!uploading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    <svg x-show="uploading" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="3" stroke-dasharray="31.4 31.4"/></svg>
                    <span x-text="uploading ? 'Uploading…' : 'Upload &amp; Parse'"></span>
                </button>
            </div>
        </form>

        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm text-blue-800 space-y-2">
            <p><strong>How to get a cPanel backup:</strong></p>
            <ol class="list-decimal list-inside space-y-1 text-blue-900/90">
                <li>Log in to your old cPanel</li>
                <li>Go to <strong>Files → Backup Wizard</strong> (or just <strong>Backup</strong>)</li>
                <li>Click <strong>Backup → Full Account Backup</strong></li>
                <li>Wait for the email saying it's ready, then download the file</li>
                <li>The file is named <code class="bg-white/60 px-1 rounded">backup-yyyy-mm-dd_username.tar.gz</code></li>
            </ol>
        </div>
    </div>
</x-user-layout>
