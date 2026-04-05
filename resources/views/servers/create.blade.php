<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Add Server</h2>
    </x-slot>

    <div class="max-w-2xl">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="mb-6">
                <h3 class="text-base font-semibold text-gray-800">Server Details</h3>
                <p class="text-sm text-gray-500 mt-1">Enter your server's IP address to connect it to Opterius.</p>
            </div>

            <form action="{{ route('servers.store') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Server Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="e.g. Production Server">
                    @error('name')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- IP Address -->
                <div>
                    <label for="ip_address" class="block text-sm font-medium text-gray-700 mb-1.5">IP Address</label>
                    <input type="text" name="ip_address" id="ip_address" value="{{ old('ip_address') }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="e.g. 192.168.1.100">
                    @error('ip_address')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Hostname -->
                <div>
                    <label for="hostname" class="block text-sm font-medium text-gray-700 mb-1.5">Hostname <span class="text-gray-400">(optional)</span></label>
                    <input type="text" name="hostname" id="hostname" value="{{ old('hostname') }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="e.g. server1.example.com">
                    @error('hostname')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Actions -->
                <div class="flex items-center space-x-3 pt-4 border-t border-gray-100">
                    <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        Add Server
                    </button>
                    <a href="{{ route('servers.index') }}" class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Info Box -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-5">
            <h4 class="text-sm font-semibold text-blue-800 mb-2">How it works</h4>
            <ol class="text-sm text-blue-700 space-y-1.5 list-decimal list-inside">
                <li>Add your server's IP address above</li>
                <li>Install the Opterius agent on your server using the command shown after adding</li>
                <li>The agent connects back to this panel automatically</li>
                <li>Start managing domains, databases, and more</li>
            </ol>
        </div>
    </div>
</x-app-layout>
