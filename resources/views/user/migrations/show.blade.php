<x-user-layout>
    <x-slot name="title">Import Status</x-slot>

    <div class="max-w-3xl mx-auto py-6 px-4 sm:px-6 lg:px-8"
         x-data="importStatus({{ $migration->id }}, {{ json_encode($migration->status) }})"
         x-init="init()">

        @if (session('success'))
            <div class="mb-6 rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        <div class="mb-6">
            <a href="{{ route('user.migrations.index') }}" class="text-sm text-slate-500 hover:text-slate-700">← Back to imports</a>
            <h1 class="text-2xl font-bold text-slate-900 mt-2">Import: {{ $migration->main_domain }}</h1>
        </div>

        {{-- Status banner --}}
        <div class="bg-white rounded-2xl border p-6 mb-6"
             :class="{
                 'border-green-200':  status === 'completed',
                 'border-amber-200':  status === 'partial',
                 'border-red-200':    status === 'failed',
                 'border-blue-200':   ['pending','running'].includes(status),
                 'border-slate-200':  ['previewing'].includes(status),
             }">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
                     :class="{
                         'bg-green-100': status === 'completed',
                         'bg-amber-100': status === 'partial',
                         'bg-red-100':   status === 'failed',
                         'bg-blue-100':  ['pending','running'].includes(status),
                     }">
                    <svg x-show="status === 'completed'" class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <svg x-show="status === 'failed'"    class="w-6 h-6 text-red-600"   fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <svg x-show="['pending','running'].includes(status)" class="w-6 h-6 text-blue-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="3" stroke-dasharray="31.4 31.4"/></svg>
                </div>
                <div class="flex-1">
                    <div class="text-lg font-bold text-slate-900" x-text="statusLabel()"></div>
                    <div class="text-sm text-slate-500" x-text="currentStep || 'Preparing...'"></div>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-extrabold text-slate-900" x-text="progress + '%'"></div>
                </div>
            </div>

            {{-- Progress bar --}}
            <div class="mt-4 h-2 bg-slate-100 rounded-full overflow-hidden" x-show="['pending','running'].includes(status) || progress > 0">
                <div class="h-full transition-all duration-500"
                     :class="status === 'failed' ? 'bg-red-500' : (status === 'completed' ? 'bg-green-500' : 'bg-blue-500')"
                     :style="'width: ' + progress + '%'"></div>
            </div>

            {{-- Error --}}
            <template x-if="error">
                <div class="mt-4 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-800">
                    <strong>Error:</strong> <span x-text="error"></span>
                </div>
            </template>
        </div>

        {{-- Result summary (when finished) --}}
        <template x-if="['completed','partial','failed'].includes(status) && result">
            <div class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h3 class="font-semibold text-slate-800">Import Results</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <template x-for="(item, key) in result" :key="key">
                            <div class="px-5 py-3 flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full" :class="item.status === 'success' ? 'bg-green-500' : (item.status === 'skipped' ? 'bg-slate-400' : 'bg-red-500')"></span>
                                    <span class="font-semibold text-slate-700 capitalize" x-text="key.replace(/_/g, ' ')"></span>
                                </div>
                                <span class="text-slate-500" x-text="item.message || item.status"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Database credentials — visible only after a successful import --}}
                <template x-if="result?.databases?.details?.length">
                    <div class="bg-amber-50 border border-amber-200 rounded-2xl overflow-hidden">
                        <div class="px-5 py-4 border-b border-amber-200">
                            <h3 class="font-bold text-amber-900 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Database Credentials
                            </h3>
                            <p class="text-xs text-amber-800 mt-1">
                                Update your application's config file (e.g. <code>wp-config.php</code>, <code>.env</code>) with these new credentials.
                                You can also reveal these later from each database's detail page.
                            </p>
                        </div>
                        <div class="divide-y divide-amber-200">
                            <template x-for="db in result.databases.details" :key="db.name">
                                <template x-if="db.status === 'success' && db.db_password">
                                    <div class="px-5 py-3">
                                        <div class="font-semibold text-amber-900 mb-1.5" x-text="db.name"></div>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                                            <div>
                                                <div class="text-amber-700 font-semibold uppercase">User</div>
                                                <code class="font-mono text-slate-800 bg-white border border-amber-200 px-2 py-1 rounded mt-0.5 block select-all" x-text="db.db_user"></code>
                                            </div>
                                            <div class="sm:col-span-2">
                                                <div class="text-amber-700 font-semibold uppercase">Password</div>
                                                <div class="flex items-center gap-1 mt-0.5">
                                                    <code class="font-mono text-slate-800 bg-white border border-amber-200 px-2 py-1 rounded flex-1 select-all" x-text="db.db_password"></code>
                                                    <button type="button"
                                                            @click="navigator.clipboard.writeText(db.db_password)"
                                                            class="text-xs font-semibold text-amber-700 hover:text-amber-900 px-2">Copy</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <template x-if="status === 'completed' || status === 'partial'">
            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('user.dashboard') }}" class="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                    Go to Dashboard
                </a>
            </div>
        </template>
    </div>

    <script>
        function importStatus(id, initialStatus) {
            return {
                id: id,
                status: initialStatus,
                progress: 0,
                currentStep: '',
                result: null,
                error: null,

                init() {
                    this.poll();
                    if (! ['completed','failed','partial'].includes(this.status)) {
                        this.interval = setInterval(() => this.poll(), 2000);
                    }
                },

                async poll() {
                    try {
                        const resp = await fetch(`/user/import/${this.id}/status`);
                        const data = await resp.json();
                        this.status      = data.status;
                        this.progress    = data.progress || 0;
                        this.currentStep = data.current_step || '';
                        this.result      = data.result;
                        this.error       = data.error;

                        if (['completed','failed','partial'].includes(this.status) && this.interval) {
                            clearInterval(this.interval);
                            this.interval = null;
                        }
                    } catch (e) {}
                },

                statusLabel() {
                    return {
                        previewing: 'Awaiting confirmation',
                        pending:    'Queued — waiting to start',
                        running:    'Import in progress…',
                        completed:  'Import completed successfully',
                        partial:    'Import completed with warnings',
                        failed:     'Import failed',
                    }[this.status] || this.status;
                },
            };
        }
    </script>
</x-user-layout>
