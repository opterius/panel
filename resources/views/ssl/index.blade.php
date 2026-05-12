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
                                @if(!empty($domain->wildcard_active))
                                    {{-- Wildcard cert exists on disk (independent of the apex's DB row).
                                         Shown as a separate badge so the customer always sees that
                                         *.domain is active for their subdomains, even after they
                                         issue a standard HTTP-01 cert for the apex. --}}
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium bg-violet-100 text-violet-700" title="Wildcard *.{{ $domain->domain }} active — covers every subdomain">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/></svg>
                                        Wildcard *.{{ $domain->domain }}
                                    </span>
                                @endif
                                <span x-show="!certActive && phase === 'idle'" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $info['statusColor'] }}">{{ $info['statusLabel'] }}</span>
                                <span x-show="phase === 'running'" x-cloak class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                    <svg class="w-3 h-3 mr-1 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    Issuing...
                                </span>
                                {{-- Standard SSL live indicator + details toggle --}}
                                <span x-show="sslPhase === 'running'" x-cloak class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-700">
                                    <svg class="w-3 h-3 mr-1 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    Issuing... <span class="ml-1 opacity-75" x-text="sslElapsed + 's'"></span>
                                </span>
                                <button type="button" x-show="sslPhase !== 'idle'" x-cloak
                                        @click="sslLogsOpen = !sslLogsOpen"
                                        class="inline-flex items-center px-2 py-1 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded transition">
                                    <span x-text="sslLogsOpen ? 'Hide details' : 'View details'"></span>
                                    <svg class="w-3 h-3 ml-1 transition-transform" :class="sslLogsOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            </div>
                            <div class="flex items-center space-x-2 ml-4">
                                {{-- Cancel button: visible while issuing (so users can abort stuck wildcard jobs) --}}
                                <template x-if="phase === 'running'">
                                    <button type="button" @click="cancelWildcard({{ $domain->id }})"
                                        class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 text-xs font-medium rounded-lg hover:bg-red-100 transition">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Cancel
                                    </button>
                                </template>
                                {{-- Buttons: only show when not actively issuing --}}
                                <template x-if="phase === 'idle' && !certActive && sslPhase === 'idle'">
                                    <div class="flex items-center gap-2">
                                        <form action="{{ route('user.ssl.issue') }}" method="POST"
                                              @submit.prevent="issueSSL($el)">
                                            @csrf
                                            <input type="hidden" name="domain_id" value="{{ $domain->id }}">
                                            <input type="hidden" name="email" value="{{ auth()->user()->email }}">
                                            <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition disabled:opacity-50">
                                                Issue SSL
                                            </button>
                                        </form>
                                        <button type="button" @click="confirmOpen = true"
                                            class="inline-flex items-center px-3 py-1.5 bg-violet-100 text-violet-700 text-xs font-medium rounded-lg hover:bg-violet-200 transition">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/></svg>
                                            Wildcard SSL
                                        </button>
                                    </div>
                                </template>
                                <template x-if="certActive && phase === 'idle'">
                                    <div class="flex items-center gap-2">
                                        @if($info['status'] === 'active' && ($info['cert']?->type ?? '') !== 'wildcard')
                                        <button type="button" @click="confirmOpen = true"
                                            class="inline-flex items-center px-3 py-1.5 bg-violet-100 text-violet-700 text-xs font-medium rounded-lg hover:bg-violet-200 transition">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/></svg>
                                            Wildcard SSL
                                        </button>
                                        @endif

                                        @if($info['status'] === 'active' && ($info['cert']?->type ?? '') === 'wildcard')
                                            {{-- Wildcard *.domain does NOT cover the apex (domain itself). Browsers
                                                 reject https://domain because the cert SAN list has only *.domain.
                                                 Offer a one-click HTTP-01 issuance for the apex; this rewrites the
                                                 apex nginx vhost to use the new apex cert while subdomains keep
                                                 using the wildcard. --}}
                                            <form action="{{ route('user.ssl.issue') }}" method="POST"
                                                  @submit.prevent="issueSSL($el)">
                                                @csrf
                                                <input type="hidden" name="domain_id" value="{{ $domain->id }}">
                                                <input type="hidden" name="email" value="{{ auth()->user()->email }}">
                                                <button type="submit"
                                                    class="inline-flex items-center px-3 py-1.5 bg-amber-100 text-amber-800 text-xs font-medium rounded-lg hover:bg-amber-200 transition"
                                                    title="Wildcard doesn't cover the apex — issue a separate cert for {{ $domain->domain }} + www.{{ $domain->domain }}">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
                                                    Issue apex SSL
                                                </button>
                                            </form>
                                        @endif
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

                        {{-- Wildcard confirmation modal --}}
                        <template x-teleport="body">
                            <div x-show="confirmOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" x-cloak>
                                <div x-show="confirmOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="confirmOpen = false"></div>
                                <div class="fixed inset-0 flex items-center justify-center p-4">
                                    <div x-show="confirmOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.stop @keydown.escape.window="confirmOpen = false" class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden">
                                        <div class="p-6">
                                            <div class="flex items-start space-x-4">
                                                <div class="w-12 h-12 rounded-full bg-violet-100 flex items-center justify-center shrink-0">
                                                    <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                </div>
                                                <div class="flex-1">
                                                    <h3 class="text-lg font-semibold text-gray-900">Issue Wildcard SSL</h3>
                                                    <p class="mt-1 text-sm text-gray-500">
                                                        Issue a wildcard certificate for <strong class="font-mono text-gray-700">*.{{ $domain->domain }}</strong>.
                                                        Covers every subdomain (api., mail., app., etc.) under this domain.
                                                        Uses DNS validation via PowerDNS; takes 1–3 minutes.
                                                        Any existing Let's Encrypt certificates on subdomains will be replaced.
                                                    </p>

                                                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3">
                                                        <div class="flex items-start gap-2">
                                                            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
                                                            <div class="text-sm text-amber-900">
                                                                <p class="font-semibold mb-1">Requires nameservers pointing to this server</p>
                                                                <p class="text-amber-800">
                                                                    Wildcard validation needs Let's Encrypt to read a DNS TXT record from <strong>this server's PowerDNS</strong>.
                                                                    Your domain's <code class="bg-amber-100 px-1 rounded">NS</code> records at the registrar must list this server's nameservers
                                                                    (not Linode, Cloudflare, GoDaddy, etc.).
                                                                </p>
                                                                <p class="mt-2 text-amber-800">
                                                                    If your NS is somewhere else, issuance will stall on "Waiting for DNS propagation" — cancel it and use <strong>Issue SSL</strong> (HTTP validation, no DNS changes needed) on each subdomain instead.
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-end space-x-3 px-6 py-5 bg-gray-50">
                                            <button type="button" @click="confirmOpen = false" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                                            <button type="button" @click="confirmOpen = false; startWildcard()" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white rounded-lg transition" style="background:#7c3aed">
                                                I understand — issue wildcard
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Standard SSL live log dropdown --}}
                        <div x-show="sslPhase !== 'idle' && sslLogsOpen" x-cloak x-collapse
                             class="mt-3 rounded-lg border border-gray-200 bg-gray-900 text-gray-100 overflow-hidden">
                            <div class="px-4 py-2 border-b border-gray-700 flex items-center justify-between">
                                <span class="text-sm font-semibold uppercase tracking-wide text-gray-300">Live SSL issuance log</span>
                                <span class="text-sm font-mono text-gray-400" x-text="sslElapsed + 's elapsed'"></span>
                            </div>
                            <div class="px-4 py-3 max-h-64 overflow-y-auto font-mono text-sm leading-relaxed"
                                 x-ref="sslLogPane"
                                 x-effect="sslLogs.length && $refs.sslLogPane && ($refs.sslLogPane.scrollTop = $refs.sslLogPane.scrollHeight)">
                                <template x-for="(line, i) in sslLogs" :key="i">
                                    <div x-text="line" :class="line.includes('ERROR') ? 'text-red-300' : 'text-gray-200'"></div>
                                </template>
                                <template x-if="sslPhase === 'done'">
                                    <div class="text-green-300 mt-1">✓ Certificate is now active. Reloading...</div>
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

                            {{-- Stall warning: same step for >2 minutes. The polling endpoint
                                 may have lost the job state (agent restart, in-memory map
                                 cleared) while the cert itself was actually issued. Tell the
                                 user to refresh — Laravel will read the DB and show truth. --}}
                            <div x-show="stalled && phase === 'running'" x-cloak class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                <p class="text-sm text-amber-800 font-medium mb-1">Taking longer than expected</p>
                                <p class="text-sm text-amber-700 mb-3">
                                    The progress hasn't advanced for over 2 minutes. The certificate may have actually been issued — refresh the page to see the latest status.
                                </p>
                                <button type="button" @click="window.location.reload()"
                                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition">
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Refresh page
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
        confirmOpen: false,
        pollTimer: null,
        elapsedTimer: null,
        stepChangedAt: 0,
        lastSeenStep: '',
        stalled: false,

        // Regular (non-wildcard) SSL progress — same UX (live log) as wildcard,
        // backed by the agent's /ssl/progress endpoint. Triggered when the
        // standard "Issue SSL" form submits.
        sslPhase: 'idle',           // 'idle' | 'running' | 'done' | 'error'
        sslLogs: [],
        sslLogsOpen: false,
        sslElapsed: 0,
        sslPollTimer: null,
        sslElapsedTimer: null,

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
            this.stepChangedAt = Date.now();
            this.lastSeenStep = '';
            this.poll();
        },

        async poll() {
            try {
                const res = await fetch(`{{ route('user.ssl.wildcard.progress') }}?domain_id=${domainId}`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) return;

                const data = await res.json();
                const step = data.step || 'starting';

                // Track step changes so we can detect a stall (agent stuck on
                // the same step too long → likely the goroutine died, the
                // process restarted, or the network call's hung).
                if (step !== this.lastSeenStep) {
                    this.lastSeenStep = step;
                    this.stepChangedAt = Date.now();
                    this.stalled = false;
                }

                this.currentStep = step;
                this.currentStepIndex = Math.max(0, stepOrder.indexOf(step));

                if (data.step === 'done') {
                    this.stopPolling();
                    this.certActive = true;
                    this.certLabel = 'Wildcard · Expires in 90 days';
                    this.phase = 'idle';
                    return;
                } else if (data.step === 'error') {
                    this.stopPolling();
                    this.phase = 'error';
                    this.errorMsg = data.error || data.message || 'Unknown error';
                    return;
                }

                // Stall: same step for >120s (agent advances steps roughly
                // every 30-60s on a healthy run). Surface a "Refresh page"
                // button so the user isn't stuck staring at a spinner that
                // will never resolve on its own.
                if (Date.now() - this.stepChangedAt > 120000) {
                    this.stalled = true;
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

        // Hook into the standard Issue SSL form: prevent the classic POST,
        // submit via fetch, then open the live log dropdown.
        async issueSSL(form) {
            this.sslPhase = 'running';
            this.sslLogs = ['[0s] Submitting issuance request...'];
            this.sslLogsOpen = true;
            this.sslElapsed = 0;

            try {
                const formData = new FormData(form);
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok && res.status >= 400 && res.status !== 302) {
                    this.sslPhase = 'error';
                    this.sslLogs.push('[ERROR] Request failed: HTTP ' + res.status);
                    return;
                }
            } catch (e) {
                this.sslPhase = 'error';
                this.sslLogs.push('[ERROR] Network: ' + e.message);
                return;
            }

            this.startSSLPolling();
        },

        startSSLPolling() {
            this.sslElapsedTimer = setInterval(() => { this.sslElapsed++; }, 1000);
            this.sslPollTimer    = setInterval(() => this.pollSSL(), 2000);
            this.pollSSL();
        },

        async pollSSL() {
            try {
                const res = await fetch(`{{ route('user.ssl.progress') }}?domain_id=${domainId}`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) return;
                const data = await res.json();
                if (Array.isArray(data.logs)) this.sslLogs = data.logs;
                if (data.step === 'done') {
                    this.sslPhase = 'done';
                    this.stopSSLPolling();
                    // Reload after a brief delay so the cert badge flips to Active.
                    setTimeout(() => window.location.reload(), 1200);
                } else if (data.step === 'error') {
                    this.sslPhase = 'error';
                    this.stopSSLPolling();
                }
            } catch {}
        },

        stopSSLPolling() {
            clearInterval(this.sslPollTimer);
            clearInterval(this.sslElapsedTimer);
        },

        async cancelWildcard(domainId) {
            this.stopPolling();
            try {
                await fetch(`{{ route('user.ssl.wildcard.cancel') }}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ domain_id: domainId }),
                });
            } catch {}
            this.phase = 'idle';
            this.elapsed = 0;
            this.errorMsg = '';
        },
    };
}
</script>
</x-user-layout>
