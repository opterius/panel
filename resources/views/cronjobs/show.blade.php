<x-user-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('user.cronjobs.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">{{ $cronJob->description ?: $cronJob->command }}</h2>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-6">

        {{-- Job summary card --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-gray-800">Job Details</h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $cronJob->account->username }} on {{ $cronJob->account->server->name }}</p>
                </div>
                <div class="flex items-center gap-2">
                    @if ($cronJob->enabled)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Enabled
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Disabled
                        </span>
                    @endif
                </div>
            </div>
            <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-3 gap-5 text-sm">
                <div>
                    <div class="text-xs text-gray-500 uppercase font-semibold mb-1">Schedule</div>
                    <div class="font-mono text-gray-800">{{ $cronJob->schedule }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ \App\Support\CronSchedule::describe($cronJob->schedule) }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase font-semibold mb-1">Last run</div>
                    <div class="text-gray-800">
                        {{ $cronJob->last_run_at ? $cronJob->last_run_at->diffForHumans() : 'Never' }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase font-semibold mb-1">Total runs</div>
                    <div class="text-gray-800">{{ $cronJob->history()->count() }}</div>
                </div>
            </div>
            <div class="px-6 pb-5">
                <div class="text-xs text-gray-500 uppercase font-semibold mb-1">Command</div>
                <pre class="bg-slate-900 text-slate-100 rounded-lg p-3 text-xs overflow-x-auto"><code>{{ $cronJob->command }}</code></pre>
            </div>
        </div>

        {{-- Run history --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Recent Runs</h3>
                <p class="text-xs text-gray-500 mt-0.5">Last 20 executions with output and exit codes.</p>
            </div>

            @if ($history->isEmpty())
                <div class="p-12 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm text-gray-500">No runs recorded yet.</p>
                    <p class="text-xs text-gray-400 mt-1">Wait for the next scheduled execution. Output will appear here automatically.</p>
                </div>
            @else
                <div class="divide-y divide-gray-100" x-data="{ open: null }">
                    @foreach ($history as $run)
                        <div>
                            <button type="button" @click="open = (open === {{ $run->id }} ? null : {{ $run->id }})"
                                    class="w-full px-6 py-3 flex items-center justify-between hover:bg-gray-50 text-left">
                                <div class="flex items-center gap-3">
                                    @if ($run->isSuccess())
                                        <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                                    @else
                                        <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                                    @endif
                                    <div>
                                        <div class="text-sm font-semibold text-gray-800">
                                            {{ $run->started_at->format('Y-m-d H:i:s') }}
                                            <span class="text-xs text-gray-500 font-normal">· {{ $run->started_at->diffForHumans() }}</span>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            Exit code <span class="font-mono {{ $run->isSuccess() ? 'text-green-700' : 'text-red-700' }}">{{ $run->exit_code }}</span>
                                            · {{ number_format($run->duration_ms) }} ms
                                            @if ($run->stdout || $run->stderr)
                                                · has output
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-gray-400" :class="open === {{ $run->id }} && 'rotate-180'" style="transition: transform 200ms" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <div x-show="open === {{ $run->id }}" x-cloak class="px-6 pb-4 space-y-3">
                                @if ($run->stdout)
                                    <div>
                                        <div class="text-xs font-semibold text-gray-500 uppercase mb-1">stdout</div>
                                        <pre class="bg-slate-900 text-slate-100 rounded-lg p-3 text-xs overflow-x-auto max-h-64"><code>{{ $run->stdout }}</code></pre>
                                    </div>
                                @endif
                                @if ($run->stderr)
                                    <div>
                                        <div class="text-xs font-semibold text-red-500 uppercase mb-1">stderr</div>
                                        <pre class="bg-red-950 text-red-100 rounded-lg p-3 text-xs overflow-x-auto max-h-64"><code>{{ $run->stderr }}</code></pre>
                                    </div>
                                @endif
                                @if (! $run->stdout && ! $run->stderr)
                                    <p class="text-xs text-gray-500 italic">No output.</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</x-user-layout>
