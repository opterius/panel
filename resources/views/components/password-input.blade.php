@props([
    'name' => 'password',
    'id' => null,
    'placeholder' => 'Min 8 characters',
    'minLength' => 8,
    'defaultLength' => 20,
])

@php
    $id = $id ?? $name;
@endphp

<div x-data="passwordInput({ length: {{ (int) $defaultLength }}, min: {{ (int) $minLength }} })" class="space-y-2">
    {{-- Input + action buttons --}}
    <div class="flex">
        <input x-ref="input"
               :type="visible ? 'text' : 'password'"
               name="{{ $name }}"
               id="{{ $id }}"
               x-model="value"
               @input="recalcStrength()"
               placeholder="{{ $placeholder }}"
               class="flex-1 min-w-0 rounded-l-lg border-gray-300 shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500" />

        {{-- Show/Hide toggle --}}
        <button type="button" @click="visible = !visible"
                class="inline-flex items-center justify-center w-10 border border-l-0 border-gray-300 bg-white text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition"
                :title="visible ? 'Hide password' : 'Show password'">
            <svg x-show="!visible" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <svg x-show="visible" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
        </button>

        {{-- Copy --}}
        <button type="button" @click="copy()"
                class="inline-flex items-center justify-center w-10 border border-l-0 border-gray-300 bg-white text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition relative"
                :title="copied ? 'Copied!' : 'Copy to clipboard'">
            <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            <svg x-show="copied" x-cloak class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
        </button>

        {{-- Generate --}}
        <button type="button" @click="generate()"
                class="inline-flex items-center px-3 rounded-r-lg border border-l-0 border-indigo-600 bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-700 transition shrink-0">
            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
            Generate
        </button>
    </div>

    {{-- Strength meter --}}
    <div x-show="value.length > 0" x-cloak class="space-y-1.5">
        <div class="flex items-center gap-2">
            <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full transition-all duration-200"
                     :class="strength.barClass"
                     :style="`width: ${strength.percent}%`"></div>
            </div>
            <span class="text-xs font-medium tabular-nums" :class="strength.textClass" x-text="strength.label"></span>
        </div>
    </div>

    {{-- Generator options (collapsible) --}}
    <details class="text-xs">
        <summary class="cursor-pointer text-gray-400 hover:text-gray-600 select-none inline-flex items-center">
            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
            Generator options
        </summary>
        <div class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200 space-y-3">
            {{-- Length slider --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-medium text-gray-700">Length</label>
                    <span class="text-xs font-mono text-gray-500 tabular-nums" x-text="opts.length + ' chars'"></span>
                </div>
                <input type="range" min="8" max="64" step="1" x-model.number="opts.length"
                       class="w-full h-1.5 bg-gray-200 rounded-full appearance-none cursor-pointer accent-indigo-600">
            </div>
            {{-- Character classes --}}
            <div class="grid grid-cols-2 gap-2">
                <label class="flex items-center text-xs text-gray-700 cursor-pointer">
                    <input type="checkbox" x-model="opts.lower" class="rounded border-gray-300 text-indigo-600 mr-2 focus:ring-indigo-500">
                    Lowercase (a–z)
                </label>
                <label class="flex items-center text-xs text-gray-700 cursor-pointer">
                    <input type="checkbox" x-model="opts.upper" class="rounded border-gray-300 text-indigo-600 mr-2 focus:ring-indigo-500">
                    Uppercase (A–Z)
                </label>
                <label class="flex items-center text-xs text-gray-700 cursor-pointer">
                    <input type="checkbox" x-model="opts.digits" class="rounded border-gray-300 text-indigo-600 mr-2 focus:ring-indigo-500">
                    Numbers (0–9)
                </label>
                <label class="flex items-center text-xs text-gray-700 cursor-pointer">
                    <input type="checkbox" x-model="opts.symbols" class="rounded border-gray-300 text-indigo-600 mr-2 focus:ring-indigo-500">
                    Symbols (!@#…)
                </label>
            </div>
        </div>
    </details>
</div>

@once
    <script>
    function passwordInput(initial) {
        return {
            value: '',
            visible: false,
            copied: false,
            opts: {
                length: initial.length,
                lower: true,
                upper: true,
                digits: true,
                symbols: true,
            },
            strength: { percent: 0, label: '', barClass: '', textClass: '' },

            generate() {
                // Always use crypto.getRandomValues — never Math.random — for any
                // value the user might trust as a real secret.
                const sets = [];
                if (this.opts.lower)   sets.push('abcdefghijklmnopqrstuvwxyz');
                if (this.opts.upper)   sets.push('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
                if (this.opts.digits)  sets.push('0123456789');
                // Skip ambiguous shell-meta chars (`"'\`$\` \\) so the password is
                // safe to paste into .env files and database connection URIs.
                if (this.opts.symbols) sets.push('!@#%^&*()-_=+[]{}:,.?');

                if (sets.length === 0) {
                    sets.push('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
                }

                const all = sets.join('');
                const len = Math.max(initial.min, this.opts.length);
                const out = new Array(len);
                const buf = new Uint32Array(len);
                crypto.getRandomValues(buf);

                // Guarantee at least one char from each enabled class so the
                // result actually meets the requested complexity.
                for (let i = 0; i < sets.length && i < len; i++) {
                    out[i] = sets[i][buf[i] % sets[i].length];
                }
                for (let i = sets.length; i < len; i++) {
                    out[i] = all[buf[i] % all.length];
                }
                // Fisher–Yates shuffle so the guaranteed chars aren't always at the front
                const shuf = new Uint32Array(len);
                crypto.getRandomValues(shuf);
                for (let i = len - 1; i > 0; i--) {
                    const j = shuf[i] % (i + 1);
                    [out[i], out[j]] = [out[j], out[i]];
                }

                this.value = out.join('');
                this.visible = true;
                this.recalcStrength();
            },

            recalcStrength() {
                const v = this.value;
                if (!v) {
                    this.strength = { percent: 0, label: '', barClass: '', textClass: '' };
                    return;
                }
                let score = 0;
                if (v.length >= 8)  score++;
                if (v.length >= 12) score++;
                if (v.length >= 16) score++;
                if (v.length >= 24) score++;
                if (/[a-z]/.test(v)) score++;
                if (/[A-Z]/.test(v)) score++;
                if (/[0-9]/.test(v)) score++;
                if (/[^a-zA-Z0-9]/.test(v)) score++;

                // Map score (0–8) to four buckets
                let label, bar, text;
                if (score <= 2)      { label = 'Weak';      bar = 'bg-red-500';    text = 'text-red-600'; }
                else if (score <= 4) { label = 'Fair';      bar = 'bg-amber-500';  text = 'text-amber-600'; }
                else if (score <= 6) { label = 'Strong';    bar = 'bg-green-500';  text = 'text-green-600'; }
                else                 { label = 'Excellent'; bar = 'bg-emerald-600'; text = 'text-emerald-700'; }

                this.strength = {
                    percent: Math.min(100, (score / 8) * 100),
                    label,
                    barClass: bar,
                    textClass: text,
                };
            },

            async copy() {
                if (!this.value) return;
                try {
                    await navigator.clipboard.writeText(this.value);
                    this.copied = true;
                    setTimeout(() => { this.copied = false; }, 1500);
                } catch (e) {
                    // Clipboard API may be unavailable on http:// — fall back to
                    // selecting the field so the user can copy manually.
                    this.visible = true;
                    if (this.$refs.input) this.$refs.input.select();
                }
            },
        };
    }
    </script>
@endonce
