<x-user-layout>
    <x-slot name="title">Hotlink Protection</x-slot>

    <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

        @if (session('success'))
            <div class="mb-6 rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900">Hotlink Protection</h1>
            <p class="text-slate-500 mt-1">Stop other websites from embedding your images and files. This blocks requests where the Referer header is from a domain not on your whitelist.</p>
        </div>

        @foreach ($domains as $domain)
            @php $hp = $domain->hotlinkProtection; @endphp
            <div class="bg-white rounded-2xl border border-slate-200 mb-6">
                <div class="flex items-center justify-between p-5 border-b border-slate-100">
                    <div>
                        <h2 class="font-bold text-slate-900">{{ $domain->domain }}</h2>
                        <p class="text-xs text-slate-500">
                            Status:
                            @if ($hp && $hp->enabled)
                                <span class="font-semibold text-green-600">enabled</span>
                            @else
                                <span class="font-semibold text-slate-500">disabled</span>
                            @endif
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('user.security.hotlink.update', $domain) }}" class="p-5 space-y-5">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="enabled" value="1" {{ $hp && $hp->enabled ? 'checked' : '' }}
                                   class="w-4 h-4 text-orange-500 border-slate-300 rounded focus:ring-orange-500">
                            <span class="text-sm font-semibold text-slate-700">Enable hotlink protection for this domain</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Allowed external domains (one per line)</label>
                        <textarea name="allowed_domains" rows="3"
                                  class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500"
                                  placeholder="cdn.example.com&#10;another-site.com">{{ $hp ? implode("\n", $hp->allowed_domains ?? []) : '' }}</textarea>
                        <p class="text-xs text-slate-500 mt-1">Your own domain ({{ $domain->domain }} and www.{{ $domain->domain }}) is always allowed automatically.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Protected file extensions (comma-separated)</label>
                        <input type="text" name="allowed_extensions"
                               value="{{ $hp ? implode(', ', $hp->allowed_extensions ?? []) : 'jpg, jpeg, png, gif, webp, svg' }}"
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500">
                        <p class="text-xs text-slate-500 mt-1">Default: images. Common: jpg, jpeg, png, gif, webp, svg, mp4, pdf, zip.</p>
                    </div>

                    <div>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="allow_direct" value="1"
                                   {{ ! $hp || $hp->allow_direct ? 'checked' : '' }}
                                   class="w-4 h-4 text-orange-500 border-slate-300 rounded focus:ring-orange-500">
                            <span class="text-sm text-slate-700">Allow direct access (visitors typing the URL or saving the image)</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Redirect blocked requests to (optional)</label>
                        <input type="url" name="redirect_url" value="{{ $hp->redirect_url ?? '' }}"
                               placeholder="https://{{ $domain->domain }}/blocked.png"
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500">
                        <p class="text-xs text-slate-500 mt-1">If empty, blocked requests return HTTP 403 Forbidden.</p>
                    </div>

                    <div class="flex justify-end pt-3 border-t border-slate-100">
                        <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        @endforeach

        @if ($domains->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-500">
                You have no domains yet.
            </div>
        @endif

    </div>
</x-user-layout>
