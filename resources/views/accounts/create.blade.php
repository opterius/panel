<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.accounts.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">{{ __('accounts.create_account') }}</h2>
        </div>
    </x-slot>

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if($servers->isEmpty())
        <div class="max-w-2xl">
            <div class="bg-white rounded-xl shadow-sm p-6 text-center py-16">
                <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>
                <h3 class="mt-4 text-base font-medium text-gray-700">{{ __('accounts.no_servers_available') }}</h3>
                <p class="mt-2 text-sm text-gray-500">{{ __('accounts.need_server_to_create_account') }}</p>
                <a href="{{ route('admin.servers.create') }}" class="mt-6 inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    {{ __('accounts.add_server') }}
                </a>
            </div>
        </div>
    @else
        @php
            $packageData = $packages->map(fn($p) => [
                'id'                  => $p->id,
                'name'                => $p->name,
                'php_versions'        => $p->php_versions,
                'default_php_version' => $p->default_php_version,
                'disk_label'          => $p->diskQuotaLabel(),
                'bandwidth_label'     => $p->bandwidthLabel(),
                'subdomains'          => $p->limitLabel($p->max_subdomains),
                'databases'           => $p->limitLabel($p->max_databases),
                'emails'              => $p->limitLabel($p->max_email_accounts),
            ])->keyBy('id');
            $defaultId = $defaultPackage?->id ?? $packages->first()?->id;
        @endphp

        <form action="{{ route('admin.accounts.store') }}" method="POST"
              x-data="{
                  username: '{{ old('username') }}',
                  domain: '{{ old('domain') }}',
                  selectedPackageId: '{{ old('package_id', $defaultId) }}',
                  packages: {{ $packageData->toJson() }},
                  get selectedPackage() {
                      return this.packages[this.selectedPackageId] ?? null;
                  }
              }">
            @csrf

            <div class="max-w-3xl space-y-6">

                {{-- Section 1: Account Info --}}
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600">1</span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-800">{{ __('accounts.account_information') }}</h3>
                                <p class="text-sm text-gray-500">{{ __('accounts.choose_server_setup_user') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-5 space-y-5">
                        <div>
                            <label for="server_id" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('accounts.server') }}</label>
                            <select name="server_id" id="server_id"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($servers as $server)
                                    <option value="{{ $server->id }}" @selected(old('server_id') == $server->id)>
                                        {{ $server->name }} ({{ $server->ip_address }})
                                    </option>
                                @endforeach
                            </select>
                            @error('server_id')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('accounts.username') }}</label>
                            <input type="text" name="username" id="username" value="{{ old('username') }}"
                                x-model="username"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="{{ __('accounts.username_placeholder') }}">
                            <p class="mt-1.5 text-xs text-gray-400">{{ __('accounts.username_hint') }}</p>
                            @error('username')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Section 2: Primary Domain --}}
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600">2</span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-800">{{ __('accounts.primary_domain') }}</h3>
                                <p class="text-sm text-gray-500">{{ __('accounts.main_domain_for_account') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-5">
                        <label for="domain" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('accounts.domain_name') }}</label>
                        <input type="text" name="domain" id="domain" value="{{ old('domain') }}"
                            x-model="domain"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="{{ __('accounts.domain_placeholder') }}">
                        @error('domain')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Section 3: Package --}}
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                    <span class="text-sm font-bold text-indigo-600">3</span>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-gray-800">{{ __('accounts.package') }}</h3>
                                    <p class="text-sm text-gray-500">{{ __('accounts.assign_package_description') }}</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.packages.create') }}" target="_blank"
                               class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition">
                                {{ __('accounts.new_package') }}
                            </a>
                        </div>
                    </div>

                    <div class="px-6 py-5">
                        @if($packages->isEmpty())
                            <div class="text-center py-6 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                                <p class="text-sm text-gray-500 mb-3">{{ __('accounts.no_packages_yet') }}</p>
                                <a href="{{ route('admin.packages.create') }}" target="_blank"
                                   class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                    {{ __('accounts.create_first_package') }}
                                </a>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($packages as $pkg)
                                    <label class="relative block">
                                        <input type="radio" name="package_id" value="{{ $pkg->id }}" class="peer sr-only"
                                            x-model="selectedPackageId"
                                            @checked(old('package_id', $defaultId) == $pkg->id)>
                                        <div class="px-4 py-3 border border-gray-200 rounded-lg cursor-pointer
                                            peer-checked:border-indigo-500 peer-checked:bg-indigo-50 hover:bg-gray-50 transition">
                                            <div class="flex items-center justify-between mb-2">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-sm font-semibold text-gray-800">{{ $pkg->name }}</span>
                                                    @if($pkg->is_default)
                                                        <span class="px-1.5 py-0.5 text-xs bg-indigo-100 text-indigo-600 rounded">{{ __('accounts.default_badge') }}</span>
                                                    @endif
                                                </div>
                                                <span class="text-xs text-gray-400">PHP {{ implode(', ', $pkg->php_versions) }}</span>
                                            </div>
                                            <div class="flex flex-wrap gap-3 text-xs text-gray-500">
                                                <span>{{ __('accounts.disk_short') }} {{ $pkg->diskQuotaLabel() }}</span>
                                                <span>{{ __('accounts.bw_short') }} {{ $pkg->bandwidthLabel() }}</span>
                                                <span>{{ __('accounts.dbs_short') }} {{ $pkg->limitLabel($pkg->max_databases) }}</span>
                                                @if($pkg->ssl_enabled) <span class="text-green-600">{{ __('common.ssl') }}</span> @endif
                                                @if($pkg->cron_jobs_enabled) <span class="text-green-600">{{ __('common.cron') }}</span> @endif
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        @error('package_id')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Account Owner --}}
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600">4</span>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-800">Account Owner</h3>
                                <p class="text-sm text-gray-500">Who will manage this hosting account from the panel.</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-5 space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Owner Email</label>
                            <input type="email" name="owner_email" value="{{ old('owner_email', Auth::user()->email) }}"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="client@example.com">
                            <p class="mt-1.5 text-xs text-gray-400">
                                If a user with this email already exists, the account is assigned to them.
                                Otherwise, a new user is created and they'll receive login credentials.
                                Default is your own email if you'll manage this account yourself.
                            </p>
                            @error('owner_email')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Owner Name <span class="text-gray-400 font-normal">(only used for new users)</span></label>
                            <input type="text" name="owner_name" value="{{ old('owner_name') }}"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Full name">
                            @error('owner_name')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Owner Password <span class="text-gray-400 font-normal">(only used for new users)</span></label>
                            <input type="password" name="owner_password" value="{{ old('owner_password') }}"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Min 8 characters (leave blank to auto-generate)">
                            <p class="mt-1.5 text-xs text-gray-400">If left blank, a random password will be generated and shown after account creation.</p>
                            @error('owner_password')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Summary --}}
                <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-5">
                    <h4 class="text-sm font-semibold text-indigo-800 mb-3">{{ __('accounts.summary') }}</h4>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm text-indigo-700">
                        <div>
                            <span class="text-indigo-400 block text-xs mb-0.5">{{ __('accounts.username') }}</span>
                            <span class="font-medium" x-text="username || '—'">—</span>
                        </div>
                        <div>
                            <span class="text-indigo-400 block text-xs mb-0.5">{{ __('domains.domain') }}</span>
                            <span class="font-medium" x-text="domain || '—'">—</span>
                        </div>
                        <div>
                            <span class="text-indigo-400 block text-xs mb-0.5">{{ __('accounts.package') }}</span>
                            <span class="font-medium" x-text="selectedPackage ? selectedPackage.name : 'None'">—</span>
                        </div>
                        <div>
                            <span class="text-indigo-400 block text-xs mb-0.5">{{ __('accounts.php_default_label') }}</span>
                            <span class="font-medium" x-text="selectedPackage ? 'PHP ' + selectedPackage.default_php_version : '—'">—</span>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center space-x-3">
                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        {{ __('accounts.create_account') }}
                    </button>
                    <a href="{{ route('admin.accounts.index') }}" class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        {{ __('common.cancel') }}
                    </a>
                </div>

            </div>
        </form>
    @endif
</x-admin-layout>
