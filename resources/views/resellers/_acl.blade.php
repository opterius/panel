{{-- Reseller ACL Permissions --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-800">Feature Permissions</h3>
                <p class="text-sm text-gray-500 mt-1">Control what this reseller can do in the panel.</p>
            </div>
            <label class="flex items-center space-x-2 cursor-pointer" x-data
                @click="document.querySelectorAll('.acl-check').forEach(c => c.checked = !c.checked)">
                <span class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Toggle all</span>
            </label>
        </div>
    </div>
    <div class="px-6 py-5 space-y-6">
        @php
            $definitions = \App\Models\User::resellerAclDefinitions();
            $currentAcl = old('reseller_acl', $reseller->reseller_acl ?? []) ?: [];
            $allPerms = collect($definitions)->flatMap(fn($perms) => array_keys($perms))->toArray();
            $hasAll = empty($currentAcl) || count(array_intersect($currentAcl, $allPerms)) === count($allPerms);
        @endphp

        @foreach($definitions as $group => $permissions)
            <div>
                <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">{{ $group }}</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach($permissions as $key => $label)
                        <label class="flex items-center space-x-2.5 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="reseller_acl[]" value="{{ $key }}" class="acl-check rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                @checked(empty($currentAcl) || in_array($key, $currentAcl))>
                            <span class="text-sm text-gray-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
