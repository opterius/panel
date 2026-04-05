<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Email Settings</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.email-settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <!-- Default Quota -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Default Settings</h3>
                    <p class="text-sm text-gray-500 mt-1">These defaults apply to new email accounts.</p>
                </div>
                <div class="px-6 py-5 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Default Mailbox Quota (MB)</label>
                            <input type="number" name="email_default_quota" value="{{ $settings['email_default_quota'] ?? 500 }}" min="0" max="51200"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="text-xs text-gray-400 mt-1">0 = unlimited. Applied when creating new email accounts.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Max Accounts per Domain</label>
                            <input type="number" name="email_max_accounts_per_domain" value="{{ $settings['email_max_accounts_per_domain'] ?? 0 }}" min="0" max="10000"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="text-xs text-gray-400 mt-1">0 = unlimited.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Send Limits -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Global Send Limits</h3>
                    <p class="text-sm text-gray-500 mt-1">Default sending limits for new email accounts. Individual accounts can override these.</p>
                </div>
                <div class="px-6 py-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Max Emails per Hour</label>
                            <input type="number" name="email_max_send_per_hour" value="{{ $settings['email_max_send_per_hour'] ?? 100 }}" min="0" max="10000"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="text-xs text-gray-400 mt-1">0 = unlimited. Prevents spam abuse.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Max Emails per Day</label>
                            <input type="number" name="email_max_send_per_day" value="{{ $settings['email_max_send_per_day'] ?? 500 }}" min="0" max="100000"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="text-xs text-gray-400 mt-1">0 = unlimited.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Global Toggles -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Global Controls</h3>
                    <p class="text-sm text-gray-500 mt-1">Enable or disable email functionality server-wide.</p>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <label class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Email Sending</span>
                            <p class="text-xs text-gray-400 mt-0.5">Allow all accounts to send emails. Disable to block outgoing mail server-wide.</p>
                        </div>
                        <input type="checkbox" name="email_sending_enabled" value="1"
                            @checked(($settings['email_sending_enabled'] ?? '1') === '1')
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-5 w-5">
                    </label>

                    <label class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Email Receiving</span>
                            <p class="text-xs text-gray-400 mt-0.5">Allow all accounts to receive emails. Disable to block incoming mail server-wide.</p>
                        </div>
                        <input type="checkbox" name="email_receiving_enabled" value="1"
                            @checked(($settings['email_receiving_enabled'] ?? '1') === '1')
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-5 w-5">
                    </label>

                    <div class="p-4 border rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Max Attachment Size (MB)</label>
                        <input type="number" name="email_max_attachment_mb" value="{{ $settings['email_max_attachment_mb'] ?? 25 }}" min="1" max="100"
                            class="w-48 rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">Maximum file size for email attachments. Applies to Postfix message_size_limit.</p>
                    </div>
                </div>
            </div>

            <!-- Save -->
            <div class="flex items-center justify-end">
                <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    Save Email Settings
                </button>
            </div>
        </div>
    </form>
</x-admin-layout>
