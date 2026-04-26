<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">{{ __('emails.email_accounts') }}</h2>
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

    <!-- Webmail Button (generic fallback when SSO is not configured) -->
    @if(!config('opterius.webmail_sso_secret'))
    <div class="mb-6 flex justify-end">
        <a href="{{ str_replace('SERVER_IP', request()->getHost(), config('opterius.webmail_url')) }}"
           target="_blank"
           class="inline-flex items-center px-4 py-2.5 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
            {{ __('emails.open_webmail') }}
        </a>
    </div>
    @endif

    <!-- Create Email Account -->
    @if($domains->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">{{ __('emails.create_email_account') }}</h3>
            </div>
            <form action="{{ route('user.emails.store') }}" method="POST" class="px-6 py-5"
                  x-data="{
                      username: '',
                      password: '',
                      showPassword: false,
                      passwordCopied: false,
                      selectedDomain: '{{ $domains->first()->domain }}',
                      quotaOption: '500',
                      customQuota: '',
                      generatePassword() {
                          const charset = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%^&*';
                          let p = '';
                          const arr = new Uint32Array(16);
                          crypto.getRandomValues(arr);
                          for (const n of arr) p += charset[n % charset.length];
                          this.password = p;
                          this.showPassword = true;
                      },
                      async copyPassword() {
                          if (!this.password) return;
                          try {
                              await navigator.clipboard.writeText(this.password);
                              this.passwordCopied = true;
                              setTimeout(() => this.passwordCopied = false, 1500);
                          } catch (e) {}
                      },
                      get cleanUsername() {
                          return this.username.replace(/[^a-zA-Z0-9._-]/g, '').substring(0, 25);
                      },
                      validateUsername() {
                          this.username = this.username.replace(/[^a-zA-Z0-9._-]/g, '').substring(0, 25);
                      },
                      get usernameError() {
                          if (this.username && this.username.length < 2) return 'Min 2 characters';
                          if (this.username && /^[._-]/.test(this.username)) return 'Must start with a letter or number';
                          if (this.username && /[._-]$/.test(this.username)) return 'Must end with a letter or number';
                          return '';
                      },
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
                      get quotaValue() {
                          return this.quotaOption === 'custom' ? this.customQuota : this.quotaOption;
                      }
                  }">
                @csrf
                <input type="hidden" name="quota" :value="quotaValue">

                <div class="space-y-5">
                    {{-- Row 1: Username @ Domain --}}
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                        <div class="sm:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('emails.username') }}</label>
                            <input type="text" name="username" x-model="username" @input="validateUsername()" maxlength="25"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="info">
                            <div class="mt-1 flex justify-between">
                                <p class="text-xs" :class="usernameError ? 'text-red-500' : 'text-gray-400'" x-text="usernameError || 'Letters, numbers, dots, hyphens. Max 25.'"></p>
                                <span class="text-xs text-gray-400" x-text="username.length + '/25'"></span>
                            </div>
                            @error('username')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="sm:col-span-1 flex items-center justify-center pt-4">
                            <span class="text-lg text-gray-400 font-bold">@</span>
                        </div>
                        <div class="sm:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('emails.domain') }}</label>
                            <select name="domain_id" x-on:change="selectedDomain = $event.target.options[$event.target.selectedIndex].text"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($domains as $domain)
                                    <option value="{{ $domain->id }}">{{ $domain->domain }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('emails.preview') }}</label>
                            <div class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-mono text-gray-600 truncate">
                                <span x-text="(cleanUsername || 'user') + '@' + selectedDomain"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Row 2: Password with strength meter --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('common.password') }}</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <div class="flex items-stretch gap-1.5">
                                    <input :type="showPassword ? 'text' : 'password'" name="password" x-model="password"
                                        class="flex-1 min-w-0 rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Min 8 characters" autocomplete="new-password">
                                    <button type="button" @click="showPassword = !showPassword"
                                        class="inline-flex items-center justify-center px-2.5 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg text-gray-600 transition"
                                        :title="showPassword ? 'Hide password' : 'Show password'">
                                        <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg x-show="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                    </button>
                                    <button type="button" @click="generatePassword()"
                                        class="inline-flex items-center justify-center px-2.5 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg text-indigo-700 transition"
                                        title="Generate random password">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </button>
                                    <button type="button" @click="copyPassword()" :disabled="!password"
                                        class="inline-flex items-center justify-center px-2.5 bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed border border-gray-300 rounded-lg text-gray-600 transition"
                                        :title="passwordCopied ? 'Copied!' : 'Copy password'">
                                        <svg x-show="!passwordCopied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        <svg x-show="passwordCopied" class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </div>
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="flex-1">
                                    <div class="flex space-x-1">
                                        <template x-for="i in 5">
                                            <div class="h-1.5 flex-1 rounded-full transition-all duration-300"
                                                :class="passwordStrength.score >= i ? passwordStrength.color : 'bg-gray-200'"></div>
                                        </template>
                                    </div>
                                    <p class="mt-1 text-xs font-medium"
                                       :class="{
                                           'text-red-500': passwordStrength.score === 1,
                                           'text-orange-500': passwordStrength.score === 2,
                                           'text-yellow-600': passwordStrength.score === 3,
                                           'text-green-500': passwordStrength.score === 4,
                                           'text-green-600': passwordStrength.score === 5,
                                           'text-gray-400': !passwordStrength.score
                                       }"
                                       x-text="passwordStrength.label || 'Enter a password'"></p>
                                </div>
                            </div>
                        </div>
                        <p class="mt-1.5 text-xs text-gray-400">{{ __('emails.password_strength_hint') }}</p>
                    </div>

                    {{-- Row 3: Quota picker --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('emails.mailbox_quota') }}</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach([
                                '100' => '100 MB',
                                '500' => '500 MB',
                                '1024' => '1 GB',
                                '2560' => '2.5 GB',
                                '5120' => '5 GB',
                                '0' => 'Unlimited',
                                'custom' => 'Custom',
                            ] as $value => $label)
                                <button type="button" @click="quotaOption = '{{ $value }}'"
                                    class="px-3 py-1.5 text-xs font-medium rounded-lg border transition"
                                    :class="quotaOption === '{{ $value }}' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                        <div x-show="quotaOption === 'custom'" x-collapse class="mt-3">
                            <div class="flex items-center gap-2 max-w-xs">
                                <input type="number" x-model="customQuota" min="1" max="51200"
                                    class="w-32 rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="e.g. 2048">
                                <span class="text-sm text-gray-500">{{ __('common.mb') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="submit"
                            :disabled="!cleanUsername || usernameError || passwordStrength.score < 3"
                            class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                            {{ __('emails.create_email_account') }}
                        </button>
                        <p class="text-xs text-gray-400" x-show="passwordStrength.score > 0 && passwordStrength.score < 3">
                            {{ __('emails.password_must_be_good') }}
                        </p>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <!-- Email Accounts List -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-800">{{ __('emails.email_accounts') }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ __('emails.manage_email_domains') }}</p>
            </div>
        </div>

        @if($emailAccounts->isEmpty())
            <div class="bg-white rounded-xl shadow-sm px-6 py-16 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                <h3 class="mt-4 text-base font-medium text-gray-700">{{ __('emails.no_email_accounts_yet') }}</h3>
                <p class="mt-2 text-sm text-gray-500">{{ __('emails.no_email_accounts_create') }}</p>
            </div>
        @else
            @foreach($emailAccounts as $account)
                <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200" x-data="{ open: false, tab: 'password' }">
                    {{-- Header --}}
                    <div class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-indigo-50/50 transition" @click="open = !open">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">{{ $account->email }}</div>
                                <div class="text-xs text-gray-500">
                                    Quota: {{ $account->quota > 0 ? ($account->quota >= 1024 ? round($account->quota / 1024, 1) . ' GB' : $account->quota . ' MB') : 'Unlimited' }}
                                    @if(!$account->can_send) &middot; <span class="text-red-500">{{ __('emails.sending_disabled') }}</span> @endif
                                    @if(!$account->can_receive) &middot; <span class="text-red-500">{{ __('emails.receiving_disabled') }}</span> @endif
                                    @if($account->max_send_per_hour > 0) &middot; {{ $account->max_send_per_hour }}/hr @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            @if($account->status === 'active')
                                <a href="{{ route('user.emails.webmail', $account) }}"
                                   @click.stop
                                   target="_blank"
                                   rel="noopener"
                                   class="inline-flex items-center px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium rounded-lg transition">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    {{ __('emails.open_webmail') }}
                                </a>
                            @endif
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                @if($account->status === 'active') bg-green-100 text-green-700 @else bg-red-100 text-red-700 @endif">
                                {{ ucfirst($account->status) }}
                            </span>
                            <svg class="w-5 h-5 text-gray-400 transition" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>

                    {{-- Expandable Panel --}}
                    <div x-show="open" x-collapse>
                        {{-- Tabs --}}
                        <div class="px-6 py-2.5 border-t border-gray-200 bg-white">
                            <div class="flex space-x-1 bg-gray-100 rounded-lg p-1 w-fit">
                                @foreach(['password' => __('common.password'), 'quota' => __('emails.mailbox_quota'), 'restrictions' => __('emails.send_limits'), 'delete' => __('common.delete')] as $key => $label)
                                    <button type="button" @click="tab = '{{ $key }}'"
                                        class="px-4 py-1.5 text-xs font-medium rounded-md transition"
                                        :class="tab === '{{ $key }}' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-gray-100 bg-white">
                            {{-- Password Tab --}}
                            <div x-show="tab === 'password'">
                                <form action="{{ route('user.emails.password', $account) }}" method="POST" class="max-w-md space-y-4"
                                      x-data="{
                                          pwd: '',
                                          show: false,
                                          copied: false,
                                          gen() {
                                              const cs = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%^&*';
                                              let p = '';
                                              const arr = new Uint32Array(16);
                                              crypto.getRandomValues(arr);
                                              for (const n of arr) p += cs[n % cs.length];
                                              this.pwd = p;
                                              this.show = true;
                                          },
                                          async copy() {
                                              if (!this.pwd) return;
                                              try { await navigator.clipboard.writeText(this.pwd); this.copied = true; setTimeout(() => this.copied = false, 1500); } catch (e) {}
                                          }
                                      }">
                                    @csrf
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('emails.new_password') }}</label>
                                        <div class="flex items-stretch gap-1.5">
                                            <input :type="show ? 'text' : 'password'" name="password" x-model="pwd" placeholder="Min 8 characters"
                                                class="flex-1 min-w-0 rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                autocomplete="new-password">
                                            <button type="button" @click="show = !show"
                                                class="inline-flex items-center justify-center px-2.5 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg text-gray-600 transition"
                                                :title="show ? 'Hide' : 'Show'">
                                                <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                            </button>
                                            <button type="button" @click="gen()"
                                                class="inline-flex items-center justify-center px-2.5 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg text-indigo-700 transition"
                                                title="Generate random password">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            </button>
                                            <button type="button" @click="copy()" :disabled="!pwd"
                                                class="inline-flex items-center justify-center px-2.5 bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed border border-gray-300 rounded-lg text-gray-600 transition"
                                                :title="copied ? 'Copied!' : 'Copy'">
                                                <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                <svg x-show="copied" class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                                        {{ __('emails.update_password') }}
                                    </button>
                                </form>
                            </div>

                            {{-- Quota Tab --}}
                            <div x-show="tab === 'quota'" x-data="{ quotaOpt: '{{ $account->quota }}', customQ: '' }">
                                <form action="{{ route('user.emails.quota', $account) }}" method="POST" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="quota" :value="quotaOpt === 'custom' ? customQ : quotaOpt">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('emails.mailbox_quota') }}</label>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach(['100' => '100 MB', '500' => '500 MB', '1024' => '1 GB', '2560' => '2.5 GB', '5120' => '5 GB', '0' => 'Unlimited', 'custom' => 'Custom'] as $val => $lbl)
                                                <button type="button" @click="quotaOpt = '{{ $val }}'"
                                                    class="px-3 py-1.5 text-xs font-medium rounded-lg border transition"
                                                    :class="quotaOpt === '{{ $val }}' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                                                    {{ $lbl }}
                                                </button>
                                            @endforeach
                                        </div>
                                        <div x-show="quotaOpt === 'custom'" x-collapse class="mt-3">
                                            <div class="flex items-center gap-2">
                                                <input type="number" x-model="customQ" min="1" max="51200"
                                                    class="w-32 rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. 2048">
                                                <span class="text-sm text-gray-500">{{ __('common.mb') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                                        {{ __('emails.update_quota') }}
                                    </button>
                                </form>
                            </div>

                            {{-- Restrictions Tab --}}
                            <div x-show="tab === 'restrictions'">
                                <form action="{{ route('user.emails.restrictions', $account) }}" method="POST" class="space-y-5">
                                    @csrf

                                    {{-- Access toggles --}}
                                    <div class="space-y-3">
                                        <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wide">{{ __('emails.access') }}</h4>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <div class="relative">
                                                <input type="checkbox" name="can_send" value="1" @checked($account->can_send) class="sr-only peer">
                                                <div class="w-10 h-5 bg-gray-200 rounded-full peer-checked:bg-indigo-600 transition-colors"></div>
                                                <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow peer-checked:translate-x-5 transition-transform"></div>
                                            </div>
                                            <div>
                                                <span class="text-sm font-medium text-gray-700">{{ __('emails.can_send') }}</span>
                                                <p class="text-xs text-gray-400">{{ __('emails.allow_outgoing') }}</p>
                                            </div>
                                        </label>
                                        <label class="flex items-center space-x-3 cursor-pointer">
                                            <div class="relative">
                                                <input type="checkbox" name="can_receive" value="1" @checked($account->can_receive) class="sr-only peer">
                                                <div class="w-10 h-5 bg-gray-200 rounded-full peer-checked:bg-indigo-600 transition-colors"></div>
                                                <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow peer-checked:translate-x-5 transition-transform"></div>
                                            </div>
                                            <div>
                                                <span class="text-sm font-medium text-gray-700">{{ __('emails.can_receive') }}</span>
                                                <p class="text-xs text-gray-400">{{ __('emails.allow_incoming') }}</p>
                                            </div>
                                        </label>
                                    </div>

                                    {{-- Send limits --}}
                                    <div class="space-y-3">
                                        <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wide">{{ __('emails.send_limits') }}</h4>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('emails.per_hour') }}</label>
                                                <input type="number" name="max_send_per_hour" value="{{ $account->max_send_per_hour }}" min="0"
                                                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('emails.per_day') }}</label>
                                                <input type="number" name="max_send_per_day" value="{{ $account->max_send_per_day }}" min="0"
                                                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('emails.per_week') }}</label>
                                                <input type="number" name="max_send_per_week" value="{{ $account->max_send_per_week }}" min="0"
                                                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('emails.per_month') }}</label>
                                                <input type="number" name="max_send_per_month" value="{{ $account->max_send_per_month }}" min="0"
                                                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-400">{{ __('emails.zero_unlimited') }}</p>
                                    </div>

                                    <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                                        {{ __('emails.save_restrictions') }}
                                    </button>
                                </form>
                            </div>

                            {{-- Delete Tab --}}
                            <div x-show="tab === 'delete'">
                                <div class="max-w-md">
                                    <p class="text-sm text-gray-600 mb-4">{{ __('emails.permanently_delete_msg', ['email' => $account->email]) }}</p>
                                    <x-delete-modal
                                        :action="route('user.emails.destroy', $account)"
                                        :title="__('emails.delete_email_account')"
                                        :message="__('emails.delete_email_msg', ['email' => $account->email])"
                                        :confirm-password="true">
                                        <x-slot name="trigger">
                                            <button type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                {{ __('common.delete') }} {{ $account->email }}
                                            </button>
                                        </x-slot>
                                    </x-delete-modal>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- Mail Client Settings -->
    <div class="mt-6 bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">{{ __('emails.mail_client_settings') }}</h3>
            <p class="text-sm text-gray-500 mt-1">{{ __('emails.use_these_settings') }}</p>
        </div>
        <div class="px-6 py-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                <div>
                    <h4 class="font-semibold text-gray-700 mb-3">{{ __('emails.incoming_imap') }}</h4>
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('emails.server') }}</dt>
                            <dd class="font-mono text-gray-800">{{ request()->getHost() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('emails.port') }}</dt>
                            <dd class="font-mono text-gray-800">993 (SSL) / 143 (STARTTLS)</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('emails.username') }}</dt>
                            <dd class="font-mono text-gray-800">{{ __('emails.full_email_address') }}</dd>
                        </div>
                    </dl>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-700 mb-3">{{ __('emails.outgoing_smtp') }}</h4>
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('emails.server') }}</dt>
                            <dd class="font-mono text-gray-800">{{ request()->getHost() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('emails.port') }}</dt>
                            <dd class="font-mono text-gray-800">587 (STARTTLS) / 465 (SSL)</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('emails.authentication') }}</dt>
                            <dd class="font-mono text-gray-800">{{ __('emails.yes_same_credentials') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-user-layout>
