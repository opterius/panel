<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.packages.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">Edit Package — {{ $package->name }}</h2>
        </div>
    </x-slot>

    @include('packages._form', ['package' => $package, 'action' => route('admin.packages.update', $package), 'method' => 'PUT'])
</x-admin-layout>
