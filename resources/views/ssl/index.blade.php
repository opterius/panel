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
                    $isWildcardPending = ($info['status'] === 'pending' && ($domain->sslCertificate?->type === 'wildcard'));
                @endphp
                <div class="bg-white rounded-xl shadow-sm overflow-hidden"
                     x-data="wildcardSsl({{ $domain->id }}, '{{ $isWildcardPending ? 'pending' : 'idle' }}')"
                     x-init="init()">
                    {{-- Main Domain Row --}}
                    <div class="px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3 min-w-0 flex-1">
                                <svg x-show="certActive" class="w-5 h-5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                <svg x-show="!certActive" class="w-5 h-5 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-semibold text-gray-800 truncate">{{ $domain->domain }}</div>
                                    <div class="text-xs" :class="certActive ? 'text-gray-500' : '{{ $sec['class'] }}'">
                                        <span x-show="certActive" x-text="certLabel"></span>
                                        <span x-show="!certActive">{{ $sec['text'] }}</span>
                                    </div>
                                </div>
                                <span x-show="certActive" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                                <span x-show="!certActive && phase === 'idle'" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $info['statusColor'] }}">{{ $info['statusLabel'] }}</span>
                                <span x-show="phase === 'running'" x-cloak class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                    <svg class="w-3 h-3 mr-1 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    Issuing...
                                </span>
                            </div>
                            <div class="flex items-center space-x-2 ml-4">
                                {{-- Buttons: only show when not actively issuing --}}
                                <template x-if="phase === 'idle' && !certActive">
                                    <div class="flex items-center gap-2">
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
                                        <button type="button" @click="startWildcard()"
                                            class="inline-flex items-center px-3 py-1.5 bg-violet-600 text-white text-xs font-medium rounded-lg hover:bg-violet-700 transition">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/></svg>
                                            Wildcard SSL
                                        </button>
                                    </div>
                                </template>
                                <template x-if="certActive">
                                    <div class="flex items-center gap-2">
                                        @if($info['status'] === 'active')
                                        <div x-data="{ open: false, loading: false }">
                                            <button type="button" @click="open = true"
                                                class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                Renew
                                            </button>
                                            <template x-teleport="body">
                                                <div x-show="open" class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" x-cloak>
                                                    <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="open = false"></div>
                                                    <div class="fixed inset-0 flex items-center justify-center p-4">
                                                        <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.stop @keydown.escape.window="open = false" class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
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
                                                                <button type="button" @click="open = false" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                                                                <form action="{{ route('user.ssl.renew', $info['cert']) }}" method="POST" @submit="loading = true">
                                                                    @csrf
                                                                    <button type="submit" :disabled="loading" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50">
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
                                        <x-delete-modal
                                            :action="route('user.ssl.destroy', $info['cert'])"
                                            title="Delete SSL Certificate"
                                            :message="'Delete the SSL certificate for ' . $domain->domain . '? The site will revert to HTTP until you issue a new certificate.'"
                                            :confirm-password="true">
                                            <x-slot name="trigger">
                                                <button type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    Delete
                                                </button>
                                            </x-slot>
                                        </x-delete-modal>
                                        @endif
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Wildcard progress panel --}}
                        <div x-show="phase === 'running' || phase === 'error'" x-cloak
                             class="mt-4 rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <div class="space-y-2">
                                @php
                                    $steps = [
                                        'starting'        => 'Preparing...',
                                        'dns_challenge'   => 'Creating DNS TXT challenge record',
                                        'dns_propagation' => 'Waiting for DNS propagation',
                                        'le_verify'       => 'Verifying with Let\'s Encrypt',
                                        'installing'      => 'Installing certificate',
                                        'done'            => 'Certificate active',
                                    ];
                                    $stepKeys = array_keys($steps);
                                @endphp
                                @foreach($steps as $key => $label)
                                    @php $idx = array_search($key, $stepKeys); @endphp
                                    <div class="flex items-center gap-2.5 text-sm"
                                         x-bind:class="{
                                             'text-gray-800': currentStepIndex >= {{ $idx }},
                                             'text-gray-400': currentStepIndex < {{ $idx }}
                                         }">
                                        {{-- done --}}
                                        <svg x-show="currentStepIndex > {{ $idx }}"
                                             class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        {{-- active spinner --}}
                                        <svg x-show="currentStepIndex === {{ $idx }} && phase === 'running'"
                                             class="w-4 h-4 text-violet-500 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                        </svg>
                                        {{-- error --}}
                                        <svg x-show="currentStepIndex === {{ $idx }} && phase === 'error'"
                                             class="w-4 h-4 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        {{-- pending dot --}}
                                        <div x-show="currentStepIndex < {{ $idx }}"
                                             class="w-4 h-4 rounded-full border-2 border-gray-300 shrink-0"></div>

                                        <span>{{ $label }}</span>

                                        {{-- elapsed timer on DNS propagation step --}}
                                        @if($key === 'dns_propagation')
                                            <span x-show="currentStepIndex === {{ $idx }} && elapsed > 0"
                                                  class="ml-auto text-xs text-gray-400 tabular-nums"
                                                  x-text="elapsed + 's'"></span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            {{-- Error message --}}
                            <div x-show="phase === 'error'" x-cloak class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-xs text-red-700 font-medium mb-1">Error details</p>
                                <pre class="text-xs text-red-600 whitespace-pre-wrap break-words" x-text="errorMsg"></pre>
                                <button type="button" @click="retryWildcard()"
                                    class="mt-2 inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
                                    Retry
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Subdomains --}}
                    @if($domain->subdomains->isNotEmpty())
                        @php $parentIsWildcard = $domain->sslCertificate?->type === 'wildcard' && $domain->sslCertificate?->status === 'active'; @endphp
                        <div class="divide-y divide-gray-50">
                            @foreach($domain->subdomains as $sub)
                                @php
                                    $subInfo = $renderRow($sub, true);
                                    $subSec = $secondaryText($subInfo, true);
                                    $coveredByWildcard = $parentIsWildcard;
                                @endphp
                                <div class="pl-12 pr-6 py-4 flex items-center justify-between bg-gray-50/50">
                                    <div class="flex items-center space-x-3 min-w-0 flex-1">
                                        <svg class="w-5 h-5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm font-semibold text-gray-800 truncate">{{ $sub->domain }}</div>
                                            <div class="text-xs {{ $coveredByWildcard ? 'text-violet-500' : $subSec['class'] }}">
                                                {{ $coveredByWildcard ? 'Covered by wildcard *.'. $domain->domain : $subSec['text'] }}
                                            </div>
                                        </div>
                                        @if($coveredByWildcard)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-violet-100 text-violet-700">Wildcard</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $subInfo['statusColor'] }}">
                                                {{ $subInfo['statusLabel'] }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center space-x-2 ml-4">
                                        @if(!$coveredByWildcard && $subInfo['status'] !== 'active')
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

<script>
function wildcardSsl(domainId, initialPhase) {
    const stepOrder = ['starting', 'dns_challenge', 'dns_propagation', 'le_verify', 'installing', 'done'];

    return {
        phase: initialPhase === 'pending' ? 'running' : 'idle',
        currentStep: initialPhase === 'pending' ? 'starting' : '',
        currentStepIndex: 0,
        elapsed: 0,
        errorMsg: '',
        certActive: false,
        certLabel: '',
        pollTimer: null,
        elapsedTimer: null,

        init() {
            @foreach($mainDomains as $d)
                @if($d->sslCertificate?->status === 'active')
                if (domainId === {{ $d->id }}) {
                    this.certActive = true;
                    this.certLabel = '{{ addslashes(($d->sslCertificate->type === 'wildcard' ? 'Wildcard · ' : '') . 'Expires ' . ($d->sslCertificate->expires_at?->format('M d, Y') ?? '')) }}';
                }
                @endif
            @endforeach

            if (this.phase === 'running') {
                this.startPolling();
            }
        },

        async startWildcard() {
            this.phase = 'running';
            this.currentStep = 'starting';
            this.currentStepIndex = 0;
            this.elapsed = 0;
            this.errorMsg = '';

            const res = await fetch('{{ route('user.ssl.wildcard.issue') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ domain_id: domainId }),
            });

            if (!res.ok) {
                const data = await res.json();
                this.phase = 'error';
                this.errorMsg = data.error || 'Failed to start wildcard SSL issuance.';
                return;
            }

            this.startPolling();
        },

        startPolling() {
            this.elapsedTimer = setInterval(() => { this.elapsed++; }, 1000);
            this.pollTimer = setInterval(() => this.poll(), 3000);
            this.poll();
        },

        async poll() {
            try {
                const res = await fetch(`{{ route('user.ssl.wildcard.progress') }}?domain_id=${domainId}`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) return;

                const data = await res.json();
                this.currentStep = data.step || 'starting';
                this.currentStepIndex = Math.max(0, stepOrder.indexOf(this.currentStep));

                if (data.step === 'done') {
                    this.stopPolling();
                    this.certActive = true;
                    this.certLabel = 'Wildcard · Expires in 90 days';
                    this.phase = 'idle';
                } else if (data.step === 'error') {
                    this.stopPolling();
                    this.phase = 'error';
                    this.errorMsg = data.error || data.message || 'Unknown error';
                }
            } catch {}
        },

        stopPolling() {
            clearInterval(this.pollTimer);
            clearInterval(this.elapsedTimer);
        },

        retryWildcard() {
            this.phase = 'idle';
            this.errorMsg = '';
            this.elapsed = 0;
        },
    };
}
</script>
</x-user-layout>
