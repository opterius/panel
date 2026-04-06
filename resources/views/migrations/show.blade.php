<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.migrations.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">Migration: {{ $migration->main_domain }}</h2>
        </div>
    </x-slot>

    <div class="max-w-3xl space-y-6"
         @if(in_array($migration->status, ['running', 'parsing', 'pending', 'previewing']))
            x-data="{ poll() { fetch('{{ route('admin.migrations.status', $migration) }}').then(r => r.json()).then(d => { this.status = d.status; this.progress = d.progress; this.step = d.current_step; this.result = d.result; this.error = d.error; if (['completed','partial','failed'].includes(d.status)) clearInterval(this.interval); }); }, status: '{{ $migration->status }}', progress: {{ $migration->progress }}, step: '{{ $migration->current_step ?? '' }}', result: null, error: null, interval: null }"
            x-init="interval = setInterval(() => poll(), 2000)"
         @else
            x-data="{ status: '{{ $migration->status }}', progress: {{ $migration->progress }}, step: '', result: {{ json_encode($migration->result) }}, error: {{ json_encode($migration->error) }} }"
         @endif
    >

        {{-- Progress Bar --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-800">Migration Progress</h3>
                <span class="text-sm font-medium" :class="{ 'text-blue-600': status === 'running', 'text-green-600': status === 'completed', 'text-amber-600': status === 'partial', 'text-red-600': status === 'failed' }"
                    x-text="status.charAt(0).toUpperCase() + status.slice(1)"></span>
            </div>

            <div class="w-full bg-gray-100 rounded-full h-3 mb-3">
                <div class="h-3 rounded-full transition-all duration-500"
                    :class="{ 'bg-indigo-500': status === 'running', 'bg-green-500': status === 'completed', 'bg-amber-500': status === 'partial', 'bg-red-500': status === 'failed' }"
                    :style="'width: ' + progress + '%'"></div>
            </div>

            <p class="text-sm text-gray-500" x-show="step" x-text="step"></p>
            <p class="text-sm text-red-600" x-show="error" x-text="error"></p>

            <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-xs text-gray-400 block">Domain</span>
                    <span class="font-medium text-gray-800">{{ $migration->main_domain }}</span>
                </div>
                <div>
                    <span class="text-xs text-gray-400 block">Username</span>
                    <span class="font-medium text-gray-800 font-mono">{{ $migration->target_username }}</span>
                </div>
                <div>
                    <span class="text-xs text-gray-400 block">Server</span>
                    <span class="font-medium text-gray-800">{{ $migration->server->name }}</span>
                </div>
                <div>
                    <span class="text-xs text-gray-400 block">Started</span>
                    <span class="font-medium text-gray-800">{{ $migration->started_at?->diffForHumans() ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- Results per component --}}
        <template x-if="result">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Results</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @php
                        $components = [
                            'account' => ['label' => 'Account', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                            'files' => ['label' => 'Files', 'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'],
                            'databases' => ['label' => 'Databases', 'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4'],
                            'email' => ['label' => 'Email', 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                            'dns' => ['label' => 'DNS Zones', 'icon' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2'],
                            'ssl' => ['label' => 'SSL', 'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
                            'cron' => ['label' => 'Cron Jobs', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ];
                    @endphp

                    @foreach($components as $key => $comp)
                        <div class="px-6 py-3 flex items-center justify-between" x-show="result?.{{ $key }}">
                            <div class="flex items-center space-x-3">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $comp['icon'] }}" /></svg>
                                <span class="text-sm font-medium text-gray-700">{{ $comp['label'] }}</span>
                            </div>
                            <div>
                                <template x-if="result?.{{ $key }}?.status === 'success'">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        Success
                                    </span>
                                </template>
                                <template x-if="result?.{{ $key }}?.status === 'failed'">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Failed</span>
                                </template>
                                <template x-if="result?.{{ $key }}?.status === 'partial'">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Partial</span>
                                </template>
                                <template x-if="result?.{{ $key }}?.status === 'skipped'">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Skipped</span>
                                </template>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </template>

        {{-- Completed actions --}}
        <template x-if="status === 'completed' || status === 'partial'">
            <div class="flex items-center space-x-3">
                @if($migration->account_id)
                    <a href="{{ route('admin.accounts.show', $migration->account_id) }}"
                       class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        View Account
                    </a>
                @endif
                <a href="{{ route('admin.migrations.index') }}"
                   class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Back to Migrations
                </a>
            </div>
        </template>
    </div>
</x-admin-layout>
