<x-user-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('user.wordpress.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">Install WordPress</h2>
        </div>
    </x-slot>

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('user.wordpress.store') }}" method="POST"
          @submit="installing = true"
          x-data="{
              installing: false,
              installPath: '',
              adminUser: '',
              password: '',
              selectedDomain: '{{ $domains->first()->domain ?? '' }}',
              get passwordStrength() {
                  const p = this.password;
                  if (!p) return { score: 0, label: '', color: '' };
                  let score = 0;
                  if (p.length >= 8) score++;
                  if (p.length >= 12) score++;
                  if (/[a-z]/.test(p) && /[A-Z]/.test(p)) score++;
                  if (/[0-9]/.test(p)) score++;
                  if (/[^a-zA-Z0-9]/.test(p)) score++;
                  if (score <= 1) return { score: 1, label: 'Weak', color: 'bg-red-500' };
                  if (score <= 2) return { score: 2, label: 'Fair', color: 'bg-orange-500' };
                  if (score <= 3) return { score: 3, label: 'Good', color: 'bg-yellow-500' };
                  if (score <= 4) return { score: 4, label: 'Strong', color: 'bg-green-500' };
                  return { score: 5, label: 'Very Strong', color: 'bg-green-600' };
              },
              validateAdmin() {
                  this.adminUser = this.adminUser.replace(/[^a-zA-Z0-9_]/g, '').substring(0, 30);
              }
          }">
        @csrf

        <div class="max-w-2xl space-y-6">

            {{-- Domain --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-blue-600">1</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Domain</h3>
                            <p class="text-sm text-gray-500">Select the domain or subdomain to install WordPress on.</p>
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
                                placeholder="leave empty for root">
                        </div>
                        <p class="mt-1.5 text-xs text-gray-400">
                            Empty = install at domain root. Or enter "blog" for domain.com/blog
                        </p>
                        @error('install_path')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Site Info --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-blue-600">2</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Site Information</h3>
                            <p class="text-sm text-gray-500">Basic details for your WordPress site.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Site Title</label>
                        <input type="text" name="site_title" value="{{ old('site_title', 'My Website') }}"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('site_title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Language</label>
                        <select name="language"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="en_US">English (US)</option>
                            <option value="en_GB">English (UK)</option>
                            <option value="de_DE">Deutsch</option>
                            <option value="fr_FR">Fran&ccedil;ais</option>
                            <option value="es_ES">Espa&ntilde;ol</option>
                            <option value="it_IT">Italiano</option>
                            <option value="pt_BR">Portugu&ecirc;s (Brasil)</option>
                            <option value="nl_NL">Nederlands</option>
                            <option value="ro_RO">Rom&acirc;n&atilde;</option>
                            <option value="ru_RU">Русский</option>
                            <option value="ja">日本語</option>
                            <option value="zh_CN">中文 (简体)</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Admin Account --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-blue-600">3</span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Admin Account</h3>
                            <p class="text-sm text-gray-500">WordPress administrator credentials.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Admin Username</label>
                            <input type="text" name="admin_user" x-model="adminUser" @input="validateAdmin()"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="e.g. myadmin">
                            <p class="mt-1 text-xs text-red-500" x-show="adminUser === 'admin' || adminUser === 'administrator'">
                                Do not use "admin" — it's the first username attackers try.
                            </p>
                            @error('admin_user')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Admin Email</label>
                            <input type="email" name="admin_email" value="{{ old('admin_email', Auth::user()->email) }}"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('admin_email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Admin Password</label>
                        <input type="password" name="admin_password" x-model="password"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Min 8 characters">
                        <div class="mt-2 flex items-center space-x-3">
                            <div class="flex space-x-1 flex-1">
                                <template x-for="i in 5">
                                    <div class="h-1.5 flex-1 rounded-full transition-all duration-300"
                                        :class="passwordStrength.score >= i ? passwordStrength.color : 'bg-gray-200'"></div>
                                </template>
                            </div>
                            <span class="text-xs font-medium"
                                :class="{
                                    'text-red-500': passwordStrength.score === 1,
                                    'text-orange-500': passwordStrength.score === 2,
                                    'text-yellow-600': passwordStrength.score === 3,
                                    'text-green-500': passwordStrength.score >= 4,
                                    'text-gray-400': !passwordStrength.score
                                }"
                                x-text="passwordStrength.label || ''"></span>
                        </div>
                        @error('admin_password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Info box --}}
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
                <h4 class="text-sm font-semibold text-blue-800 mb-2">What happens next</h4>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>A MySQL database will be created automatically</li>
                    <li>WordPress will be downloaded and configured</li>
                    <li>Your admin account will be ready to use</li>
                    <li>This usually takes about 30 seconds</li>
                </ul>
            </div>

            {{-- Submit --}}
            <div class="flex items-center space-x-3">
                <button type="submit"
                    :disabled="installing || !adminUser || passwordStrength.score < 3 || adminUser === 'admin'"
                    class="inline-flex items-center px-6 py-3 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <template x-if="!installing">
                        <svg class="w-4 h-4 mr-2" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"/></svg>
                    </template>
                    <template x-if="installing">
                        <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </template>
                    <span x-text="installing ? 'Installing WordPress... Please wait' : 'Install WordPress'">Install WordPress</span>
                </button>
                <a href="{{ route('user.wordpress.index') }}" class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</x-user-layout>
