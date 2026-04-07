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
        @endphp

        <div class="space-y-5">
            @foreach($mainDomains as $domain)
                @php $info = $renderRow($domain); @endphp
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
                                    @if($info['cert'] && $info['cert']->expires_at)
                                        <div class="text-xs text-gray-500">
                                            {{ ucfirst($info['cert']->type ?? 'letsencrypt') }} &middot;
                                            Expires {{ $info['cert']->expires_at->format('M d, Y') }}
                                            @if($info['cert']->auto_renew)
                                                &middot; Auto-renew
                                            @endif
                                        </div>
                                    @else
                                        <div class="text-xs text-gray-400">Main domain</div>
                                    @endif
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
                                    <form action="{{ route('user.ssl.renew', $info['cert']) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Renew</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Subdomains --}}
                    @if($domain->subdomains->isNotEmpty())
                        <div class="divide-y divide-gray-50">
                            @foreach($domain->subdomains as $sub)
                                @php $subInfo = $renderRow($sub, true); @endphp
                                <div class="pl-12 pr-6 py-3 flex items-center justify-between bg-gray-50/50">
                                    <div class="flex items-center space-x-3 min-w-0 flex-1">
                                        @if($subInfo['status'] === 'active')
                                            <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                        @else
                                            <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm text-gray-700 truncate">{{ $sub->domain }}</div>
                                            @if($subInfo['cert'] && $subInfo['cert']->expires_at && $subInfo['status'] === 'active')
                                                <div class="text-xs text-gray-400">Expires {{ $subInfo['cert']->expires_at->format('M d, Y') }}</div>
                                            @endif
                                        </div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $subInfo['statusColor'] }}">
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
                                            <form action="{{ route('user.ssl.renew', $subInfo['cert']) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Renew</button>
                                            </form>
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
