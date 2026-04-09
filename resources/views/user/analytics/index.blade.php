<x-user-layout>
    <x-slot name="title">Visitor Analytics</x-slot>

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8"
         x-data="analyticsApp({{ $domains->toJson() }})"
         x-init="init()">

        <div class="mb-8 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Visitor Analytics</h1>
                <p class="text-slate-500 mt-1">Privacy-friendly traffic stats parsed from your access logs. No tracking script. No cookies. No third-party requests.</p>
            </div>
        </div>

        @if ($domains->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-500">
                You have no domains yet.
            </div>
        @else
            {{-- Top bar: domain selector + range buttons + refresh --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-4 mb-6 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    <select x-model="domainId" @change="reload()"
                            class="rounded-lg border-slate-300 text-sm focus:border-orange-500 focus:ring-orange-500 min-w-[240px]">
                        <template x-for="d in domains" :key="d.id">
                            <option :value="d.id" x-text="d.domain"></option>
                        </template>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <div class="inline-flex rounded-lg border border-slate-200 overflow-hidden">
                        <template x-for="r in ranges" :key="r.key">
                            <button type="button" @click="setRange(r.key)"
                                    :class="range === r.key ? 'bg-orange-500 text-white' : 'bg-white text-slate-600 hover:bg-slate-50'"
                                    class="px-4 py-2 text-xs font-semibold border-r border-slate-200 last:border-r-0 transition"
                                    x-text="r.label"></button>
                        </template>
                    </div>
                    <button type="button" @click="reload()" :disabled="loading"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-lg transition disabled:opacity-50">
                        <svg class="w-3.5 h-3.5" :class="loading && 'animate-spin'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Refresh
                    </button>
                </div>
            </div>

            {{-- Stat cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Visits</div>
                        <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 text-3xl font-extrabold text-slate-900" x-text="formatNum(summary.visits)">—</div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Unique Visitors</div>
                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 text-3xl font-extrabold text-slate-900" x-text="formatNum(summary.unique)">—</div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Bandwidth</div>
                        <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 text-3xl font-extrabold text-slate-900" x-text="formatBytes(summary.bandwidth)">—</div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Bot Traffic</div>
                        <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h.01M15 9h.01"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 text-3xl font-extrabold text-slate-900" x-text="(summary.bot_percent || 0).toFixed(1) + '%'">—</div>
                </div>
            </div>

            {{-- Visits chart --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-slate-800">Visits Over Time</h3>
                    <div class="flex items-center gap-3 text-xs">
                        <span class="inline-flex items-center gap-1.5 text-slate-600">
                            <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span> Visits
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-slate-600">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Unique
                        </span>
                    </div>
                </div>
                <div class="relative h-64"><canvas id="visitsChart"></canvas></div>
            </div>

            {{-- Top pages + top referrers --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <h3 class="font-semibold text-slate-800">Top Pages</h3>
                    </div>
                    <div class="divide-y divide-slate-50 max-h-96 overflow-auto">
                        <template x-for="row in topPages" :key="row.key">
                            <div class="px-5 py-2.5 flex items-center gap-3 text-sm hover:bg-slate-50">
                                <div class="flex-1 min-w-0">
                                    <div class="font-mono text-slate-700 truncate" :title="row.key" x-text="row.key"></div>
                                    <div class="h-1 bg-slate-100 rounded-full mt-1.5 overflow-hidden">
                                        <div class="h-full bg-orange-500 rounded-full" :style="'width: ' + barWidth(row.value, topPages) + '%'"></div>
                                    </div>
                                </div>
                                <span class="font-bold text-slate-900 shrink-0 text-right" x-text="formatNum(row.value)"></span>
                            </div>
                        </template>
                        <template x-if="topPages.length === 0">
                            <div class="px-5 py-12 text-center text-slate-400 text-sm">No data yet</div>
                        </template>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        <h3 class="font-semibold text-slate-800">Top Referrers</h3>
                    </div>
                    <div class="divide-y divide-slate-50 max-h-96 overflow-auto">
                        <template x-for="row in topRefs" :key="row.key">
                            <div class="px-5 py-2.5 flex items-center gap-3 text-sm hover:bg-slate-50">
                                <div class="flex-1 min-w-0">
                                    <div class="text-slate-700 truncate" x-text="row.key"></div>
                                    <div class="h-1 bg-slate-100 rounded-full mt-1.5 overflow-hidden">
                                        <div class="h-full bg-blue-500 rounded-full" :style="'width: ' + barWidth(row.value, topRefs) + '%'"></div>
                                    </div>
                                </div>
                                <span class="font-bold text-slate-900 shrink-0 text-right" x-text="formatNum(row.value)"></span>
                            </div>
                        </template>
                        <template x-if="topRefs.length === 0">
                            <div class="px-5 py-12 text-center text-slate-400 text-sm">No data yet</div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Countries / Browsers / OS --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

                {{-- Countries --}}
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <h3 class="font-semibold text-slate-800">Top Countries</h3>
                    </div>
                    <div class="divide-y divide-slate-50">
                        <template x-for="row in countries" :key="row.key">
                            <div class="px-5 py-2.5 flex items-center gap-3 text-sm hover:bg-slate-50">
                                <span class="text-2xl leading-none" x-text="flagEmoji(row.key)"></span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-slate-700 truncate" x-text="countryName(row.key)"></div>
                                    <div class="h-1 bg-slate-100 rounded-full mt-1.5 overflow-hidden">
                                        <div class="h-full bg-emerald-500 rounded-full" :style="'width: ' + barWidth(row.value, countries) + '%'"></div>
                                    </div>
                                </div>
                                <span class="font-bold text-slate-900 shrink-0" x-text="formatNum(row.value)"></span>
                            </div>
                        </template>
                        <template x-if="countries.length === 0">
                            <div class="px-5 py-12 text-center text-slate-400 text-sm">
                                <p>No data yet</p>
                                <p class="text-xs mt-1">Configure MaxMind GeoLite2 in admin settings to enable countries.</p>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Browsers --}}
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3a14.95 14.95 0 00-9 17.95M12 3a14.95 14.95 0 019 17.95M3 12h18"/></svg>
                        <h3 class="font-semibold text-slate-800">Top Browsers</h3>
                    </div>
                    <div class="divide-y divide-slate-50">
                        <template x-for="row in browsers" :key="row.key">
                            <div class="px-5 py-2.5 flex items-center gap-3 text-sm hover:bg-slate-50">
                                <span x-html="browserIcon(row.key)"></span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-slate-700" x-text="row.key"></div>
                                    <div class="h-1 bg-slate-100 rounded-full mt-1.5 overflow-hidden">
                                        <div class="h-full bg-indigo-500 rounded-full" :style="'width: ' + barWidth(row.value, browsers) + '%'"></div>
                                    </div>
                                </div>
                                <span class="font-bold text-slate-900 shrink-0" x-text="formatNum(row.value)"></span>
                            </div>
                        </template>
                        <template x-if="browsers.length === 0">
                            <div class="px-5 py-12 text-center text-slate-400 text-sm">No data yet</div>
                        </template>
                    </div>
                </div>

                {{-- Operating Systems --}}
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <h3 class="font-semibold text-slate-800">Operating Systems</h3>
                    </div>
                    <div class="divide-y divide-slate-50">
                        <template x-for="row in os" :key="row.key">
                            <div class="px-5 py-2.5 flex items-center gap-3 text-sm hover:bg-slate-50">
                                <span x-html="osIcon(row.key)"></span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-slate-700" x-text="row.key"></div>
                                    <div class="h-1 bg-slate-100 rounded-full mt-1.5 overflow-hidden">
                                        <div class="h-full bg-pink-500 rounded-full" :style="'width: ' + barWidth(row.value, os) + '%'"></div>
                                    </div>
                                </div>
                                <span class="font-bold text-slate-900 shrink-0" x-text="formatNum(row.value)"></span>
                            </div>
                        </template>
                        <template x-if="os.length === 0">
                            <div class="px-5 py-12 text-center text-slate-400 text-sm">No data yet</div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Status codes --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-6">
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    HTTP Status Codes
                </h3>
                <template x-if="Object.keys(statusCodes).length === 0">
                    <p class="text-sm text-slate-400 text-center py-6">No data yet</p>
                </template>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3" x-show="Object.keys(statusCodes).length > 0">
                    <template x-for="code in sortedStatusCodes" :key="code">
                        <div class="rounded-xl p-4 text-center" :class="statusBg(code)">
                            <div class="text-2xl font-extrabold" :class="statusText(code)" x-text="code"></div>
                            <div class="text-xs text-slate-600 mt-1 font-semibold" x-text="formatNum(statusCodes[code])"></div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Privacy footer --}}
            <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm text-blue-800 flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <div>
                    <strong>Privacy-first analytics.</strong>
                    Visitor data is parsed directly from your Nginx access logs on this server. No tracking script is loaded on your site, no cookies are set, no IP addresses are stored, and no data leaves your server. Fully GDPR-friendly with no consent banner needed.
                </div>
            </div>

        @endif

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
    function analyticsApp(domains) {
        return {
            domains: domains,
            domainId: domains[0]?.id || null,
            range: '24h',
            ranges: [
                { key: '24h', label: '24 Hours' },
                { key: '7d',  label: '7 Days'  },
                { key: '30d', label: '30 Days' },
                { key: '90d', label: '90 Days' },
            ],
            loading: false,
            summary: { visits: 0, unique: 0, bandwidth: 0, bot_percent: 0 },
            timeseries: [],
            topPages: [],
            topRefs: [],
            countries: [],
            browsers: [],
            os: [],
            statusCodes: {},
            chart: null,

            init() {
                this.initChart();
                this.reload();
            },

            setRange(r) {
                this.range = r;
                this.reload();
            },

            async reload() {
                if (! this.domainId) return;
                this.loading = true;
                try {
                    const resp = await fetch('{{ route("user.analytics.query") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            domain_id: this.domainId,
                            range: this.range,
                        }),
                    });
                    const data = await resp.json();
                    if (data.error) {
                        console.error(data.error);
                        return;
                    }
                    this.summary     = data.summary || this.summary;
                    this.timeseries  = data.timeseries || [];
                    this.topPages    = data.top_pages || [];
                    this.topRefs     = data.top_referrers || [];
                    this.countries   = data.top_countries || [];
                    this.browsers    = data.top_browsers || [];
                    this.os          = data.top_os || [];
                    this.statusCodes = data.status_codes || {};
                    this.updateChart();
                } catch (e) {
                    console.error(e);
                } finally {
                    this.loading = false;
                }
            },

            initChart() {
                const ctx = document.getElementById('visitsChart');
                if (! ctx) return;
                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: { labels: [], datasets: [
                        {
                            label: 'Visits',
                            data: [],
                            borderColor: '#f97316',
                            backgroundColor: 'rgba(249, 115, 22, 0.12)',
                            fill: true,
                            tension: 0.35,
                        },
                        {
                            label: 'Unique',
                            data: [],
                            borderColor: '#3b82f6',
                            backgroundColor: 'transparent',
                            fill: false,
                            tension: 0.35,
                            borderDash: [4, 4],
                        },
                    ]},
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        scales: {
                            x: { display: true, grid: { display: false }, ticks: { font: { size: 10 } } },
                            y: { beginAtZero: true, ticks: { font: { size: 10 } } },
                        },
                        plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
                        elements: { point: { radius: 0, hoverRadius: 5 }, line: { borderWidth: 2 } },
                    },
                });
            },

            updateChart() {
                if (! this.chart) return;
                const showHour = this.range === '24h';
                const showDate = this.range === '7d' || this.range === '30d' || this.range === '90d';

                const labels = this.timeseries.map(p => {
                    const d = new Date(p.t * 1000);
                    if (showHour) {
                        return String(d.getHours()).padStart(2, '0') + ':00';
                    }
                    if (showDate) {
                        return (d.getMonth() + 1) + '/' + d.getDate();
                    }
                    return '';
                });

                this.chart.data.labels = labels;
                this.chart.data.datasets[0].data = this.timeseries.map(p => p.v);
                this.chart.data.datasets[1].data = this.timeseries.map(p => p.u);
                this.chart.update('none');
            },

            // ── Helpers ────────────────────────────────────────────────

            formatNum(n) {
                if (n === undefined || n === null) return '0';
                if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
                if (n >= 1000)    return (n / 1000).toFixed(1) + 'k';
                return n.toString();
            },

            formatBytes(bytes) {
                if (! bytes) return '0 B';
                const units = ['B', 'KB', 'MB', 'GB', 'TB'];
                let i = 0;
                while (bytes >= 1024 && i < units.length - 1) { bytes /= 1024; i++; }
                return bytes.toFixed(1) + ' ' + units[i];
            },

            barWidth(value, list) {
                const max = list.length > 0 ? list[0].value : 1;
                if (max === 0) return 0;
                return Math.max(2, Math.round((value / max) * 100));
            },

            flagEmoji(code) {
                if (! code || code.length !== 2 || code === '??') return '🌐';
                const offset = 127397;
                return String.fromCodePoint(...code.toUpperCase().split('').map(c => c.charCodeAt(0) + offset));
            },

            countryName(code) {
                const names = {
                    US: 'United States', GB: 'United Kingdom', DE: 'Germany', FR: 'France',
                    RO: 'Romania', IT: 'Italy', ES: 'Spain', NL: 'Netherlands', PL: 'Poland',
                    RU: 'Russia', UA: 'Ukraine', JP: 'Japan', CN: 'China', IN: 'India',
                    BR: 'Brazil', MX: 'Mexico', CA: 'Canada', AU: 'Australia',
                    SE: 'Sweden', NO: 'Norway', FI: 'Finland', DK: 'Denmark',
                    BE: 'Belgium', CH: 'Switzerland', AT: 'Austria', PT: 'Portugal',
                    GR: 'Greece', TR: 'Turkey', IL: 'Israel', AE: 'UAE',
                    SG: 'Singapore', HK: 'Hong Kong', KR: 'South Korea', TW: 'Taiwan',
                    ZA: 'South Africa', AR: 'Argentina', CL: 'Chile', CO: 'Colombia',
                    '??': 'Unknown',
                };
                return names[code] || code;
            },

            browserIcon(name) {
                const icons = {
                    Chrome:  '<svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M12 2C9.232 2 6.748 3.146 5 5l3.5 6.06A4.001 4.001 0 0112 8h7.95A10 10 0 0012 2z"/><path fill="#EA4335" d="M2 12c0 3.073 1.388 5.829 3.575 7.668L9.07 13.66A4 4 0 018.5 11.06L5 5C3.146 6.748 2 9.232 2 12z"/><path fill="#FBBC04" d="M12 22c4.97 0 9-4.03 9-10 0-1.045-.155-2.057-.45-3H12a4 4 0 01-3.5-2.06L4.575 19.668C6.748 21.512 9.227 22 12 22z"/><circle cx="12" cy="12" r="3.2" fill="#fff"/><circle cx="12" cy="12" r="2.4" fill="#4285F4"/></svg>',
                    Firefox: '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="#FF7139"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10c0-1.5-.3-2.9-.9-4.2C20 4.7 17 4 17 4c1.5 1.5 1.8 3.5 1.5 5-.5-2.5-1.7-4.5-4-5.5C12.5 3 11 3.5 9.5 5 8 6.5 7.5 8 8 9.5 6.5 10 5.5 11 5 12.5c-.5-1 0-2.5 1-4-3 1.5-4 4.5-3 7.5 1.5 4 5 6 9 6 5.5 0 10-4.5 10-10 0-1.7-.4-3.3-1.2-4.7.6.8 1.2 1.7 1.7 2.7 0-.1 0-.3-.5-2-.5-1.7-2-3-2-3 0 .5.2 1 .5 1.5"/></svg>',
                    Safari:  '<svg class="w-5 h-5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="#1B88CA"/><circle cx="12" cy="12" r="9" fill="#fff"/><circle cx="12" cy="12" r="8" fill="#1B88CA"/><polygon points="12,5 13.5,11 12,12 10.5,11" fill="#fff"/><polygon points="12,19 10.5,13 12,12 13.5,13" fill="#FF3B30"/></svg>',
                    Edge:    '<svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#0078D4" d="M21.86 17.86c-.3.74-.66 1.43-1.05 2.07-.42-.4-2.41-2.33-2.41-5.45 0-2.69 1.81-4.08 2.84-4.66 1.27 1.66 2.04 3.7 2.04 5.92 0 .73-.09 1.44-.25 2.12zM12 2C6.48 2 2 6.48 2 12c0 .9.13 1.78.36 2.61.13-2.84 2.49-5.11 5.39-5.11 1.49 0 2.85.6 3.83 1.58.55.55 1.04 1.31 1.04 2.42 0 1.92-1.55 3.5-3.5 3.5-1.49 0-2.66-.91-3.13-2.16C5.31 16.7 6.95 19 9.5 19c2.49 0 4.5-2.01 4.5-4.5 0-1.42-.66-2.69-1.69-3.51C13.5 9.5 16 8 18 8c1.5 0 2.85.5 4 1.5 0-3-3-7-10-7.5z"/></svg>',
                    Opera:   '<svg class="w-5 h-5" viewBox="0 0 24 24"><ellipse cx="12" cy="12" rx="10" ry="11" fill="#FF1B2D"/><ellipse cx="12" cy="12" rx="4" ry="7.5" fill="#fff"/></svg>',
                    Bot:     '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="#64748b"><path d="M12 2a2 2 0 00-2 2v1H7a3 3 0 00-3 3v9a3 3 0 003 3h10a3 3 0 003-3V8a3 3 0 00-3-3h-3V4a2 2 0 00-2-2zm-2.5 8a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm5 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM9 16h6v1H9v-1z"/></svg>',
                    Other:   '<svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                    Unknown: '<svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                };
                return icons[name] || icons.Other;
            },

            osIcon(name) {
                const icons = {
                    Windows: '<svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#0078D4" d="M3 5.5l8-1.1V11H3V5.5zm9-1.25l10-1.4V11H12V4.25zM3 12h8v6.6L3 17.5V12zm9 0h10v8.65l-10-1.4V12z"/></svg>',
                    macOS:   '<svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#000" d="M17.05 12.04c-.03-2.86 2.34-4.24 2.45-4.31-1.34-1.96-3.43-2.23-4.16-2.26-1.77-.18-3.46 1.04-4.36 1.04-.91 0-2.29-1.02-3.78-.99-1.94.03-3.74 1.13-4.74 2.87-2.04 3.53-.52 8.74 1.46 11.6.97 1.4 2.12 2.97 3.61 2.92 1.46-.06 2-.94 3.76-.94 1.75 0 2.25.94 3.78.91 1.56-.03 2.55-1.42 3.5-2.83 1.11-1.62 1.57-3.2 1.59-3.28-.04-.02-3.06-1.17-3.09-4.65zm-2.83-8.55c.79-.96 1.32-2.29 1.18-3.62-1.14.05-2.52.76-3.34 1.71-.74.85-1.38 2.21-1.21 3.51 1.27.1 2.57-.65 3.37-1.6z"/></svg>',
                    Linux:   '<svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#FCC624" d="M12.504.06C6.65.06 2.99 6.43 2.99 6.43s.05.32.05.7c0 1.07-.42 2.14-.74 2.93-.18.45-.27.83-.27 1.18 0 .35.09.66.18.93.32.93.6 1.83.6 2.93 0 .55-.09 1.07-.27 1.5-.27.66-.78 1.16-.78 2.36 0 1.69 1.59 2.78 3.18 3.65 1.41.78 2.83 1.62 4.27 1.62 1.05 0 1.59-.43 2.07-.97.36-.4.7-.83 1.23-.83.51 0 .85.37 1.21.78.51.55 1.05.97 2.07.97 1.43 0 2.86-.83 4.27-1.62 1.59-.87 3.18-1.97 3.18-3.65 0-1.2-.51-1.69-.78-2.36-.18-.43-.27-.94-.27-1.5 0-1.1.27-2.01.6-2.93.09-.27.18-.58.18-.93 0-.35-.09-.73-.27-1.18-.32-.78-.74-1.86-.74-2.93 0-.37.05-.7.05-.7S17.36.06 11.5.06c-.34.34-.34 0 1.004 0z"/><circle cx="9" cy="10" r="1" fill="#000"/><circle cx="15" cy="10" r="1" fill="#000"/></svg>',
                    Android: '<svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#3DDC84" d="M16.5 14.5c0-2.5-2-4.5-4.5-4.5S7.5 12 7.5 14.5v5.5c0 .55.45 1 1 1h7c.55 0 1-.45 1-1v-5.5zm-9 0v.5h-1.5c-.83 0-1.5.67-1.5 1.5v3c0 .83.67 1.5 1.5 1.5H7v.5c0 .55.45 1 1 1h.5v-2H7v-3.5h.5v-.5zm10.5 0h.5v.5H18v3.5h-.5V19v2H18c.55 0 1-.45 1-1V19h.5c.83 0 1.5-.67 1.5-1.5v-3c0-.83-.67-1.5-1.5-1.5H18v-.5zM7.5 8.5c-.83 0-1.5.67-1.5 1.5v3.5h.5V14h12v-.5h.5V10c0-.83-.67-1.5-1.5-1.5h-10zm1-2.5l-.85-1.45c-.07-.13-.02-.3.11-.37.13-.08.3-.03.37.11L9.13 5.7c.84-.32 1.78-.5 2.87-.5s2.03.18 2.87.5l1-1.41c.07-.14.24-.19.37-.11.13.07.18.24.11.37l-.85 1.45c1.5.83 2.5 2.27 2.5 3.83v.17h-12v-.17c0-1.56 1-3 2.5-3.83zM10 8c-.28 0-.5-.22-.5-.5s.22-.5.5-.5.5.22.5.5-.22.5-.5.5zm4 0c-.28 0-.5-.22-.5-.5s.22-.5.5-.5.5.22.5.5-.22.5-.5.5z"/></svg>',
                    iOS:     '<svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#000" d="M17.05 12.04c-.03-2.86 2.34-4.24 2.45-4.31-1.34-1.96-3.43-2.23-4.16-2.26-1.77-.18-3.46 1.04-4.36 1.04-.91 0-2.29-1.02-3.78-.99-1.94.03-3.74 1.13-4.74 2.87-2.04 3.53-.52 8.74 1.46 11.6.97 1.4 2.12 2.97 3.61 2.92 1.46-.06 2-.94 3.76-.94 1.75 0 2.25.94 3.78.91 1.56-.03 2.55-1.42 3.5-2.83 1.11-1.62 1.57-3.2 1.59-3.28-.04-.02-3.06-1.17-3.09-4.65zm-2.83-8.55c.79-.96 1.32-2.29 1.18-3.62-1.14.05-2.52.76-3.34 1.71-.74.85-1.38 2.21-1.21 3.51 1.27.1 2.57-.65 3.37-1.6z"/></svg>',
                    Bot:     '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="#64748b"><path d="M12 2a2 2 0 00-2 2v1H7a3 3 0 00-3 3v9a3 3 0 003 3h10a3 3 0 003-3V8a3 3 0 00-3-3h-3V4a2 2 0 00-2-2zm-2.5 8a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm5 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM9 16h6v1H9v-1z"/></svg>',
                    Other:   '<svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
                    Unknown: '<svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
                };
                return icons[name] || icons.Other;
            },

            get sortedStatusCodes() {
                return Object.keys(this.statusCodes).sort();
            },

            statusBg(code) {
                if (code.startsWith('2')) return 'bg-green-50';
                if (code.startsWith('3')) return 'bg-blue-50';
                if (code.startsWith('4')) return 'bg-amber-50';
                if (code.startsWith('5')) return 'bg-red-50';
                return 'bg-slate-50';
            },

            statusText(code) {
                if (code.startsWith('2')) return 'text-green-600';
                if (code.startsWith('3')) return 'text-blue-600';
                if (code.startsWith('4')) return 'text-amber-600';
                if (code.startsWith('5')) return 'text-red-600';
                return 'text-slate-600';
            },
        };
    }
    </script>
</x-user-layout>
