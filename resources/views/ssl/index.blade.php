<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">SSL Certificates</h2>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-center mb-6">
            <p class="text-gray-500 text-sm">Manage SSL certificates for your domains.</p>
            <a href="#" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Issue Certificate
            </a>
        </div>
        <p class="text-gray-400 text-sm">No certificates issued yet.</p>
    </div>
</x-app-layout>
