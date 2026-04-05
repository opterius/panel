@props(['active' => false])

<a {{ $attributes->merge(['class' => 'flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition ' . ($active ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white')]) }}>
    {{ $icon }}
    <span>{{ $slot }}</span>
</a>
