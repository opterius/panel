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
                  x-data="{ username: '', selectedDomain: '{{ $domains->first()->domain }}' }">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                        <input type="text" name="username" x-model="username"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="info">
                        @error('username')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-1 flex items-end justify-center pb-2.5">
                        <span class="text-lg text-gray-400 font-bold">@</span>
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Domain</label>
                        <select name="domain_id" x-on:change="selectedDomain = $event.target.options[$event.target.selectedIndex].text"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($domains as $domain)
                                <option value="{{ $domain->id }}">{{ $domain->domain }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                        <input type="password" name="password"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Min 8 chars">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Quota</label>
                        <input type="number" name="quota" value="500" placeholder="MB"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-2 flex items-end">
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                            Create
                        </button>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-400">
                    Preview: <span class="font-mono" x-text="(username || 'user') + '@' + selectedDomain"></span>
                </p>
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
