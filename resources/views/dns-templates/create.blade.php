<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.dns-templates.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">{{ isset($template) ? 'Edit' : 'Create' }} DNS Template</h2>
        </div>
    </x-slot>

    @php $template = $template ?? null; @endphp

    <form action="{{ $template ? route('admin.dns-templates.update', $template) : route('admin.dns-templates.store') }}" method="POST"
          x-data="{
              records: {{ json_encode(old('records', $template?->records ?? [
                  ['name' => '{domain}', 'type' => 'A', 'content' => '{ip}', 'ttl' => 3600, 'priority' => 0],
                  ['name' => 'www.{domain}', 'type' => 'A', 'content' => '{ip}', 'ttl' => 3600, 'priority' => 0],
                  ['name' => '{domain}', 'type' => 'MX', 'content' => '{domain}', 'ttl' => 3600, 'priority' => 10],
                  ['name' => '{domain}', 'type' => 'TXT', 'content' => 'v=spf1 ip4:{ip} a mx ~all', 'ttl' => 3600, 'priority' => 0],
              ])) }},
              addRecord() {
                  this.records.push({name: '{domain}', type: 'A', content: '{ip}', ttl: 3600, priority: 0});
              },
              removeRecord(i) { this.records.splice(i, 1); }
          }">
        @csrf
        @if($template) @method('PUT') @endif

        <div class="max-w-3xl space-y-6">

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Template Details</h3>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Template Name</label>
                            <input type="text" name="name" value="{{ old('name', $template?->name) }}"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="e.g. Standard, Mail-Ready">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="is_default" value="1"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    @checked(old('is_default', $template?->is_default))>
                                <span class="text-sm text-gray-700">Set as default template</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">DNS Records</h3>
                            <p class="text-sm text-gray-500 mt-1">Use variables: <code class="bg-gray-100 px-1 rounded text-xs">{domain}</code> <code class="bg-gray-100 px-1 rounded text-xs">{ip}</code> <code class="bg-gray-100 px-1 rounded text-xs">{ns1}</code> <code class="bg-gray-100 px-1 rounded text-xs">{ns2}</code></p>
                        </div>
                        <button type="button" @click="addRecord()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">+ Add Record</button>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <template x-for="(rec, i) in records" :key="i">
                        <div class="flex items-center gap-2 mb-3">
                            <input type="text" :name="'records['+i+'][name]'" x-model="rec.name" placeholder="Name"
                                class="w-48 rounded-lg border-gray-300 shadow-sm text-xs font-mono focus:border-indigo-500 focus:ring-indigo-500">
                            <select :name="'records['+i+'][type]'" x-model="rec.type"
                                class="w-24 rounded-lg border-gray-300 shadow-sm text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                <option>A</option><option>AAAA</option><option>CNAME</option><option>MX</option>
                                <option>TXT</option><option>NS</option><option>SRV</option><option>CAA</option>
                            </select>
                            <input type="text" :name="'records['+i+'][content]'" x-model="rec.content" placeholder="Content"
                                class="flex-1 rounded-lg border-gray-300 shadow-sm text-xs font-mono focus:border-indigo-500 focus:ring-indigo-500">
                            <input type="number" :name="'records['+i+'][ttl]'" x-model="rec.ttl" placeholder="TTL"
                                class="w-20 rounded-lg border-gray-300 shadow-sm text-xs focus:border-indigo-500 focus:ring-indigo-500">
                            <input type="number" :name="'records['+i+'][priority]'" x-model="rec.priority" placeholder="Pri"
                                class="w-16 rounded-lg border-gray-300 shadow-sm text-xs focus:border-indigo-500 focus:ring-indigo-500">
                            <button type="button" @click="removeRecord(i)" class="text-gray-400 hover:text-red-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    {{ $template ? 'Save Changes' : 'Create Template' }}
                </button>
                <a href="{{ route('admin.dns-templates.index') }}" class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</x-admin-layout>
