@props([
    'action',
    'title' => 'Delete Item',
    'message' => 'Are you sure? This action cannot be undone.',
    'confirmPassword' => false,
    'confirmText' => null,  // Require user to type this exact text to enable delete
])

<div x-data="{
        open: false,
        password: '',
        typedConfirm: '',
        loading: false,
        error: '',
        requiredConfirm: @js($confirmText),
        canDelete() {
            if (this.requiredConfirm && this.typedConfirm !== this.requiredConfirm) return false;
            if (@js($confirmPassword) && this.password.length === 0) return false;
            return true;
        },
        reset() {
            this.open = false;
            this.error = '';
            this.password = '';
            this.typedConfirm = '';
        }
    }" x-cloak>
    <!-- Trigger -->
    <div @click="open = true">
        {{ $trigger }}
    </div>

    <!-- Modal Backdrop -->
    <template x-teleport="body">
        <div x-show="open" class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <!-- Overlay -->
            <div x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"
                @click="reset()">
            </div>

            <!-- Modal Panel -->
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div x-show="open"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    @keydown.escape.window="reset()"
                    @click.stop
                    class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">

                    <!-- Header -->
                    <div class="p-6 pb-0">
                        <div class="flex items-start space-x-4">
                            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                                <p class="mt-1 text-sm text-gray-500">{{ $message }}</p>
                            </div>
                        </div>
                    </div>

                    @if($confirmText)
                        <div class="px-6 pt-5">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Type <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs font-mono text-red-600">{{ $confirmText }}</code> to confirm
                            </label>
                            <input type="text" x-model="typedConfirm" autocomplete="off"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:border-red-500 focus:ring-red-500"
                                :placeholder="requiredConfirm">
                        </div>
                    @endif

                    @if($confirmPassword)
                        <div class="px-6 pt-5">
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1.5">Enter your password to confirm</label>
                            <input type="password" id="confirm_password" x-model="password" autocomplete="current-password"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-red-500 focus:ring-red-500"
                                placeholder="Your password"
                                @keydown.enter="if(canDelete()) { $refs.form.submit(); }">
                            <template x-if="error">
                                <p class="mt-1.5 text-sm text-red-600" x-text="error"></p>
                            </template>
                        </div>
                    @endif

                    <!-- Footer -->
                    <div class="flex items-center justify-end space-x-3 px-6 py-5 mt-2">
                        <button type="button"
                            @click="reset()"
                            class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            Cancel
                        </button>

                        <form action="{{ $action }}" method="POST" x-ref="form">
                            @csrf
                            @method('DELETE')
                            @if($confirmPassword)
                                <input type="hidden" name="password" :value="password">
                            @endif
                            <button type="submit" :disabled="!canDelete()"
                                @click.prevent="if(!canDelete()) { error = 'Confirmation incomplete.'; return; } $refs.form.submit();"
                                class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
