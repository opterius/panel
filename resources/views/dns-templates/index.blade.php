<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">DNS Templates</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <p class="text-sm text-gray-500">Default DNS records auto-applied when creating new domains.</p>
        <a href="{{ route('admin.dns-templates.create') }}" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
            Create Template
        </a>
    </div>

    @if($templates->isEmpty())
        <div class="bg-white rounded-xl shadow-sm px-6 py-16 text-center">
            <h3 class="text-base font-medium text-gray-700">No DNS templates</h3>
            <p class="mt-2 text-sm text-gray-500">Create a template to auto-populate DNS records for new domains.</p>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Records</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Packages</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Default</th>
                        <th class="px-6 py-3 w-24"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($templates as $t)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $t->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ count($t->records ?? []) }} records</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $t->packages_count }} packages</td>
                            <td class="px-6 py-4">
                                @if($t->is_default)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Default</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-3">
                                    <a href="{{ route('admin.dns-templates.edit', $t) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                                    <form action="{{ route('admin.dns-templates.destroy', $t) }}" method="POST" class="inline" onsubmit="return confirm('Delete this template?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-600 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-admin-layout>
