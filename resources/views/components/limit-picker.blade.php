@props(['name', 'label', 'value' => 0])

@php
    $currentValue = (int) old($name, $value);
    $isUnlimited = $currentValue === 0;
@endphp

<div x-data="{
    unlimited: {{ $isUnlimited ? 'true' : 'false' }},
    count: {{ $isUnlimited ? '""' : $currentValue }},
    get formValue() {
        return this.unlimited ? 0 : (this.count || 0);
    }
}">
    <label class="block text-sm font-medium text-gray-700 mb-3">{{ $label }}</label>

    <!-- Unlimited Toggle -->
    <div class="flex items-center justify-between mb-3 p-3 rounded-lg border transition"
        :class="unlimited ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200 bg-white'">
        <span class="text-sm font-medium" :class="unlimited ? 'text-indigo-700' : 'text-gray-600'">Unlimited</span>
        <button type="button" @click="unlimited = !unlimited; if(!unlimited) { $nextTick(() => $refs.input?.focus()); }"
            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            :class="unlimited ? 'bg-indigo-600' : 'bg-gray-300'">
            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                :class="unlimited ? 'translate-x-6' : 'translate-x-1'"></span>
        </button>
    </div>

    <!-- Count Input -->
    <div x-show="!unlimited" x-transition>
        <input type="number" x-ref="input" x-model="count" min="1"
            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Enter limit">
    </div>

    <!-- Hidden Form Input -->
    <input type="hidden" name="{{ $name }}" :value="formValue">

    @error($name)
        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
