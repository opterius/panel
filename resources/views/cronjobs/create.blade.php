<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('cronjobs.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">Add Cron Job</h2>
        </div>
    </x-slot>

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if($accounts->isEmpty())
        <div class="max-w-2xl">
            <div class="bg-white rounded-xl shadow-sm p-6 text-center py-16">
                <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                <h3 class="mt-4 text-base font-medium text-gray-700">No accounts available</h3>
                <p class="mt-2 text-sm text-gray-500">You need to create an account before adding a cron job.</p>
            </div>
        </div>
    @else
        <form action="{{ route('cronjobs.store') }}" method="POST"
              x-data="{
                  preset: 'custom',
                  minute: '{{ old('minute', '*') }}',
                  hour: '{{ old('hour', '*') }}',
                  day: '{{ old('day', '*') }}',
                  month: '{{ old('month', '*') }}',
                  weekday: '{{ old('weekday', '*') }}',
                  command: '{{ old('command') }}',
                  setPreset(p) {
                      this.preset = p;
                      switch(p) {
                          case 'every_minute': this.minute='*'; this.hour='*'; this.day='*'; this.month='*'; this.weekday='*'; break;
                          case 'every_5min': this.minute='*/5'; this.hour='*'; this.day='*'; this.month='*'; this.weekday='*'; break;
                          case 'every_15min': this.minute='*/15'; this.hour='*'; this.day='*'; this.month='*'; this.weekday='*'; break;
                          case 'every_30min': this.minute='*/30'; this.hour='*'; this.day='*'; this.month='*'; this.weekday='*'; break;
                          case 'hourly': this.minute='0'; this.hour='*'; this.day='*'; this.month='*'; this.weekday='*'; break;
                          case 'daily': this.minute='0'; this.hour='0'; this.day='*'; this.month='*'; this.weekday='*'; break;
                          case 'weekly': this.minute='0'; this.hour='0'; this.day='*'; this.month='*'; this.weekday='0'; break;
                          case 'monthly': this.minute='0'; this.hour='0'; this.day='1'; this.month='*'; this.weekday='*'; break;
                      }
                  }
              }">
            @csrf

            <div class="max-w-2xl space-y-6">

                {{-- Account --}}
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600">1</span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-800">Account</h3>
                                <p class="text-sm text-gray-500">The cron job runs as this system user.</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-5">
                        <select name="account_id"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" @selected(old('account_id') == $account->id)>
                                    {{ $account->username }} ({{ $account->server->name }})
                                </option>
                            @endforeach
                        </select>
                        @error('account_id')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Schedule --}}
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600">2</span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-800">Schedule</h3>
                                <p class="text-sm text-gray-500">How often should this job run?</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-5 space-y-5">
                        {{-- Presets --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quick presets</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach([
                                    'every_minute' => 'Every minute',
                                    'every_5min' => 'Every 5 min',
                                    'every_15min' => 'Every 15 min',
                                    'every_30min' => 'Every 30 min',
                                    'hourly' => 'Hourly',
                                    'daily' => 'Daily',
                                    'weekly' => 'Weekly',
                                    'monthly' => 'Monthly',
                                ] as $key => $label)
                                    <button type="button" @click="setPreset('{{ $key }}')"
                                        class="px-3 py-1.5 text-xs font-medium rounded-lg border transition"
                                        :class="preset === '{{ $key }}' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Custom fields --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cron expression</label>
                            <div class="grid grid-cols-5 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1 text-center">Minute</label>
                                    <input type="text" name="minute" x-model="minute" @input="preset='custom'"
                                        class="w-full text-center rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1 text-center">Hour</label>
                                    <input type="text" name="hour" x-model="hour" @input="preset='custom'"
                                        class="w-full text-center rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1 text-center">Day</label>
                                    <input type="text" name="day" x-model="day" @input="preset='custom'"
                                        class="w-full text-center rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1 text-center">Month</label>
                                    <input type="text" name="month" x-model="month" @input="preset='custom'"
                                        class="w-full text-center rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1 text-center">Weekday</label>
                                    <input type="text" name="weekday" x-model="weekday" @input="preset='custom'"
                                        class="w-full text-center rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-gray-400 font-mono text-center" x-text="minute + ' ' + hour + ' ' + day + ' ' + month + ' ' + weekday"></p>
                        </div>
                    </div>
                </div>

                {{-- Command --}}
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600">3</span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-800">Command</h3>
                                <p class="text-sm text-gray-500">The command to execute.</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-5">
                        <input type="text" name="command" x-model="command"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="e.g. /usr/bin/php /home/user/domain.com/public_html/artisan schedule:run">
                        <p class="mt-1.5 text-xs text-gray-400">Use full paths for commands and scripts.</p>
                        @error('command')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center space-x-3">
                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        Add Cron Job
                    </button>
                    <a href="{{ route('cronjobs.index') }}" class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </a>
                </div>
            </div>
        </form>
    @endif
</x-app-layout>
