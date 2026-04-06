<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">{{ __('ssl.ssl_certificates') }}</h2>
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

    <!-- Issue Let's Encrypt -->
    @if($domains->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">{{ __('ssl.issue_free_ssl') }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ __('ssl.issue_free_ssl_letsencrypt') }}</p>
            </div>
            <form action="{{ route('user.ssl.issue') }}" method="POST" class="px-6 py-5"
                  x-data="{ issuing: false }" @submit="issuing = true">
                @csrf
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <label for="domain_id" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('ssl.domain_label') }}</label>
                        <select name="domain_id" id="domain_id"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($domains as $domain)
                                <option value="{{ $domain->id }}">{{ $domain->domain }} ({{ $domain->server->name }})</option>
                            @endforeach
                        </select>
                        @error('domain_id')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex-1">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('ssl.email_for_letsencrypt') }}</label>
                        <input type="email" name="email" id="email" value="{{ old('email', Auth::user()->email) }}"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('email')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-end">
                        <button type="submit" :disabled="issuing"
                            class="inline-flex items-center px-5 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <template x-if="!issuing">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </template>
                            <template x-if="issuing">
                                <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            </template>
                            <span x-text="issuing ? '{{ __('ssl.issuing_ssl_please_wait') }}' : '{{ __('ssl.issue_ssl') }}'">{{ __('ssl.issue_ssl') }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <!-- Certificates List -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">{{ __('ssl.installed_certificates') }}</h3>
        </div>

        @if($certificates->isEmpty())
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                <h3 class="mt-4 text-base font-medium text-gray-700">{{ __('ssl.no_ssl_certificates') }}</h3>
                <p class="mt-2 text-sm text-gray-500">{{ __('ssl.issue_letsencrypt_or_upload') }}</p>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($certificates as $cert)
                    <div class="flex items-center justify-between px-6 py-4">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                @if($cert->status === 'active') bg-green-100
                                @elseif($cert->status === 'expiring_soon') bg-yellow-100
                                @elseif($cert->status === 'expired') bg-red-100
                                @else bg-gray-100
                                @endif">
                                <svg class="w-5 h-5
                                    @if($cert->status === 'active') text-green-600
                                    @elseif($cert->status === 'expiring_soon') text-yellow-600
                                    @elseif($cert->status === 'expired') text-red-600
                                    @else text-gray-400
                                    @endif"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">{{ $cert->domain->domain }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ ucfirst($cert->type) }}
                                    @if($cert->expires_at)
                                        &middot; {{ __('ssl.expires') }} {{ $cert->expires_at->format('M d, Y') }}
                                        ({{ $cert->expires_at->diffForHumans() }})
                                    @endif
                                    @if($cert->auto_renew)
                                        &middot; {{ __('ssl.auto_renew') }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                @if($cert->status === 'active') bg-green-100 text-green-700
                                @elseif($cert->status === 'expiring_soon') bg-yellow-100 text-yellow-700
                                @elseif($cert->status === 'expired') bg-red-100 text-red-700
                                @else bg-gray-100 text-gray-600
                                @endif">
                                {{ ucfirst(str_replace('_', ' ', $cert->status)) }}
                            </span>

                            @if($cert->type === 'letsencrypt')
                                <form action="{{ route('user.ssl.renew', $cert) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium transition" title="{{ __('ssl.renew_btn') }}">
                                        {{ __('ssl.renew_btn') }}
                                    </button>
                                </form>
                            @endif

                            <x-delete-modal
                                :action="route('user.ssl.destroy', $cert)"
                                :title="__('ssl.remove_ssl_title')"
                                :message="__('ssl.remove_ssl_msg')"
                                :confirm-password="true">
                                <x-slot name="trigger">
                                    <button type="button" class="text-gray-400 hover:text-red-600 transition" title="{{ __('common.remove') }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </x-slot>
                            </x-delete-modal>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Upload Custom Certificate (collapsible) -->
    <div class="mt-6 bg-white rounded-xl shadow-sm overflow-hidden" x-data="{ open: false }">
        <button @click="open = !open" class="w-full px-6 py-4 flex items-center justify-between text-left hover:bg-gray-100 transition">
            <div>
                <h3 class="text-sm font-semibold text-gray-800">{{ __('ssl.upload_custom_certificate') }}</h3>
                <p class="text-xs text-gray-500 mt-0.5">{{ __('ssl.paste_your_own_ssl') }}</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
        </button>

        <form action="{{ route('user.ssl.upload') }}" method="POST" x-show="open" x-collapse>
            @csrf
            <div class="px-6 pb-5 space-y-4">
                <div>
                    <label for="upload_domain_id" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('ssl.domain_label') }}</label>
                    <select name="domain_id" id="upload_domain_id"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($domains as $domain)
                            <option value="{{ $domain->id }}">{{ $domain->domain }}</option>
                        @endforeach
                        @foreach($certificates as $cert)
                            <option value="{{ $cert->domain_id }}">{{ $cert->domain->domain }} ({{ __('ssl.replace_existing') }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="certificate" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('ssl.certificate_pem') }}</label>
                    <textarea name="certificate" id="certificate" rows="4"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-xs font-mono focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="-----BEGIN CERTIFICATE-----"></textarea>
                    @error('certificate')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="private_key" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('ssl.private_key_pem') }}</label>
                    <textarea name="private_key" id="private_key" rows="4"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-xs font-mono focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="-----BEGIN PRIVATE KEY-----"></textarea>
                    @error('private_key')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    {{ __('ssl.upload_certificate') }}
                </button>
            </div>
        </form>
    </div>
</x-user-layout>
