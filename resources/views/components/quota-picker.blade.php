@props(['name', 'label', 'value' => 0, 'presets' => []])

@php
    $currentValue = old($name, $value);
    $isUnlimited = (int) $currentValue === 0;
@endphp

<div x-data="{
    unlimited: {{ $isUnlimited ? 'true' : 'false' }},
    selectedPreset: null,
    customValue: {{ $isUnlimited ? '""' : $currentValue }},
    presets: {{ json_encode($presets) }},
    init() {
        if (!this.unlimited && this.customValue) {
            let found = this.presets.find(p => p.mb == this.customValue);
            this.selectedPreset = found ? found.mb : 'custom';
        }
    },
    get formValue() {
        if (this.unlimited) return 0;
        if (this.selectedPreset === 'custom') return this.customValue || 0;
        return this.selectedPreset || 0;
    },
    selectPreset(mb) {
        this.selectedPreset = mb;
        this.customValue = '';
    }
}">
    <label class="block text-sm font-medium text-gray-700 mb-3">{{ $label }}</label>

    <!-- Unlimited Toggle -->
    <div class="flex items-center justify-between mb-4 p-3 rounded-lg border transition"
        :class="unlimited ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200 bg-white'">
        <span class="text-sm font-medium" :class="unlimited ? 'text-indigo-700' : 'text-gray-600'">Unlimited</span>
        <button type="button" @click="unlimited = !unlimited; if(unlimited) { selectedPreset = null; customValue = ''; }"
            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            :class="unlimited ? 'bg-indigo-600' : 'bg-gray-300'">
            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                :class="unlimited ? 'translate-x-6' : 'translate-x-1'"></span>
        </button>
    </div>

    <!-- Preset Options -->
    <div x-show="!unlimited" x-transition class="space-y-3">
        <div class="grid grid-cols-3 gap-2">
            @foreach($presets as $preset)
                <button type="button"
                    @click="selectPreset({{ $preset['mb'] }})"
                    class="px-3 py-2.5 border rounded-lg text-sm font-medium transition text-center"
                    :class="selectedPreset == {{ $preset['mb'] }}
                        ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                        : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                    {{ $preset['label'] }}
                </button>
            @endforeach

            <!-- Custom Option -->
            <button type="button"
                @click="selectedPreset = 'custom'; $nextTick(() => $refs.customInput?.focus())"
                class="px-3 py-2.5 border rounded-lg text-sm font-medium transition text-center"
                :class="selectedPreset === 'custom'
                    ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                    : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                Custom
            </button>
        </div>

        <!-- Custom Input -->
        <div x-show="selectedPreset === 'custom'" x-transition>
            <div class="flex items-center space-x-2">
                <input type="number" x-ref="customInput" x-model="customValue" min="1"
                    class="flex-1 rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Enter value in MB">
                <span class="text-sm text-gray-400 shrink-0">MB</span>
            </div>
            <p class="mt-1.5 text-xs text-gray-400">1024 MB = 1 GB &middot; 10240 MB = 10 GB</p>
        </div>
    </div>

    <!-- Hidden Form Input -->
    <input type="hidden" name="{{ $name }}" :value="formValue">

    @error($name)
        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
