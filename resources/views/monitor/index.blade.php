<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Server Monitor</h2>
    </x-slot>

    <!-- Server Selector -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5">
            <form method="GET" action="{{ route('admin.monitor.index') }}" class="flex items-end gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Server</label>
                    <select name="server_id"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($servers as $server)
                            <option value="{{ $server->id }}" @selected($selectedServer && $selectedServer->id === $server->id)>
                                {{ $server->name }} ({{ $server->ip_address }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    Monitor
                </button>
            </form>
        </div>
    </div>

    @if($selectedServer)
        <div x-data="serverMonitor({{ $selectedServer->id }})" x-init="startPolling()">
            <!-- Live Indicator -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    </span>
                    <span class="text-sm font-medium text-green-600">Live</span>
                </div>
                <span class="text-xs text-gray-400" x-text="'Updated ' + lastUpdate">--</span>
            </div>

            <!-- Live Stats Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-medium text-gray-400 uppercase">CPU</div>
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" /></svg>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-gray-900" x-text="latest.cpu_percent.toFixed(1) + '%'">--</div>
                    <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                        <div class="bg-indigo-500 h-1.5 rounded-full transition-all duration-500" :style="'width:' + Math.min(latest.cpu_percent, 100) + '%'"></div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-5">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-medium text-gray-400 uppercase">Memory</div>
                        <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-gray-900" x-text="latest.mem_used_mb + ' / ' + latest.mem_total_mb + ' MB'">--</div>
                    <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                        <div class="bg-green-500 h-1.5 rounded-full transition-all duration-500" :style="'width:' + Math.min(latest.mem_percent, 100) + '%'"></div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-5">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-medium text-gray-400 uppercase">Disk</div>
                        <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" /></svg>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-gray-900" x-text="latest.disk_used_gb + ' / ' + latest.disk_total_gb + ' GB'">--</div>
                    <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                        <div class="bg-amber-500 h-1.5 rounded-full transition-all duration-500" :style="'width:' + Math.min(latest.disk_percent, 100) + '%'"></div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-5">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-medium text-gray-400 uppercase">Load Average</div>
                        <div class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-gray-900" x-text="latest.load_avg_1.toFixed(2)">--</div>
                    <div class="mt-1 text-xs text-gray-400" x-text="'5m: ' + latest.load_avg_5.toFixed(2) + ' / 15m: ' + latest.load_avg_15.toFixed(2)"></div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">CPU Usage</h3>
                    <div class="relative h-48"><canvas id="cpuChart"></canvas></div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Memory Usage</h3>
                    <div class="relative h-48"><canvas id="memChart"></canvas></div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Network I/O (KB/s)</h3>
                    <div class="relative h-48"><canvas id="netChart"></canvas></div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Load Average</h3>
                    <div class="relative h-48"><canvas id="loadChart"></canvas></div>
                </div>
            </div>

            <!-- Top Processes -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Top Processes</h3>
                </div>
                <div class="hidden sm:grid grid-cols-12 px-6 py-2 text-xs font-medium text-gray-500 uppercase tracking-wide border-b border-gray-200 bg-white">
                    <div class="col-span-2">User</div>
                    <div class="col-span-1">PID</div>
                    <div class="col-span-1">CPU%</div>
                    <div class="col-span-1">MEM%</div>
                    <div class="col-span-7">Command</div>
                </div>
                <div class="divide-y divide-gray-50">
                    <template x-for="proc in processes" :key="proc.pid">
                        <div class="grid grid-cols-12 items-center px-6 py-2 text-sm">
                            <div class="col-span-2 font-medium text-gray-800" x-text="proc.user"></div>
                            <div class="col-span-1 text-gray-500 font-mono text-xs" x-text="proc.pid"></div>
                            <div class="col-span-1" :class="proc.cpu_percent > 50 ? 'text-red-600 font-bold' : 'text-gray-600'" x-text="proc.cpu_percent.toFixed(1)"></div>
                            <div class="col-span-1 text-gray-600" x-text="proc.mem_percent.toFixed(1)"></div>
                            <div class="col-span-7 text-gray-500 font-mono text-xs truncate" x-text="proc.command"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
        <script>
            function serverMonitor(serverId) {
                return {
                    serverId: serverId,
                    latest: { cpu_percent: 0, mem_used_mb: 0, mem_total_mb: 0, mem_percent: 0, disk_used_gb: 0, disk_total_gb: 0, disk_percent: 0, load_avg_1: 0, load_avg_5: 0, load_avg_15: 0, network_in_kb: 0, network_out_kb: 0 },
                    processes: [],
                    lastUpdate: 'never',
                    charts: {},

                    startPolling() {
                        this.initCharts();
                        this.fetchMetrics();
                        this.fetchProcesses();
                        setInterval(() => this.fetchMetrics(), 5000);
                        setInterval(() => this.fetchProcesses(), 10000);
                    },

                    async fetchMetrics() {
                        try {
                            const resp = await fetch(`/admin/monitor/realtime?server_id=${this.serverId}`);
                            const data = await resp.json();
                            if (data.snapshots && data.snapshots.length > 0) {
                                this.latest = data.snapshots[data.snapshots.length - 1];
                                this.lastUpdate = new Date().toLocaleTimeString();
                                this.updateCharts(data.snapshots);
                            }
                        } catch (e) {}
                    },

                    async fetchProcesses() {
                        try {
                            const resp = await fetch(`/admin/monitor/processes?server_id=${this.serverId}`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                            const data = await resp.json();
                            if (data.processes) this.processes = data.processes;
                        } catch (e) {}
                    },

                    initCharts() {
                        const opts = {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            scales: {
                                x: { display: false },
                                y: { beginAtZero: true, max: 100 }
                            },
                            plugins: { legend: { display: false } },
                            elements: { point: { radius: 0 }, line: { borderWidth: 2, tension: 0.3 } }
                        };

                        this.charts.cpu = new Chart(document.getElementById('cpuChart'), {
                            type: 'line',
                            data: { labels: [], datasets: [{ data: [], borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', fill: true }] },
                            options: { ...opts }
                        });

                        this.charts.mem = new Chart(document.getElementById('memChart'), {
                            type: 'line',
                            data: { labels: [], datasets: [{ data: [], borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.1)', fill: true }] },
                            options: { ...opts }
                        });

                        this.charts.net = new Chart(document.getElementById('netChart'), {
                            type: 'line',
                            data: { labels: [], datasets: [
                                { label: 'In', data: [], borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', fill: true },
                                { label: 'Out', data: [], borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.1)', fill: true }
                            ]},
                            options: { ...opts, scales: { x: { display: false }, y: { beginAtZero: true } }, plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 12, font: { size: 11 } } } } }
                        });

                        this.charts.load = new Chart(document.getElementById('loadChart'), {
                            type: 'line',
                            data: { labels: [], datasets: [
                                { label: '1m', data: [], borderColor: '#ef4444' },
                                { label: '5m', data: [], borderColor: '#f59e0b' },
                                { label: '15m', data: [], borderColor: '#22c55e' }
                            ]},
                            options: { ...opts, scales: { x: { display: false }, y: { beginAtZero: true } }, plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 12, font: { size: 11 } } } } }
                        });
                    },

                    updateCharts(snapshots) {
                        const labels = snapshots.map((_, i) => i);
                        const cpu = snapshots.map(s => s.cpu_percent);
                        const mem = snapshots.map(s => s.mem_percent);
                        const netIn = snapshots.map(s => s.network_in_kb);
                        const netOut = snapshots.map(s => s.network_out_kb);
                        const load1 = snapshots.map(s => s.load_avg_1);
                        const load5 = snapshots.map(s => s.load_avg_5);
                        const load15 = snapshots.map(s => s.load_avg_15);

                        this.charts.cpu.data.labels = labels;
                        this.charts.cpu.data.datasets[0].data = cpu;
                        this.charts.cpu.update('none');

                        this.charts.mem.data.labels = labels;
                        this.charts.mem.data.datasets[0].data = mem;
                        this.charts.mem.update('none');

                        this.charts.net.data.labels = labels;
                        this.charts.net.data.datasets[0].data = netIn;
                        this.charts.net.data.datasets[1].data = netOut;
                        this.charts.net.update('none');

                        this.charts.load.data.labels = labels;
                        this.charts.load.data.datasets[0].data = load1;
                        this.charts.load.data.datasets[1].data = load5;
                        this.charts.load.data.datasets[2].data = load15;
                        this.charts.load.update('none');
                    }
                };
            }
        </script>
    @endif
</x-admin-layout>
