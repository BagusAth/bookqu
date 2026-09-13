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
        @if(session('sukses')) add('success', '{{ addslashes(session('sukses')) }}'); @endif
        @if(session('pesan')) add('warning', '{{ addslashes(session('pesan')) }}'); @endif
        @if(session('error')) add('error', '{{ addslashes(session('error')) }}'); @endif
        @if(isset($errors) && $errors->any()) add('error', '{{ addslashes($errors->first()) }}'); @endif
    "
    class="fixed top-4 right-4 z-[120] flex flex-col gap-2.5 pointer-events-none"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0 translate-y-[-8px] scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="pointer-events-auto flex w-full max-w-sm items-center gap-3 rounded-2xl border px-4 py-3 shadow-[0_10px_30px_rgba(35,26,61,0.12)] bg-white"
            :class="{
                'border-emerald-200 bg-emerald-50/80': toast.type === 'success',
                'border-rose-200 bg-rose-50/80': toast.type === 'error',
                'border-amber-200 bg-amber-50/80': toast.type === 'warning'
            }"
        >
            <!-- Icon Success -->
            <div x-show="toast.type === 'success'" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <!-- Icon Error -->
            <div x-show="toast.type === 'error'" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <!-- Icon Warning -->
            <div x-show="toast.type === 'warning'" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>

            <!-- Message -->
            <p class="text-xs font-bold flex-1 leading-snug"
                :class="{
                    'text-emerald-950': toast.type === 'success',
                    'text-rose-950': toast.type === 'error',
                    'text-amber-950': toast.type === 'warning'
                }" x-text="toast.message"></p>

            <!-- Close Button -->
            <button @click="remove(toast.id)" class="text-[#6e6584] hover:text-[#231a3d] p-1 rounded-lg hover:bg-black/5 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </template>
</div>
