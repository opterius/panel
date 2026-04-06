<x-user-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('user.laravel.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">Install Laravel</h2>
        </div>
    </x-slot>

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('user.laravel.store') }}" method="POST"
          @submit="installing = true"
          x-data="{
              installing: false,
              installPath: '',
              selectedDomain: '{{ $domains->first()->domain ?? '' }}',
              dbMode: 'auto',
              version: 'latest'
          }">
        @csrf

        <div class="max-w-2xl space-y-6">

            {{-- Domain & Path --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-red-600">1</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Domain & Location</h3>
                            <p class="text-sm text-gray-500">Where to install Laravel.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Domain / Subdomain</label>
                        <select name="domain_id"
                            x-on:change="selectedDomain = $event.target.options[$event.target.selectedIndex].text.split(' ')[0]"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($domains as $domain)
                                <option value="{{ $domain->id }}">{{ $domain->domain }} ({{ $domain->account->server->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Install Path <span class="text-gray-400 font-normal">(optional)</span></label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm font-mono"
                                  x-text="selectedDomain + '/'"></span>
                            <input type="text" name="install_path" x-model="installPath"
                                class="flex-1 min-w-0 rounded-r-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="leave empty for domain root">
                        </div>
                        <p class="mt-1.5 text-xs text-gray-400">Empty = domain root. Nginx will point to Laravel's public/ directory.</p>
                    </div>
                </div>
            </div>

            {{-- Laravel Version --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-red-600">2</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Laravel Version</h3>
                            <p class="text-sm text-gray-500">Select the version to install.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <div class="flex flex-wrap gap-3">
                        @foreach(['latest' => 'Latest', '13' => 'Laravel 13', '12' => 'Laravel 12', '11' => 'Laravel 11', '10' => 'Laravel 10'] as $val => $label)
                            <label class="relative">
                                <input type="radio" name="version" value="{{ $val }}" class="peer sr-only"
                                    x-model="version" @checked($val === 'latest')>
                                <div class="px-4 py-2.5 border border-gray-200 rounded-lg cursor-pointer text-sm font-medium
                                    peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700
                                    hover:bg-gray-50 transition">
                                    {{ $label }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- App Settings --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-red-600">3</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Application Settings</h3>
                            <p class="text-sm text-gray-500">These will be written to the .env file.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">App Name</label>
                            <input type="text" name="app_name" value="{{ old('app_name', 'My Laravel App') }}"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Environment</label>
                            <select name="app_env"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="production">Production (APP_DEBUG=false)</option>
                                <option value="staging">Staging (APP_DEBUG=true)</option>
                                <option value="local">Local Development (APP_DEBUG=true)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Database --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-red-600">4</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Database</h3>
                            <p class="text-sm text-gray-500">Database will be configured in .env and migrations will run automatically.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div class="flex space-x-1 bg-gray-100 rounded-lg p-1 w-fit">
                        <button type="button" @click="dbMode = 'auto'"
                            class="px-4 py-1.5 text-xs font-medium rounded-md transition"
                            :class="dbMode === 'auto' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                            Auto-create
                        </button>
                        <button type="button" @click="dbMode = 'manual'"
                            class="px-4 py-1.5 text-xs font-medium rounded-md transition"
                            :class="dbMode === 'manual' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                            Use existing
                        </button>
                    </div>
                    <input type="hidden" name="db_mode" :value="dbMode">

                    <div x-show="dbMode === 'auto'" class="bg-green-50 rounded-lg p-4">
                        <p class="text-sm text-green-700">A new MySQL database and user will be created automatically. Credentials will be written to .env.</p>
                    </div>

                    <div x-show="dbMode === 'manual'" x-collapse class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Database Name</label>
                                <input type="text" name="db_name" value="{{ old('db_name') }}"
                                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">DB Username</label>
                                <input type="text" name="db_user" value="{{ old('db_user') }}"
                                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">DB Password</label>
                                <input type="password" name="db_password"
                                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Info --}}
            <div class="bg-red-50 border border-red-200 rounded-xl p-5">
                <h4 class="text-sm font-semibold text-red-800 mb-2">What happens next</h4>
                <ul class="text-sm text-red-700 space-y-1">
                    <li>Composer will create a fresh Laravel project</li>
                    <li>The .env file will be configured with your settings</li>
                    <li>APP_KEY will be generated automatically</li>
                    <li>Database migrations will run</li>
                    <li>Nginx will be configured to serve from Laravel's public/ directory</li>
                    <li>This may take 1-2 minutes</li>
                </ul>
            </div>

            {{-- Submit --}}
            <div class="flex items-center space-x-3">
                <button type="submit"
                    :disabled="installing"
                    class="inline-flex items-center px-6 py-3 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <template x-if="!installing">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                    </template>
                    <template x-if="installing">
                        <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </template>
                    <span x-text="installing ? 'Installing Laravel... Please wait' : 'Install Laravel'">Install Laravel</span>
                </button>
                <a href="{{ route('user.laravel.index') }}" class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</x-user-layout>
