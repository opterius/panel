<x-user-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('user.cms.index', $type) }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">Install {{ __('common.' . $type) }}</h2>
        </div>
    </x-slot>

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('user.cms.store', $type) }}" method="POST"
          x-data="{
              installing: false,
              installPath: '',
              selectedDomain: '{{ $domains->first()->domain ?? '' }}'
          }"
          @submit="installing = true">
        @csrf

        <div class="max-w-2xl space-y-6">

            {{-- Domain & Path --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-indigo-600">1</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Domain & Location</h3>
                            <p class="text-sm text-gray-500">Where to install {{ __('common.' . $type) }}.</p>
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
                                <option value="{{ $domain->id }}" @selected(old('domain_id') == $domain->id)>
                                    {{ $domain->domain }} ({{ $domain->account->server->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Install Path <span class="text-gray-400 font-normal">(optional)</span></label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm font-mono"
                                  x-text="selectedDomain + '/'"></span>
                            <input type="text" name="install_path" x-model="installPath"
                                value="{{ old('install_path') }}"
                                class="flex-1 min-w-0 rounded-r-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="leave empty for domain root">
                        </div>
                        <p class="mt-1.5 text-xs text-gray-400">Empty = domain root.</p>
                    </div>
                </div>
            </div>

            {{-- Site Settings --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-indigo-600">2</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Site Settings</h3>
                            <p class="text-sm text-gray-500">Basic configuration for your {{ __('common.' . $type) }} site.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Site Name</label>
                        <input type="text" name="site_name" value="{{ old('site_name', 'My Site') }}"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            {{-- Admin Account --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-indigo-600">3</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Admin Account</h3>
                            <p class="text-sm text-gray-500">Credentials for the {{ __('common.' . $type) }} administrator.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                            <input type="text" name="admin_user" value="{{ old('admin_user', 'admin') }}"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                            <input type="password" name="admin_pass"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                            <input type="email" name="admin_email" value="{{ old('admin_email', auth()->user()->email) }}"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Magento Marketplace Keys (only for Magento) --}}
            @if($type === 'magento')
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-indigo-600">4</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Magento Marketplace Keys</h3>
                            <p class="text-sm text-gray-500">Required to download Magento from repo.magento.com.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
                        Get your keys at <strong>commercemarketplace.adobe.com → My Profile → Access Keys</strong>.
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Public Key</label>
                        <input type="text" name="magento_public_key" value="{{ old('magento_public_key') }}"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Public key (username for repo.magento.com)">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Private Key</label>
                        <input type="password" name="magento_private_key"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Private key (password for repo.magento.com)">
                    </div>
                </div>
            </div>
            @endif

            {{-- What happens next --}}
            <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-5">
                <h4 class="text-sm font-semibold text-indigo-800 mb-2">What happens next</h4>
                <ul class="text-sm text-indigo-700 space-y-1">
                    @if($type === 'joomla')
                        <li>The latest Joomla release will be downloaded from GitHub</li>
                        <li>The files will be extracted to your domain root</li>
                        <li>Joomla will be installed using the CLI installer</li>
                        <li>The installation directory will be automatically removed</li>
                        <li>This typically takes 1–2 minutes</li>
                    @elseif($type === 'drupal')
                        <li>Composer will download Drupal and all dependencies</li>
                        <li>Nginx will be configured to serve from Drupal's web/ directory</li>
                        <li>Drush will run the installation</li>
                        <li>This typically takes 2–3 minutes</li>
                    @elseif($type === 'magento')
                        <li>Magento will be downloaded from repo.magento.com using your Marketplace keys</li>
                        <li>Nginx will be configured to serve from Magento's pub/ directory</li>
                        <li>The setup wizard, compilation, and static content deployment will run</li>
                        <li>This can take 5–10 minutes</li>
                    @elseif($type === 'prestashop')
                        <li>The latest PrestaShop release will be downloaded from GitHub</li>
                        <li>The files will be extracted and installed via the CLI installer</li>
                        <li>The install directory will be automatically removed</li>
                        <li>This typically takes 1–2 minutes</li>
                    @endif
                    <li>A database and user will be created automatically</li>
                </ul>
            </div>

            {{-- Submit --}}
            <div class="flex items-center space-x-3">
                <button type="submit"
                    :disabled="installing"
                    class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <template x-if="!installing">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                    </template>
                    <template x-if="installing">
                        <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </template>
                    <span x-text="installing ? 'Installing... Please wait' : 'Install {{ __('common.' . $type) }}'">
                        Install {{ __('common.' . $type) }}
                    </span>
                </button>
                <a href="{{ route('user.cms.index', $type) }}"
                   class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</x-user-layout>
