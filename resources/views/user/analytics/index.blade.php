<x-user-layout>
    <x-slot name="title">Visitor Analytics</x-slot>

    <div class="w-full py-6 px-4 sm:px-6 lg:px-8"
         x-data="analyticsApp({{ $domains->toJson() }})"
         x-init="init()">

        <div class="mb-6 flex items-start justify-between gap-4">
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
            {{-- Top bar: domain selector + range + compare + refresh --}}
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
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="inline-flex rounded-lg border border-slate-200 overflow-hidden">
                        <template x-for="r in ranges" :key="r.key">
                            <button type="button" @click="setRange(r.key)"
                                    :class="range === r.key ? 'bg-orange-500 text-white' : 'bg-white text-slate-600 hover:bg-slate-50'"
                                    class="px-4 py-2 text-xs font-semibold border-r border-slate-200 last:border-r-0 transition"
                                    x-text="r.label"></button>
                        </template>
                    </div>
                    <label class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg cursor-pointer transition"
                           :class="compare ? 'bg-indigo-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                        <input type="checkbox" x-model="compare" @change="reload()" class="hidden">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        Compare
                    </label>
                    <button type="button" @click="reload()" :disabled="loading"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-lg transition disabled:opacity-50">
                        <svg class="w-3.5 h-3.5" :class="loading && 'animate-spin'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Refresh
                    </button>
                </div>
            </div>

            {{-- ── LIVE ──────────────────────────────────────────────────── --}}
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl p-5 mb-6 flex flex-wrap items-center gap-6">
                <div class="flex items-center gap-3">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <div>
                        <div class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Live visitors</div>
                        <div class="text-3xl font-extrabold" x-text="live.active_now || 0"></div>
                    </div>
                </div>
                <div class="flex items-center gap-3 border-l border-slate-700 pl-6">
                    <div>
                        <div class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Requests — last 5 min</div>
                        <div class="text-xl font-bold" x-text="formatNum(live.visits_last_5 || 0)"></div>
                    </div>
                </div>
                <div class="flex-1 min-w-[240px]">
                    <div class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold mb-1">Visits — last 30 min</div>
                    <div class="relative h-12"><canvas id="liveChart"></canvas></div>
                </div>
            </div>

            {{-- ── Stat cards ────────────────────────────────────────────── --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Visits</div>
                        <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 text-3xl font-extrabold text-slate-900" x-text="formatNum(summary.visits)">—</div>
                    <div x-show="compare && summaryPrev.visits !== undefined" class="mt-1 text-xs" :class="deltaClass(summary.visits, summaryPrev.visits)" x-text="deltaText(summary.visits, summaryPrev.visits)"></div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Unique Visitors</div>
                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 text-3xl font-extrabold text-slate-900" x-text="formatNum(summary.unique)">—</div>
                    <div x-show="compare && summaryPrev.unique !== undefined" class="mt-1 text-xs" :class="deltaClass(summary.unique, summaryPrev.unique)" x-text="deltaText(summary.unique, summaryPrev.unique)"></div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Bandwidth</div>
                        <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 text-3xl font-extrabold text-slate-900" x-text="formatBytes(summary.bandwidth)">—</div>
                    <div x-show="compare && summaryPrev.bandwidth !== undefined" class="mt-1 text-xs" :class="deltaClass(summary.bandwidth, summaryPrev.bandwidth)" x-text="deltaText(summary.bandwidth, summaryPrev.bandwidth)"></div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Bots</div>
                        <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 text-3xl font-extrabold text-slate-900" x-text="(summary.bot_percent || 0).toFixed(1) + '%'">—</div>
                </div>
            </div>

            {{-- ── Visits chart (with optional compare overlay) ──────────── --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-slate-800">Visits & Unique Visitors</h3>
                    <div class="flex items-center gap-4 text-xs">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-orange-500"></span><span class="text-slate-600">Visits</span></span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-0.5 rounded-full bg-blue-500"></span><span class="text-slate-600">Unique</span></span>
                        <template x-if="compare">
                            <span class="flex items-center gap-1.5"><span class="w-3 h-0.5 rounded-full bg-slate-400"></span><span class="text-slate-500">Previous period</span></span>
                        </template>
                    </div>
                </div>
                <div class="relative h-64"><canvas id="visitsChart"></canvas></div>
            </div>

            {{-- ── Bandwidth chart ───────────────────────────────────────── --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-slate-800">Bandwidth Over Time</h3>
                </div>
                <div class="relative h-40"><canvas id="bandwidthChart"></canvas></div>
            </div>

            {{-- ── Heatmap ───────────────────────────────────────────────── --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-6">
                <h3 class="text-sm font-semibold text-slate-800 mb-1">Activity by Hour & Day</h3>
                <p class="text-xs text-slate-400 mb-4">When your visitors arrive, aggregated over the selected range (server local time)</p>
                <div class="overflow-x-auto">
                    <div class="min-w-[640px]">
                        {{-- Hour column headers --}}
                        <div class="flex items-center gap-0.5 mb-1">
                            <div class="w-10 shrink-0"></div>
                            <template x-for="h in 24" :key="'hh-' + h">
                                <div class="flex-1 text-[9px] text-slate-400 text-center font-medium" x-text="(h-1).toString().padStart(2,'0')"></div>
                            </template>
                        </div>
                        {{-- Day rows --}}
                        <template x-for="(row, dIdx) in heatmap" :key="'hrow-' + dIdx">
                            <div class="flex items-center gap-0.5 mb-0.5">
                                <div class="w-10 shrink-0 text-[10px] text-slate-500 font-medium text-right pr-1" x-text="['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][dIdx]"></div>
                                <template x-for="(count, hIdx) in (row || [])" :key="'hc-' + dIdx + '-' + hIdx">
                                    <div class="flex-1 h-5 rounded-[2px] transition"
                                         :class="heatmapBg(count)"
                                         :title="['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][dIdx] + ' ' + hIdx.toString().padStart(2,'0') + ':00 — ' + formatNum(count) + ' visits'"></div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-3 text-xs text-slate-400">
                    <span>Less</span>
                    <div class="w-4 h-4 rounded-[2px] bg-slate-100"></div>
                    <div class="w-4 h-4 rounded-[2px] bg-orange-200"></div>
                    <div class="w-4 h-4 rounded-[2px] bg-orange-400"></div>
                    <div class="w-4 h-4 rounded-[2px] bg-orange-600"></div>
                    <div class="w-4 h-4 rounded-[2px] bg-orange-800"></div>
                    <span>More</span>
                </div>
            </div>

            {{-- ── Sources + Devices donut row ───────────────────────────── --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <h3 class="text-sm font-semibold text-slate-800 mb-4">Traffic Sources</h3>
                    <div class="flex items-center gap-6">
                        <div class="relative w-32 h-32 shrink-0"><canvas id="sourcesChart"></canvas></div>
                        <div class="flex-1 space-y-2">
                            <template x-for="(s, i) in sources" :key="s.key">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full" :style="'background:' + sourceColor(s.key)"></span>
                                        <span class="text-slate-700" x-text="s.key"></span>
                                    </span>
                                    <span class="text-slate-500 text-xs font-medium" x-text="formatNum(s.value) + ' · ' + percent(s.value, totalSources) + '%'"></span>
                                </div>
                            </template>
                            <template x-if="sources.length === 0">
                                <p class="text-xs text-slate-400">No data yet</p>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <h3 class="text-sm font-semibold text-slate-800 mb-4">Device Type</h3>
                    <div class="flex items-center gap-6">
                        <div class="relative w-32 h-32 shrink-0"><canvas id="devicesChart"></canvas></div>
                        <div class="flex-1 space-y-2">
                            <template x-for="d in devices" :key="d.key">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full" :style="'background:' + deviceColor(d.key)"></span>
                                        <span class="text-slate-700" x-text="d.key"></span>
                                    </span>
                                    <span class="text-slate-500 text-xs font-medium" x-text="formatNum(d.value) + ' · ' + percent(d.value, totalDevices) + '%'"></span>
                                </div>
                            </template>
                            <template x-if="devices.length === 0">
                                <p class="text-xs text-slate-400">No data yet</p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Top Pages + Top Referrers ─────────────────────────────── --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-semibold text-slate-800">Top Pages</h3>
                        <span class="text-xs text-slate-400" x-text="topPages.length + ' items'"></span>
                    </div>
                    <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                        <template x-for="p in topPages" :key="p.key">
                            <div class="px-5 py-2.5">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="truncate text-slate-700 font-mono text-xs" x-text="p.key"></span>
                                    <span class="text-slate-500 text-xs font-medium ml-3 shrink-0" x-text="formatNum(p.value)"></span>
                                </div>
                                <div class="mt-1.5 h-1 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-orange-500 rounded-full" :style="'width:' + barWidth(p.value, topPages) + '%'"></div>
                                </div>
                            </div>
                        </template>
                        <template x-if="topPages.length === 0">
                            <div class="px-5 py-12 text-center text-slate-400 text-sm">No data yet</div>
                        </template>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h3 class="font-semibold text-slate-800">Top Referrers</h3>
                    </div>
                    <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                        <template x-for="r in topRefs" :key="r.key">
                            <div class="px-5 py-2.5 flex items-center justify-between text-sm">
                                <span class="truncate text-slate-700" x-text="r.key === 'direct' ? 'Direct / No referrer' : r.key"></span>
                                <span class="text-slate-500 text-xs font-medium ml-3 shrink-0" x-text="formatNum(r.value)"></span>
                            </div>
                        </template>
                        <template x-if="topRefs.length === 0">
                            <div class="px-5 py-12 text-center text-slate-400 text-sm">No data yet</div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- ── Countries + Top 404s ──────────────────────────────────── --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-semibold text-slate-800">Top Countries</h3>
                        <span x-show="!geoEnabled" class="text-xs text-amber-600 font-medium">GeoIP not configured</span>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <template x-for="c in countries" :key="c.key">
                            <div class="px-5 py-2.5 flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2.5">
                                    <span class="text-lg" x-text="flagEmoji(c.key)"></span>
                                    <span class="text-slate-700" x-text="countryName(c.key)"></span>
                                </span>
                                <span class="text-slate-500 text-xs font-medium" x-text="formatNum(c.value)"></span>
                            </div>
                        </template>
                        <template x-if="countries.length === 0">
                            <div class="px-5 py-12 text-center text-slate-400 text-sm">No data yet</div>
                        </template>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-semibold text-slate-800">Top 404 (broken links)</h3>
                        <span class="text-xs text-slate-400" x-text="notFound.length + ' items'"></span>
                    </div>
                    <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                        <template x-for="nf in notFound" :key="nf.key">
                            <div class="px-5 py-2.5 flex items-center justify-between text-sm">
                                <span class="truncate text-slate-700 font-mono text-xs" x-text="nf.key"></span>
                                <span class="text-red-600 text-xs font-semibold ml-3 shrink-0" x-text="formatNum(nf.value)"></span>
                            </div>
                        </template>
                        <template x-if="notFound.length === 0">
                            <div class="px-5 py-12 text-center text-slate-400 text-sm">No broken links — good!</div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- ── Browsers + OS ─────────────────────────────────────────── --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h3 class="font-semibold text-slate-800">Top Browsers</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <template x-for="b in browsers" :key="b.key">
                            <div class="px-5 py-2.5 flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2.5">
                                    <span x-html="browserIcon(b.key)"></span>
                                    <span class="text-slate-700" x-text="b.key"></span>
                                </span>
                                <span class="text-slate-500 text-xs font-medium" x-text="formatNum(b.value)"></span>
                            </div>
                        </template>
                        <template x-if="browsers.length === 0">
                            <div class="px-5 py-12 text-center text-slate-400 text-sm">No data yet</div>
                        </template>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h3 class="font-semibold text-slate-800">Operating Systems</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <template x-for="o in os" :key="o.key">
                            <div class="px-5 py-2.5 flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2.5">
                                    <span x-html="osIcon(o.key)"></span>
                                    <span class="text-slate-700" x-text="o.key"></span>
                                </span>
                                <span class="text-slate-500 text-xs font-medium" x-text="formatNum(o.value)"></span>
                            </div>
                        </template>
                        <template x-if="os.length === 0">
                            <div class="px-5 py-12 text-center text-slate-400 text-sm">No data yet</div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- ── Top IPs ───────────────────────────────────────────────── --}}
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden mb-6">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-slate-800">Top IPs</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Useful for spotting abusive clients — a single IP with thousands of hits usually means a bot or misconfigured scraper</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                    <template x-for="ip in topIps" :key="ip.key">
                        <div class="px-5 py-2.5 flex items-center justify-between text-sm">
                            <span class="font-mono text-xs text-slate-700" x-text="ip.key"></span>
                            <span class="text-slate-500 text-xs font-medium" x-text="formatNum(ip.value) + ' hits'"></span>
                        </div>
                    </template>
                    <template x-if="topIps.length === 0">
                        <div class="px-5 py-12 text-center text-slate-400 text-sm">No data yet</div>
                    </template>
                </div>
            </div>

            {{-- ── HTTP Status Codes ─────────────────────────────────────── --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-6">
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    HTTP Status Codes
                </h3>
                <template x-if="Object.keys(statusCodes).length === 0">
                    <p class="text-sm text-slate-400 text-center py-6">No data yet</p>
                </template>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3" x-show="Object.keys(statusCodes).length > 0">
                    <template x-for="code in sortedStatusCodes" :key="code">
                        <div class="rounded-xl p-4 text-center" :class="statusBg(code)">
                            <div class="text-2xl font-extrabold" :class="statusText(code)" x-text="code"></div>
                            <div class="text-xs text-slate-600 mt-1 font-semibold" x-text="formatNum(statusCodes[code])"></div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ── Privacy footer ────────────────────────────────────────── --}}
            <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm text-blue-800 flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <div>
                    <strong>Privacy-first analytics.</strong>
                    Visitor data is parsed directly from your Nginx access logs on this server. No tracking script is loaded on your site, no cookies are set, and no data leaves your server. Top IPs are only displayed here for abuse detection — they are not shared. Fully GDPR-friendly with no consent banner needed.
                </div>
            </div>

        @endif

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
    function analyticsApp(domains) {
        // Chart instances live OUTSIDE the Alpine-reactive object so Chart.js's
        // internal state doesn't get wrapped in proxies (breaks rendering).
        let visitsChart = null;
        let bandwidthChart = null;
        let sourcesChart = null;
        let devicesChart = null;
        let liveChart = null;
        let liveTimer = null;

        const SOURCE_COLORS = {
            'Direct':   '#64748b',
            'Search':   '#10b981',
            'Social':   '#8b5cf6',
            'Referral': '#f97316',
        };
        const DEVICE_COLORS = {
            'Desktop': '#3b82f6',
            'Mobile':  '#10b981',
            'Tablet':  '#f59e0b',
            'Bot':     '#94a3b8',
            'Unknown': '#cbd5e1',
        };

        return {
            domains: domains,
            domainId: domains[0]?.id || null,
            range: '24h',
            compare: false,
            ranges: [
                { key: '24h', label: '24 Hours' },
                { key: '7d',  label: '7 Days'  },
                { key: '30d', label: '30 Days' },
                { key: '90d', label: '90 Days' },
            ],
            loading: false,
            summary: { visits: 0, unique: 0, bandwidth: 0, bot_percent: 0 },
            summaryPrev: {},
            timeseries: [],
            timeseriesPrev: [],
            topPages: [], topRefs: [], countries: [], browsers: [], os: [],
            devices: [], sources: [], topIps: [], notFound: [],
            statusCodes: {},
            heatmap: [[],[],[],[],[],[],[]],
            geoEnabled: false,
            live: { active_now: 0, visits_last_5: 0, per_minute: [], recent_pages: [], recent_countries: [] },

            init() {
                this.initCharts();
                this.reload();
                this.reloadLive();
                // Poll live every 30s.
                liveTimer = setInterval(() => this.reloadLive(), 30000);
            },

            setRange(r) {
                this.range = r;
                this.reload();
            },

            async reload() {
                if (! this.domainId) return;
                this.loading = true;
                try {
                    const body = { domain_id: this.domainId, range: this.range };
                    const resp = await fetch('{{ route("user.analytics.query") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(body),
                    });
                    const data = await resp.json();
                    if (data.error) { console.error(data.error); return; }

                    this.summary     = data.summary || this.summary;
                    this.timeseries  = data.timeseries || [];
                    this.topPages    = data.top_pages || [];
                    this.topRefs     = data.top_referrers || [];
                    this.countries   = data.top_countries || [];
                    this.browsers    = data.top_browsers || [];
                    this.os          = data.top_os || [];
                    this.devices     = data.top_devices || [];
                    this.sources     = data.top_sources || [];
                    this.topIps      = data.top_ips || [];
                    this.notFound    = data.top_404s || [];
                    this.statusCodes = data.status_codes || {};
                    this.heatmap     = data.heatmap || [[],[],[],[],[],[],[]];
                    this.geoEnabled  = data.geo_enabled ?? true;

                    if (this.compare) {
                        await this.reloadPrevious();
                    } else {
                        this.summaryPrev = {};
                        this.timeseriesPrev = [];
                    }

                    this.updateCharts();
                } catch (e) { console.error(e); }
                finally { this.loading = false; }
            },

            // Fetches the equivalent range immediately before the current one
            // so the chart can overlay "previous period" as a dashed line.
            async reloadPrevious() {
                const rangeHours = { '24h': 24, '7d': 168, '30d': 720, '90d': 2160 }[this.range] || 24;
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
                            offset_hours: rangeHours,
                        }),
                    });
                    const data = await resp.json();
                    if (data.error) { this.summaryPrev = {}; this.timeseriesPrev = []; return; }
                    this.summaryPrev    = data.summary || {};
                    this.timeseriesPrev = data.timeseries || [];
                } catch (e) {
                    this.summaryPrev    = {};
                    this.timeseriesPrev = [];
                }
            },

            async reloadLive() {
                if (! this.domainId) return;
                try {
                    const resp = await fetch('{{ route("user.analytics.live") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ domain_id: this.domainId }),
                    });
                    const data = await resp.json();
                    if (data.error) return;
                    this.live = data;
                    this.updateLiveChart();
                } catch (e) {}
            },

            initCharts() {
                // Visits chart (main)
                const ctx = document.getElementById('visitsChart');
                if (ctx) {
                    const existing = Chart.getChart(ctx);
                    if (existing) existing.destroy();
                    visitsChart = new Chart(ctx, {
                        type: 'line',
                        data: { labels: [], datasets: [
                            { label: 'Visits', data: [], borderColor: '#f97316', backgroundColor: 'rgba(249, 115, 22, 0.12)', fill: true, tension: 0.35 },
                            { label: 'Unique', data: [], borderColor: '#3b82f6', backgroundColor: 'transparent', fill: false, tension: 0.35, borderDash: [4, 4] },
                            { label: 'Previous', data: [], borderColor: '#94a3b8', backgroundColor: 'transparent', fill: false, tension: 0.35, borderDash: [2, 3], hidden: true },
                        ]},
                        options: {
                            responsive: true, maintainAspectRatio: false, animation: false,
                            scales: {
                                x: { display: true, grid: { display: false }, ticks: { font: { size: 10 } } },
                                y: { beginAtZero: true, ticks: { font: { size: 10 } } },
                            },
                            plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
                            elements: { point: { radius: 2.5, hoverRadius: 5 }, line: { borderWidth: 2 } },
                        },
                    });
                }

                // Bandwidth chart
                const bctx = document.getElementById('bandwidthChart');
                if (bctx) {
                    const existing = Chart.getChart(bctx);
                    if (existing) existing.destroy();
                    bandwidthChart = new Chart(bctx, {
                        type: 'bar',
                        data: { labels: [], datasets: [{ label: 'Bytes', data: [], backgroundColor: 'rgba(16, 185, 129, 0.6)', borderRadius: 3 }]},
                        options: {
                            responsive: true, maintainAspectRatio: false, animation: false,
                            scales: {
                                x: { display: true, grid: { display: false }, ticks: { font: { size: 10 } } },
                                y: { beginAtZero: true, ticks: { font: { size: 10 }, callback: (v) => formatBytesShort(v) } },
                            },
                            plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => formatBytesShort(c.parsed.y) } } },
                        },
                    });
                }

                // Sources donut
                const sctx = document.getElementById('sourcesChart');
                if (sctx) {
                    const existing = Chart.getChart(sctx);
                    if (existing) existing.destroy();
                    sourcesChart = new Chart(sctx, {
                        type: 'doughnut',
                        data: { labels: [], datasets: [{ data: [], backgroundColor: [] }] },
                        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } },
                    });
                }

                // Devices donut
                const dctx = document.getElementById('devicesChart');
                if (dctx) {
                    const existing = Chart.getChart(dctx);
                    if (existing) existing.destroy();
                    devicesChart = new Chart(dctx, {
                        type: 'doughnut',
                        data: { labels: [], datasets: [{ data: [], backgroundColor: [] }] },
                        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } },
                    });
                }

                // Live mini-chart
                const lctx = document.getElementById('liveChart');
                if (lctx) {
                    const existing = Chart.getChart(lctx);
                    if (existing) existing.destroy();
                    liveChart = new Chart(lctx, {
                        type: 'bar',
                        data: { labels: [], datasets: [{ data: [], backgroundColor: 'rgba(16, 185, 129, 0.8)', borderRadius: 1.5 }]},
                        options: {
                            responsive: true, maintainAspectRatio: false, animation: false,
                            scales: { x: { display: false }, y: { display: false, beginAtZero: true } },
                            plugins: { legend: { display: false }, tooltip: { enabled: false } },
                        },
                    });
                }
            },

            updateCharts() {
                if (visitsChart) {
                    const labels = this.timeseries.map(p => this.formatLabel(p.t));
                    visitsChart.data.labels = labels;
                    visitsChart.data.datasets[0].data = this.timeseries.map(p => p.v);
                    visitsChart.data.datasets[1].data = this.timeseries.map(p => p.u);
                    visitsChart.data.datasets[2].data = this.timeseriesPrev.map(p => p.v);
                    visitsChart.data.datasets[2].hidden = !this.compare;
                    visitsChart.update('none');
                }
                if (bandwidthChart) {
                    bandwidthChart.data.labels  = this.timeseries.map(p => this.formatLabel(p.t));
                    bandwidthChart.data.datasets[0].data = this.timeseries.map(p => p.b || 0);
                    bandwidthChart.update('none');
                }
                if (sourcesChart) {
                    sourcesChart.data.labels = this.sources.map(s => s.key);
                    sourcesChart.data.datasets[0].data = this.sources.map(s => s.value);
                    sourcesChart.data.datasets[0].backgroundColor = this.sources.map(s => SOURCE_COLORS[s.key] || '#cbd5e1');
                    sourcesChart.update('none');
                }
                if (devicesChart) {
                    devicesChart.data.labels = this.devices.map(d => d.key);
                    devicesChart.data.datasets[0].data = this.devices.map(d => d.value);
                    devicesChart.data.datasets[0].backgroundColor = this.devices.map(d => DEVICE_COLORS[d.key] || '#cbd5e1');
                    devicesChart.update('none');
                }
            },

            updateLiveChart() {
                if (! liveChart) return;
                const pm = this.live.per_minute || [];
                liveChart.data.labels = pm.map(() => '');
                liveChart.data.datasets[0].data = pm.map(p => p.v);
                liveChart.update('none');
            },

            formatLabel(t) {
                const d = new Date(t * 1000);
                if (this.range === '24h') return String(d.getHours()).padStart(2, '0') + ':00';
                return (d.getMonth() + 1) + '/' + d.getDate();
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
            percent(v, total) {
                if (! total) return '0.0';
                return ((v / total) * 100).toFixed(1);
            },
            deltaText(cur, prev) {
                if (!prev) return '';
                const pct = ((cur - prev) / prev) * 100;
                const sign = pct >= 0 ? '+' : '';
                return sign + pct.toFixed(1) + '% vs prev';
            },
            deltaClass(cur, prev) {
                if (!prev) return 'text-slate-400';
                return cur >= prev ? 'text-emerald-600' : 'text-red-500';
            },
            sourceColor(key) { return SOURCE_COLORS[key] || '#cbd5e1'; },
            deviceColor(key) { return DEVICE_COLORS[key] || '#cbd5e1'; },

            heatmapBg(count) {
                if (!count) return 'bg-slate-100';
                // Scale against the max in the heatmap.
                const max = this.heatmapMax || 1;
                const pct = count / max;
                if (pct > 0.75) return 'bg-orange-800';
                if (pct > 0.50) return 'bg-orange-600';
                if (pct > 0.25) return 'bg-orange-400';
                return 'bg-orange-200';
            },

            get heatmapMax() {
                let m = 0;
                for (const row of this.heatmap) { for (const v of (row || [])) { if (v > m) m = v; } }
                return m;
            },
            get totalSources() { return this.sources.reduce((s, x) => s + x.value, 0); },
            get totalDevices() { return this.devices.reduce((s, x) => s + x.value, 0); },

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
                    Linux:   '<svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#FCC624" d="M12 2a10 10 0 100 20 10 10 0 000-20zm-2 7a1 1 0 110 2 1 1 0 010-2zm4 0a1 1 0 110 2 1 1 0 010-2z"/></svg>',
                    Android: '<svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#3DDC84" d="M17.5 10.5h-11a1 1 0 00-1 1v6a2 2 0 002 2h1v2a1 1 0 102 0v-2h3v2a1 1 0 102 0v-2h1a2 2 0 002-2v-6a1 1 0 00-1-1zM5 9.5a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zm14 0a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zM7 8.5l-1.2-2.1a.4.4 0 01.7-.4l1.2 2.1c.9-.4 1.9-.6 3.3-.6s2.4.2 3.3.6l1.2-2.1a.4.4 0 11.7.4L15 8.5c1.5.8 2.5 2.2 2.5 3.8H6.5c0-1.6 1-3 2.5-3.8zm2.5-1c-.3 0-.5-.2-.5-.5s.2-.5.5-.5.5.2.5.5-.2.5-.5.5zm5 0c-.3 0-.5-.2-.5-.5s.2-.5.5-.5.5.2.5.5-.2.5-.5.5z"/></svg>',
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

    function formatBytesShort(bytes) {
        if (! bytes) return '0';
        const units = ['B', 'K', 'M', 'G', 'T'];
        let i = 0;
        while (bytes >= 1024 && i < units.length - 1) { bytes /= 1024; i++; }
        return bytes.toFixed(1) + units[i];
    }
    </script>
</x-user-layout>
