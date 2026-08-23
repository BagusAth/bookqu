<div
    x-data="{
        toasts: [],
        add(type, message) {
            const id = Date.now();
            this.toasts.push({ id, type, message });
            setTimeout(() => this.remove(id), 5000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    @toast.window="add($event.detail.type, $event.detail.message)"
    x-init="
        @if(session('sukses')) add('success', '{{ session('sukses') }}'); @endif
        @if(session('pesan')) add('warning', '{{ session('pesan') }}'); @endif
        @if(session('error')) add('error', '{{ session('error') }}'); @endif
    "
    class="fixed top-4 right-4 z-[100] flex flex-col gap-2 pointer-events-none"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-full"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex w-full max-w-sm items-center gap-3 rounded-xl border px-4 py-3 shadow-lg bg-white"
            :class="{
                'border-emerald-200 bg-emerald-50': toast.type === 'success',
                'border-rose-200 bg-rose-50': toast.type === 'error',
                'border-amber-200 bg-amber-50': toast.type === 'warning'
            }"
        >
            <!-- Icon Success -->
            <svg x-show="toast.type === 'success'" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <!-- Icon Error -->
            <svg x-show="toast.type === 'error'" class="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <!-- Icon Warning -->
            <svg x-show="toast.type === 'warning'" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>

            <!-- Message -->
            <p class="text-sm font-medium flex-1"
                :class="{
                    'text-emerald-800': toast.type === 'success',
                    'text-rose-800': toast.type === 'error',
                    'text-amber-800': toast.type === 'warning'
                }" x-text="toast.message"></p>

            <!-- Close Button -->
            <button @click="remove(toast.id)" class="text-gray-400 hover:text-gray-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </template>
</div>
