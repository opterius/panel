<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Autoresponders</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <!-- Domain Selector -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5">
            <form method="GET" action="{{ route('user.autoresponders.index') }}" class="flex items-end gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Domain</label>
                    <select name="domain_id" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($domains as $domain)
                            <option value="{{ $domain->id }}" @selected($selectedDomain && $selectedDomain->id === $domain->id)>{{ $domain->domain }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">Manage</button>
            </form>
        </div>
    </div>

    @if($selectedDomain)
        @if(empty($emailAccounts))
            <div class="bg-white rounded-xl shadow-sm px-6 py-16 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                <h3 class="mt-4 text-base font-medium text-gray-700">No email accounts</h3>
                <p class="mt-2 text-sm text-gray-500">Create email accounts first before setting up autoresponders.</p>
                <a href="{{ route('user.emails.index') }}" class="mt-4 inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                    Go to Email Accounts
                </a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($emailAccounts as $acct)
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden"
                         x-data="{
                             open: {{ $acct['enabled'] ? 'true' : 'false' }},
                             enabled: {{ $acct['enabled'] ? 'true' : 'false' }},
                             subject: '{{ addslashes($acct['subject']) }}',
                             body: `{{ addslashes($acct['body']) }}`
                         }">
                        <!-- Email Header -->
                        <div class="px-6 py-4 flex items-center justify-between cursor-pointer" @click="open = !open">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $acct['enabled'] ? 'bg-green-100' : 'bg-gray-100' }}">
                                    <svg class="w-5 h-5 {{ $acct['enabled'] ? 'text-green-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-800">{{ $acct['email'] }}</div>
                                    <div class="text-xs text-gray-500">
                                        @if($acct['enabled'])
                                            <span class="text-green-600">Autoresponder active</span>
                                        @else
                                            No autoresponder
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                @if($acct['enabled'])
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                                @endif
                                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>

                        <!-- Autoresponder Form -->
                        <div x-show="open" x-collapse class="border-t border-gray-100">
                            <form action="{{ route('user.autoresponders.store') }}" method="POST" class="px-6 py-5 space-y-4">
                                @csrf
                                <input type="hidden" name="domain_id" value="{{ $selectedDomain->id }}">
                                <input type="hidden" name="email" value="{{ $acct['email'] }}">

                                <div class="flex items-center space-x-3">
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" name="enabled" value="1" x-model="enabled"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-sm font-medium text-gray-700">Enable autoresponder</span>
                                    </label>
                                </div>

                                <div x-show="enabled" x-collapse class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Subject</label>
                                        <input type="text" name="subject" x-model="subject"
                                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="e.g. I'm currently out of office">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Message</label>
                                        <textarea name="body" x-model="body" rows="4"
                                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Thank you for your email. I'm currently away and will respond when I return."></textarea>
                                        <p class="mt-1 text-xs text-gray-400">This message is sent once per day to each sender.</p>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-3 pt-2">
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                                        Save
                                    </button>
                                    @if($acct['enabled'])
                                        <button type="submit" name="enabled" value="0"
                                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition">
                                            Disable
                                        </button>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</x-user-layout>
