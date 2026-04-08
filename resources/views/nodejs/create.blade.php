<x-user-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('user.nodejs.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">Deploy Node.js App</h2>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('user.nodejs.store') }}" method="POST"
          x-data="{ installing: false }"
          @submit="installing = true">
        @csrf
        <div class="max-w-2xl space-y-6">

            {{-- Domain --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-green-700">1</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Domain</h3>
                            <p class="text-sm text-gray-500">Nginx will proxy this domain to your app's port.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Domain / Subdomain</label>
                    <select name="domain_id"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-green-500 focus:ring-green-500">
                        @foreach($domains as $domain)
                            <option value="{{ $domain->id }}" @selected(old('domain_id') == $domain->id)>
                                {{ $domain->domain }} ({{ $domain->account->server->name }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- App Settings --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-green-700">2</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">App Configuration</h3>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">App Name</label>
                        <input type="text" name="name" value="{{ old('name') }}"
                            placeholder="my-app"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-green-500 focus:ring-green-500">
                        <p class="mt-1 text-xs text-gray-400">Letters, numbers, hyphens, underscores only. Used as the PM2 process name.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Startup Command</label>
                        <input type="text" name="startup_command" value="{{ old('startup_command', 'node server.js') }}"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-green-500 focus:ring-green-500">
                        <p class="mt-1 text-xs text-gray-400">E.g. <code class="bg-gray-100 px-1 rounded">node server.js</code> or <code class="bg-gray-100 px-1 rounded">npm start</code>. Run from your domain's home directory.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Port</label>
                        <input type="number" name="port" value="{{ old('port', 3000) }}"
                            min="1024" max="65535"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-green-500 focus:ring-green-500">
                        <p class="mt-1 text-xs text-gray-400">The port your app listens on (also passed as <code class="bg-gray-100 px-1 rounded">PORT</code> env var). Use 1024–65535.</p>
                    </div>
                </div>
            </div>

            {{-- Info --}}
            <div class="bg-green-50 border border-green-200 rounded-xl p-5">
                <h4 class="text-sm font-semibold text-green-800 mb-2">What happens</h4>
                <ul class="text-sm text-green-700 space-y-1">
                    <li>PM2 starts your app as the hosting account user</li>
                    <li>Nginx is configured to proxy the domain to your app's port</li>
                    <li>The app restarts automatically if the server reboots</li>
                    <li>Your app receives <code class="bg-green-100 px-1 rounded">PORT</code> as an environment variable</li>
                </ul>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                    :disabled="installing"
                    class="inline-flex items-center px-6 py-3 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition disabled:opacity-50">
                    <template x-if="!installing">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </template>
                    <template x-if="installing">
                        <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </template>
                    <span x-text="installing ? 'Deploying...' : 'Deploy App'">Deploy App</span>
                </button>
                <a href="{{ route('user.nodejs.index') }}"
                   class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</x-user-layout>
