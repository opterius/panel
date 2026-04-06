<form action="{{ $action }}" method="POST">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    <div class="max-w-2xl space-y-6">

        {{-- Basic Info --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                        <span class="text-sm font-bold text-indigo-600">1</span>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">{{ __('packages.package_name') }}</h3>
                        <p class="text-sm text-gray-500">{{ __('packages.packages_define_limits') }}</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-5 space-y-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('packages.package_name') }}</label>
                    <input type="text" name="name" id="name"
                           value="{{ old('name', $package?->name) }}"
                           placeholder="{{ __('packages.package_name_placeholder') }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('name')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('packages.description') }} <span class="text-gray-400 font-normal">({{ __('common.optional') }})</span></label>
                    <input type="text" name="description" id="description"
                           value="{{ old('description', $package?->description) }}"
                           placeholder="{{ __('packages.description_placeholder') }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('description')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-center space-x-3">
                    <input type="checkbox" name="is_default" id="is_default" value="1"
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                           @checked(old('is_default', $package?->is_default))>
                    <label for="is_default" class="text-sm text-gray-700">{{ __('packages.set_as_default') }} <span class="text-gray-400">({{ __('packages.default_hint') }})</span></label>
                </div>
            </div>
        </div>

        {{-- PHP & Disk --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                        <span class="text-sm font-bold text-indigo-600">2</span>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">{{ __('packages.php_and_storage') }}</h3>
                        <p class="text-sm text-gray-500">{{ __('packages.php_storage_hint') }}</p>
                    </div>
                </div>
            </div>
            @php
                $availableVersions = config('opterius.php_versions');
                $currentVersions = old('php_versions', $package?->php_versions ?? $availableVersions);
                $currentDefault = old('default_php_version', $package?->default_php_version ?? config('opterius.default_php_version'));
            @endphp
            <div class="px-6 py-5 space-y-5" x-data="{
                versions: {{ json_encode($currentVersions) }},
                defaultVersion: '{{ $currentDefault }}',
                toggle(v) {
                    if (this.versions.includes(v)) {
                        if (this.versions.length === 1) return;
                        this.versions = this.versions.filter(x => x !== v);
                        if (this.defaultVersion === v) this.defaultVersion = this.versions[0];
                    } else {
                        this.versions.push(v);
                        this.versions.sort();
                    }
                }
            }">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('packages.allowed_php_versions') }}</label>
                    <p class="text-xs text-gray-400 mb-3">{{ __('packages.allowed_php_versions_hint') }}</p>
                    <div class="grid grid-cols-4 gap-3">
                        @foreach($availableVersions as $version)
                            <label class="relative cursor-pointer" @click.prevent="toggle('{{ $version }}')">
                                <div class="flex items-center justify-center px-4 py-3 border rounded-lg text-sm font-medium transition"
                                    :class="versions.includes('{{ $version }}')
                                        ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                        : 'border-gray-200 text-gray-400 hover:bg-gray-50'">
                                    <svg x-show="versions.includes('{{ $version }}')" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    PHP {{ $version }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <template x-for="v in versions" :key="v">
                        <input type="hidden" name="php_versions[]" :value="v">
                    </template>
                    @error('php_versions')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('packages.default_php_version') }}</label>
                    <p class="text-xs text-gray-400 mb-3">{{ __('packages.default_php_version_hint') }}</p>
                    <div class="grid grid-cols-4 gap-3">
                        @foreach($availableVersions as $version)
                            <label class="relative cursor-pointer"
                                x-show="versions.includes('{{ $version }}')"
                                @click.prevent="defaultVersion = '{{ $version }}'">
                                <div class="flex items-center justify-center px-4 py-3 border rounded-lg text-sm font-medium transition"
                                    :class="defaultVersion === '{{ $version }}'
                                        ? 'border-green-500 bg-green-50 text-green-700'
                                        : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                                    PHP {{ $version }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <input type="hidden" name="default_php_version" :value="defaultVersion">
                    @error('default_php_version')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <x-quota-picker
                        name="disk_quota"
                        :label="__('packages.disk_quota_mb')"
                        :value="$package?->disk_quota ?? 0"
                        :presets="[
                            ['mb' => 512, 'label' => '512 MB'],
                            ['mb' => 1024, 'label' => '1 GB'],
                            ['mb' => 2048, 'label' => '2 GB'],
                            ['mb' => 5120, 'label' => '5 GB'],
                            ['mb' => 10240, 'label' => '10 GB'],
                        ]"
                    />

                    <x-quota-picker
                        name="bandwidth"
                        :label="__('packages.bandwidth_per_month')"
                        :value="$package?->bandwidth ?? 0"
                        :presets="[
                            ['mb' => 10240, 'label' => '10 GB'],
                            ['mb' => 51200, 'label' => '50 GB'],
                            ['mb' => 102400, 'label' => '100 GB'],
                            ['mb' => 512000, 'label' => '500 GB'],
                            ['mb' => 1048576, 'label' => '1 TB'],
                        ]"
                    />
                </div>
            </div>
        </div>

        {{-- Limits --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                        <span class="text-sm font-bold text-indigo-600">3</span>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">{{ __('packages.resource_limits') }}</h3>
                        <p class="text-sm text-gray-500">{{ __('packages.set_zero_unlimited') }}</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <x-limit-picker name="max_domains" :label="__('packages.max_addon_domains')" :value="$package?->max_domains ?? 0" />
                <x-limit-picker name="max_subdomains" :label="__('packages.max_subdomains')" :value="$package?->max_subdomains ?? 0" />
                <x-limit-picker name="max_databases" :label="__('packages.max_databases')" :value="$package?->max_databases ?? 0" />
                <x-limit-picker name="max_email_accounts" :label="__('packages.max_email_accounts')" :value="$package?->max_email_accounts ?? 0" />
            </div>
        </div>

        {{-- PHP Resources --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                        <span class="text-sm font-bold text-indigo-600">4</span>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">{{ __('packages.php_resources') }}</h3>
                        <p class="text-sm text-gray-500">{{ __('packages.php_resources_hint') }}</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('packages.php_workers') }}</label>
                    <p class="text-xs text-gray-400 mb-2">{{ __('packages.php_workers_hint') }}</p>
                    <div class="flex flex-wrap gap-2" x-data="{ workers: '{{ old('max_php_workers', $package?->max_php_workers ?? 5) }}' }">
                        @foreach([2, 3, 5, 10, 15, 25] as $w)
                            <button type="button" @click="workers = '{{ $w }}'"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg border transition"
                                :class="workers == '{{ $w }}' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                                {{ $w }} {{ __('packages.workers') }}
                            </button>
                        @endforeach
                        <input type="hidden" name="max_php_workers" :value="workers">
                    </div>
                    <p class="mt-2 text-xs text-gray-400">{{ __('packages.php_workers_recommended') }}</p>
                    @error('max_php_workers')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('packages.memory_per_process') }}</label>
                    <p class="text-xs text-gray-400 mb-2">{{ __('packages.memory_per_process_hint') }}</p>
                    <div class="flex flex-wrap gap-2" x-data="{ memory: '{{ old('memory_per_process', $package?->memory_per_process ?? 256) }}' }">
                        @foreach([64, 128, 256, 512, 1024, 2048] as $m)
                            <button type="button" @click="memory = '{{ $m }}'"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg border transition"
                                :class="memory == '{{ $m }}' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                                {{ $m >= 1024 ? ($m / 1024) . ' GB' : $m . ' MB' }}
                            </button>
                        @endforeach
                        <input type="hidden" name="memory_per_process" :value="memory">
                    </div>
                    <p class="mt-2 text-xs text-gray-400">
                        {{ __('packages.max_ram_per_domain') }}: <span class="font-semibold" x-data x-text="'{{ __('packages.workers') }} x {{ __('packages.memory') }}'"></span>
                    </p>
                    @error('memory_per_process')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Features --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                        <span class="text-sm font-bold text-indigo-600">5</span>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">{{ __('packages.features') }}</h3>
                        <p class="text-sm text-gray-500">{{ __('packages.features_hint') }}</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div class="flex items-center space-x-3">
                    <input type="checkbox" name="ssl_enabled" id="ssl_enabled" value="1"
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                           @checked(old('ssl_enabled', $package?->ssl_enabled ?? true))>
                    <label for="ssl_enabled" class="text-sm text-gray-700">{{ __('packages.ssl_enabled') }}</label>
                </div>
                <div class="flex items-center space-x-3">
                    <input type="checkbox" name="cron_jobs_enabled" id="cron_jobs_enabled" value="1"
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                           @checked(old('cron_jobs_enabled', $package?->cron_jobs_enabled ?? true))>
                    <label for="cron_jobs_enabled" class="text-sm text-gray-700">{{ __('cron.cron_jobs') }}</label>
                </div>
                <div class="flex items-center space-x-3">
                    <input type="checkbox" name="php_switch_enabled" id="php_switch_enabled" value="1"
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                           @checked(old('php_switch_enabled', $package?->php_switch_enabled ?? false))>
                    <label for="php_switch_enabled" class="text-sm text-gray-700">{{ __('packages.php_switch_enabled') }} <span class="text-gray-400">({{ __('packages.php_switch_hint') }})</span></label>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center space-x-3">
            <button type="submit"
                    class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                {{ $package ? __('common.save') : __('packages.new_package') }}
            </button>
            <a href="{{ route('admin.packages.index') }}"
               class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                {{ __('common.cancel') }}
            </a>
        </div>

    </div>
</form>
