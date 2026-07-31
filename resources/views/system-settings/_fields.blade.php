{{--
    Shared field partials for System Settings.

    Every category form is built from these so the categories stay visually
    consistent and adding a setting is one @include rather than a block of
    hand-written markup.

    Usage:
      @include('system-settings._fields', ['field' => 'text', 'name' => 'panel_name', ...])
--}}

@php
    $value = old($name, $settings[$name] ?? '');
    $inputClass = 'w-full max-w-md rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500';
@endphp

<div class="mb-6">
    @if($field !== 'checkbox')
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1.5">{{ $label }}</label>
        @if(!empty($hint))
            <p class="text-xs text-gray-500 mb-2">{{ $hint }}</p>
        @endif
    @endif

    @switch($field)
        @case('select')
            <select name="{{ $name }}" id="{{ $name }}" class="{{ $inputClass }}">
                @foreach($options as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $optLabel }}</option>
                @endforeach
            </select>
            @break

        @case('textarea')
            <textarea name="{{ $name }}" id="{{ $name }}" rows="{{ $rows ?? 3 }}"
                      class="{{ $inputClass }} font-mono text-xs">{{ $value }}</textarea>
            @break

        @case('checkbox')
            <label for="{{ $name }}" class="flex items-start gap-3 cursor-pointer">
                {{-- Hidden field first so an unchecked box still submits a value. --}}
                <input type="hidden" name="{{ $name }}" value="0">
                <input type="checkbox" name="{{ $name }}" id="{{ $name }}" value="1"
                       @checked((string) $value === '1')
                       class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                <span>
                    <span class="block text-sm font-medium text-gray-700">{{ $label }}</span>
                    @if(!empty($hint))
                        <span class="block text-xs text-gray-500 mt-0.5">{{ $hint }}</span>
                    @endif
                </span>
            </label>
            @break

        @case('color')
            <div class="flex items-center gap-3">
                <input type="color" name="{{ $name }}" id="{{ $name }}" value="{{ $value ?: '#4f46e5' }}"
                       class="h-9 w-16 rounded border-gray-300 shadow-sm cursor-pointer">
                <code class="text-xs text-gray-500">{{ $value }}</code>
            </div>
            @break

        @case('password')
            <input type="password" name="{{ $name }}" id="{{ $name }}" value="" autocomplete="new-password"
                   placeholder="{{ $value !== '' ? '••••••••  (leave blank to keep)' : '' }}"
                   class="{{ $inputClass }}">
            @break

        @default
            <input type="{{ $field }}" name="{{ $name }}" id="{{ $name }}" value="{{ $value }}"
                   @if(isset($min)) min="{{ $min }}" @endif
                   @if(isset($max)) max="{{ $max }}" @endif
                   @if(isset($step)) step="{{ $step }}" @endif
                   class="{{ $inputClass }}">
    @endswitch

    @error($name)
        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
