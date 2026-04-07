<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">{{ __('ssl.ssl_certificates') }}</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <div class="mb-6">
        <p class="text-sm text-gray-500">SSL certificates for all your domains and subdomains. Free Let's Encrypt certificates auto-renew every 60 days.</p>
    </div>

    @if($mainDomains->isEmpty())
        <div class="bg-white rounded-xl shadow-sm px-6 py-16 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
            <h3 class="mt-4 text-base font-medium text-gray-700">No domains</h3>
            <p class="mt-2 text-sm text-gray-500">Create a domain first.</p>
        </div>
    @else
        @php
            // Helper to render an SSL row for a domain or subdomain
            $renderRow = function ($d, $isSub = false) {
                $cert = $d->sslCertificate;
                $status = $cert?->status ?? 'none';
                $statusColor = match($status) {
                    'active'  => 'bg-green-100 text-green-700',
                    'pending' => 'bg-amber-100 text-amber-700',
                    'error'   => 'bg-red-100 text-red-700',
                    default   => 'bg-gray-100 text-gray-500',
                };
                $statusLabel = match($status) {
                    'active'  => 'Active',
                    'pending' => 'Pending',
                    'error'   => 'Failed',
                    default   => 'Not installed',
                };
                return [
                    'cert' => $cert,
                    'status' => $status,
                    'statusColor' => $statusColor,
                    'statusLabel' => $statusLabel,
                ];
            };

            // Reusable secondary text so non-active rows still have a second line
            // and don't visually collapse next to active rows.
            $secondaryText = function ($info, $isSub = false) {
                if ($info['cert'] && $info['cert']->expires_at && $info['status'] === 'active') {
                    return [
                        'text' => ucfirst($info['cert']->type ?? 'letsencrypt')
                                  . ' · Expires ' . $info['cert']->expires_at->format('M d, Y')
                                  . ($info['cert']->auto_renew ? ' · Auto-renew' : ''),
                        'class' => 'text-gray-500',
                    ];
                }
                return match ($info['status']) {
                    'pending' => ['text' => 'Issuing certificate…', 'class' => 'text-amber-600'],
                    'error'   => ['text' => 'Certificate issuance failed', 'class' => 'text-red-600'],
                    default   => ['text' => $isSub ? 'No certificate installed' : 'Main domain', 'class' => 'text-gray-400'],
                };
            };
        @endphp

        <div class="space-y-5">
            @foreach($mainDomains as $domain)
                @php
                    $info = $renderRow($domain);
                    $sec = $secondaryText($info);
                @endphp
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    {{-- Main Domain Row --}}
                    <div class="px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3 min-w-0 flex-1">
                                @if($info['status'] === 'active')
                                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                @else
                                    <svg class="w-5 h-5 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-semibold text-gray-800 truncate">{{ $domain->domain }}</div>
                                    <div class="text-xs {{ $sec['class'] }}">{{ $sec['text'] }}</div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $info['statusColor'] }}">
                                    {{ $info['statusLabel'] }}
                                </span>
                            </div>
                            <div class="flex items-center space-x-2 ml-4">
                                @if($info['status'] !== 'active')
                                    <form action="{{ route('user.ssl.issue') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
                                        @csrf
                                        <input type="hidden" name="domain_id" value="{{ $domain->id }}">
                                        <input type="hidden" name="email" value="{{ auth()->user()->email }}">
                                        <button type="submit" :disabled="loading"
                                            class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition disabled:opacity-50">
                                            <svg x-show="loading" class="w-3 h-3 mr-1 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                            <span x-text="loading ? 'Issuing...' : 'Issue SSL'">Issue SSL</span>
                                        </button>
                                    </form>
                                @else
                                    <div x-data="{ open: false, loading: false }">
                                        <button type="button" @click="open = true"
                                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            Renew
                                        </button>

                                        <template x-teleport="body">
                                            <div x-show="open" class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" x-cloak>
                                                <div x-show="open"
                                                    x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                                    class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="open = false"></div>
                                                <div class="fixed inset-0 flex items-center justify-center p-4">
                                                    <div x-show="open"
                                                        x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                                        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                                        @click.stop @keydown.escape.window="open = false"
                                                        class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
                                                        <div class="p-6">
                                                            <div class="flex items-start space-x-4">
                                                                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
                                                                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                                </div>
                                                                <div>
                                                                    <h3 class="text-lg font-semibold text-gray-900">Renew SSL Certificate</h3>
                                                                    <p class="mt-1 text-sm text-gray-500">Renew the Let's Encrypt certificate for <strong class="font-mono text-gray-700">{{ $domain->domain }}</strong>? The new certificate will be valid for 90 days.</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center justify-end space-x-3 px-6 py-5 bg-gray-50">
                                                            <button type="button" @click="open = false"
                                                                class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                                                Cancel
                                                            </button>
                                                            <form action="{{ route('user.ssl.renew', $info['cert']) }}" method="POST" @submit="loading = true">
                                                                @csrf
                                                                <button type="submit" :disabled="loading"
                                                                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50">
                                                                    <svg x-show="loading" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                                    <span x-text="loading ? 'Renewing...' : 'Renew Certificate'">Renew Certificate</span>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Subdomains --}}
                    @if($domain->subdomains->isNotEmpty())
                        <div class="divide-y divide-gray-50">
                            @foreach($domain->subdomains as $sub)
                                @php
                                    $subInfo = $renderRow($sub, true);
                                    $subSec = $secondaryText($subInfo, true);
                                @endphp
                                <div class="pl-12 pr-6 py-4 flex items-center justify-between bg-gray-50/50">
                                    <div class="flex items-center space-x-3 min-w-0 flex-1">
                                        @if($subInfo['status'] === 'active')
                                            <svg class="w-5 h-5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                        @else
                                            <svg class="w-5 h-5 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm font-semibold text-gray-800 truncate">{{ $sub->domain }}</div>
                                            <div class="text-xs {{ $subSec['class'] }}">{{ $subSec['text'] }}</div>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $subInfo['statusColor'] }}">
                                            {{ $subInfo['statusLabel'] }}
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-2 ml-4">
                                        @if($subInfo['status'] !== 'active')
                                            <form action="{{ route('user.ssl.issue') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
                                                @csrf
                                                <input type="hidden" name="domain_id" value="{{ $sub->id }}">
                                                <input type="hidden" name="email" value="{{ auth()->user()->email }}">
                                                <button type="submit" :disabled="loading"
                                                    class="inline-flex items-center px-2.5 py-1 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 transition disabled:opacity-50">
                                                    <svg x-show="loading" class="w-3 h-3 mr-1 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                    <span x-text="loading ? 'Issuing...' : 'Issue SSL'">Issue SSL</span>
                                                </button>
                                            </form>
                                        @else
                                            <div x-data="{ open: false, loading: false }">
                                                <button type="button" @click="open = true"
                                                    class="inline-flex items-center px-2.5 py-1 bg-indigo-600 text-white text-xs font-medium rounded hover:bg-indigo-700 transition">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                    Renew
                                                </button>
                                                <template x-teleport="body">
                                                    <div x-show="open" class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" x-cloak>
                                                        <div x-show="open"
                                                            x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                                            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                                            class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="open = false"></div>
                                                        <div class="fixed inset-0 flex items-center justify-center p-4">
                                                            <div x-show="open"
                                                                x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                                                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                                                @click.stop @keydown.escape.window="open = false"
                                                                class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
                                                                <div class="p-6">
                                                                    <div class="flex items-start space-x-4">
                                                                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
                                                                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                                        </div>
                                                                        <div>
                                                                            <h3 class="text-lg font-semibold text-gray-900">Renew SSL Certificate</h3>
                                                                            <p class="mt-1 text-sm text-gray-500">Renew the Let's Encrypt certificate for <strong class="font-mono text-gray-700">{{ $sub->domain }}</strong>? The new certificate will be valid for 90 days.</p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="flex items-center justify-end space-x-3 px-6 py-5 bg-gray-50">
                                                                    <button type="button" @click="open = false"
                                                                        class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                                                        Cancel
                                                                    </button>
                                                                    <form action="{{ route('user.ssl.renew', $subInfo['cert']) }}" method="POST" @submit="loading = true">
                                                                        @csrf
                                                                        <button type="submit" :disabled="loading"
                                                                            class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50">
                                                                            <svg x-show="loading" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                                            <span x-text="loading ? 'Renewing...' : 'Renew Certificate'">Renew Certificate</span>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-user-layout>
