<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Email Accounts</h2>
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

    <!-- Webmail Button -->
    <div class="mb-6 flex justify-end">
        <a href="{{ str_replace('SERVER_IP', request()->getHost(), config('opterius.webmail_url')) }}"
           target="_blank"
           class="inline-flex items-center px-4 py-2.5 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
            Open Webmail
        </a>
    </div>

    <!-- Create Email Account -->
    @if($domains->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Create Email Account</h3>
            </div>
            <form action="{{ route('user.emails.store') }}" method="POST" class="px-6 py-5"
                  x-data="{
                      username: '',
                      password: '',
                      selectedDomain: '{{ $domains->first()->domain }}',
                      quotaOption: '500',
                      customQuota: '',
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
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
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
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Domain</label>
                            <select name="domain_id" x-on:change="selectedDomain = $event.target.options[$event.target.selectedIndex].text"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($domains as $domain)
                                    <option value="{{ $domain->id }}">{{ $domain->domain }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Preview</label>
                            <div class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-mono text-gray-600 truncate">
                                <span x-text="(cleanUsername || 'user') + '@' + selectedDomain"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Row 2: Password with strength meter --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <input type="password" name="password" x-model="password"
                                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Min 8 characters">
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
                        <p class="mt-1.5 text-xs text-gray-400">Use uppercase, lowercase, numbers, and symbols for a strong password.</p>
                    </div>

                    {{-- Row 3: Quota picker --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mailbox Quota</label>
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
                                <span class="text-sm text-gray-500">MB</span>
                            </div>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="submit"
                            :disabled="!cleanUsername || usernameError || passwordStrength.score < 3"
                            class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                            Create Email Account
                        </button>
                        <p class="text-xs text-gray-400" x-show="passwordStrength.score > 0 && passwordStrength.score < 3">
                            Password must be at least "Good" strength to create.
                        </p>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <!-- Email Accounts List -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">Email Accounts</h3>
            <p class="text-sm text-gray-500 mt-1">Manage email accounts for your domains.</p>
        </div>

        @if($emailAccounts->isEmpty())
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                <h3 class="mt-4 text-base font-medium text-gray-700">No email accounts</h3>
                <p class="mt-2 text-sm text-gray-500">Create your first email account above.</p>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($emailAccounts as $account)
                    <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition"
                         x-data="{ showPassword: false }">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">{{ $account->email }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ $account->domain->domain }}
                                    &middot; Quota: {{ $account->quota > 0 ? $account->quota . ' MB' : 'Unlimited' }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                {{ ucfirst($account->status) }}
                            </span>

                            <!-- Change Password -->
                            <button @click="showPassword = !showPassword" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium transition">
                                Password
                            </button>

                            <!-- Delete -->
                            <x-delete-modal
                                :action="route('user.emails.destroy', $account)"
                                title="Delete Email Account"
                                message="Are you sure you want to delete {{ $account->email }}? All emails in this mailbox will be permanently deleted."
                                :confirm-password="true">
                                <x-slot name="trigger">
                                    <button type="button" class="text-gray-400 hover:text-red-600 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </x-slot>
                            </x-delete-modal>
                        </div>
                    </div>

                    <!-- Inline password change form -->
                    <div x-show="showPassword" x-collapse class="px-6 py-3 bg-gray-50 border-b border-gray-100">
                        <form action="{{ route('user.emails.password', $account) }}" method="POST" class="flex items-end gap-3">
                            @csrf
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-600 mb-1">New Password for {{ $account->email }}</label>
                                <input type="password" name="password" placeholder="Min 8 characters"
                                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                                Update
                            </button>
                            <button type="button" @click="showPassword = false" class="px-4 py-2.5 text-sm text-gray-600 hover:text-gray-800 transition">
                                Cancel
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Mail Client Settings -->
    <div class="mt-6 bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">Mail Client Settings</h3>
            <p class="text-sm text-gray-500 mt-1">Use these settings in Outlook, Thunderbird, or your phone.</p>
        </div>
        <div class="px-6 py-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                <div>
                    <h4 class="font-semibold text-gray-700 mb-3">Incoming Mail (IMAP)</h4>
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Server</dt>
                            <dd class="font-mono text-gray-800">{{ request()->getHost() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Port</dt>
                            <dd class="font-mono text-gray-800">993 (SSL) / 143 (STARTTLS)</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Username</dt>
                            <dd class="font-mono text-gray-800">Full email address</dd>
                        </div>
                    </dl>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-700 mb-3">Outgoing Mail (SMTP)</h4>
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Server</dt>
                            <dd class="font-mono text-gray-800">{{ request()->getHost() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Port</dt>
                            <dd class="font-mono text-gray-800">587 (STARTTLS) / 465 (SSL)</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Authentication</dt>
                            <dd class="font-mono text-gray-800">Yes (same credentials)</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-user-layout>
