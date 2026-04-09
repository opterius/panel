<x-user-layout>
    <x-slot name="title">Live Log Viewer</x-slot>

    <div class="max-w-6xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Live Log Viewer</h1>
            <p class="text-slate-500 mt-1">Stream your error and access logs in real time. Pick a domain and a log type below.</p>
        </div>

        <div x-data="logTail()" x-init="init()">

            {{-- Selectors --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Domain</label>
                        <select x-model="domainId" @change="reset()"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="">— select —</option>
                            @foreach ($domains as $d)
                                <option value="{{ $d->id }}">{{ $d->domain }} ({{ $d->account->username }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Log type</label>
                        <select x-model="logType" @change="reset()"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="error">Nginx error.log</option>
                            <option value="access">Nginx access.log</option>
                            <option value="php-error">PHP-FPM error log</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="button" @click="toggle()"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition"
                                :class="streaming ? 'bg-red-500 hover:bg-red-600 text-white' : 'bg-green-600 hover:bg-green-700 text-white'">
                            <span x-show="!streaming">▶ Start Stream</span>
                            <span x-show="streaming">■ Stop</span>
                        </button>
                        <button type="button" @click="clear()"
                                class="px-3 py-2 rounded-lg text-sm font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700">
                            Clear
                        </button>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-4 text-xs text-slate-500">
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" x-model="autoscroll" class="rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                        Auto-scroll to bottom
                    </label>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" x-model="wrapLines" class="rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                        Wrap lines
                    </label>
                    <span x-show="streaming" class="inline-flex items-center gap-1.5 text-green-600 font-semibold">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        Live
                    </span>
                    <span x-show="error" class="text-red-600 font-semibold" x-text="error"></span>
                </div>
            </div>

            {{-- Terminal-style output --}}
            <div class="bg-slate-950 rounded-xl shadow-sm border border-slate-800 overflow-hidden">
                <div class="px-4 py-2.5 border-b border-slate-800 flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                        <span class="text-slate-400 ml-2 font-mono" x-text="filename"></span>
                    </div>
                    <span class="text-slate-500" x-text="byteCount + ' bytes'"></span>
                </div>
                <pre x-ref="output"
                     class="text-xs text-slate-200 p-4 overflow-auto h-[500px] font-mono leading-relaxed"
                     :class="wrapLines ? 'whitespace-pre-wrap' : 'whitespace-pre'"
                     x-html="renderedContent"></pre>
            </div>
        </div>
    </div>

    <script>
        function logTail() {
            return {
                domainId: '',
                logType: 'error',
                streaming: false,
                offset: 0,
                content: '',
                autoscroll: true,
                wrapLines: true,
                error: null,
                pollHandle: null,
                byteCount: 0,
                filename: '— no log selected —',

                init() {},

                reset() {
                    this.stop();
                    this.offset = 0;
                    this.content = '';
                    this.byteCount = 0;
                    this.error = null;
                    this.updateFilename();
                },

                clear() {
                    this.content = '';
                    this.byteCount = 0;
                },

                updateFilename() {
                    const map = { error: 'error.log', access: 'access.log', 'php-error': 'php-error.log' };
                    this.filename = this.domainId ? map[this.logType] : '— no log selected —';
                },

                toggle() {
                    if (this.streaming) {
                        this.stop();
                    } else {
                        this.start();
                    }
                },

                start() {
                    if (! this.domainId) {
                        this.error = 'Pick a domain first';
                        return;
                    }
                    this.error = null;
                    this.streaming = true;
                    this.updateFilename();
                    this.fetchOnce();
                    this.pollHandle = setInterval(() => this.fetchOnce(), 2000);
                },

                stop() {
                    this.streaming = false;
                    if (this.pollHandle) {
                        clearInterval(this.pollHandle);
                        this.pollHandle = null;
                    }
                },

                async fetchOnce() {
                    try {
                        const resp = await fetch('{{ route("user.logs.tail") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                domain_id: this.domainId,
                                log_type:  this.logType,
                                offset:    this.offset,
                            }),
                        });

                        if (! resp.ok) {
                            const err = await resp.json().catch(() => ({}));
                            this.error = err.error || ('HTTP ' + resp.status);
                            this.stop();
                            return;
                        }

                        const data = await resp.json();
                        if (data.missing) {
                            this.error = 'Log file does not exist yet — waiting for first entry...';
                            return;
                        }

                        this.error = null;
                        if (data.data) {
                            this.content += data.data;
                            this.byteCount += data.data.length;

                            // Cap in-memory buffer at ~256 KB so very long sessions don't crash the browser.
                            if (this.content.length > 262144) {
                                this.content = this.content.slice(-262144);
                            }

                            this.$nextTick(() => {
                                if (this.autoscroll && this.$refs.output) {
                                    this.$refs.output.scrollTop = this.$refs.output.scrollHeight;
                                }
                            });
                        }
                        this.offset = data.next_offset;
                    } catch (e) {
                        this.error = 'Network error: ' + e.message;
                        this.stop();
                    }
                },

                get renderedContent() {
                    // Escape HTML and apply simple log-line colouring (errors red, warnings yellow).
                    const escape = s => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    return escape(this.content)
                        .replace(/(\b(?:error|fatal|critical|emerg)\b[^\n]*)/gi, '<span class="text-red-400">$1</span>')
                        .replace(/(\b(?:warn|warning)\b[^\n]*)/gi, '<span class="text-yellow-400">$1</span>')
                        .replace(/(\b(?:notice|info)\b[^\n]*)/gi, '<span class="text-sky-400">$1</span>');
                },
            };
        }
    </script>
</x-user-layout>
