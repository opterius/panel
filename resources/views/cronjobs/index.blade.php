<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Cron Jobs</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm">
        <div class="flex justify-between items-center px-6 py-5 border-b border-gray-100">
            <div>
                <h3 class="text-base font-semibold text-gray-800">All Cron Jobs</h3>
                <p class="text-sm text-gray-500 mt-1">Schedule commands to run automatically.</p>
            </div>
            <a href="{{ route('cronjobs.create') }}" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                Add Cron Job
            </a>
        </div>

        @if($cronJobs->isEmpty())
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <h3 class="mt-4 text-base font-medium text-gray-700">No cron jobs yet</h3>
                <p class="mt-2 text-sm text-gray-500">Schedule your first automated task.</p>
                <a href="{{ route('cronjobs.create') }}" class="mt-6 inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    Add Cron Job
                </a>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($cronJobs as $cron)
                    <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                @if($cron->enabled) bg-teal-100 @else bg-gray-100 @endif">
                                <svg class="w-5 h-5 @if($cron->enabled) text-teal-600 @else text-gray-400 @endif"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-mono text-gray-800">{{ $cron->command }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">{{ $cron->schedule }}</span>
                                    &middot; {{ $cron->account->username }}
                                    &middot; {{ $cron->server->name }}
                                    @if($cron->last_run_at)
                                        &middot; Last run: {{ $cron->last_run_at->diffForHumans() }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                @if($cron->enabled) bg-green-100 text-green-700 @else bg-gray-100 text-gray-500 @endif">
                                {{ $cron->enabled ? 'Active' : 'Disabled' }}
                            </span>

                            <form action="{{ route('cronjobs.toggle', $cron) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-sm font-medium transition
                                    @if($cron->enabled) text-yellow-600 hover:text-yellow-800
                                    @else text-green-600 hover:text-green-800 @endif">
                                    {{ $cron->enabled ? 'Disable' : 'Enable' }}
                                </button>
                            </form>

                            <form action="{{ route('cronjobs.destroy', $cron) }}" method="POST"
                                  onsubmit="return confirm('Delete this cron job?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600 transition" title="Delete">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
