<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Custom Nginx Directives</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <div class="mb-6">
        <p class="text-sm text-gray-500">Add custom Nginx configuration per domain (headers, proxy_pass, rewrites, etc.)</p>
    </div>

    @foreach($domains as $domain)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-5" x-data="{ open: {{ $domain->nginxDirective ? 'true' : 'false' }} }">
            <div class="px-6 py-4 flex items-center justify-between cursor-pointer" @click="open = !open">
                <div>
                    <div class="text-sm font-semibold text-gray-800">{{ $domain->domain }}</div>
                    <div class="text-xs text-gray-500">
                        @if($domain->nginxDirective)
                            <span class="text-green-600">Custom directives active</span>
                        @else
                            No custom directives
                        @endif
                    </div>
                </div>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </div>

            <div x-show="open" x-collapse class="border-t border-gray-100">
                <form action="{{ route('user.nginx-directives.store') }}" method="POST" class="px-6 py-5 space-y-4">
                    @csrf
                    <input type="hidden" name="domain_id" value="{{ $domain->id }}">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nginx Directives</label>
                        <textarea name="directives" rows="8"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="# Example: Add security headers
add_header X-Frame-Options SAMEORIGIN;
add_header X-Content-Type-Options nosniff;

# Example: Proxy pass
location /api {
    proxy_pass http://localhost:3000;
    proxy_set_header Host $host;
}">{{ $domain->nginxDirective?->directives }}</textarea>
                        <p class="mt-1.5 text-xs text-gray-400">These directives are included inside the server block. Use valid Nginx syntax.</p>
                    </div>

                    <div class="flex items-center space-x-3">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                            Save & Reload
                        </button>
                        @if($domain->nginxDirective)
                            <form action="{{ route('user.nginx-directives.destroy', $domain) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition">
                                    Remove
                                </button>
                            </form>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    @endforeach
</x-user-layout>
