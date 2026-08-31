<div
    x-data="{
        buka: false,
        title: '',
        message: '',
        formId: null,
        actionUrl: null,
        method: 'POST'
    }"
    @open-confirm.window="
        title = $event.detail.title || 'Konfirmasi';
        message = $event.detail.message || 'Apakah Anda yakin?';
        formId = $event.detail.formId || null;
        actionUrl = $event.detail.actionUrl || null;
        method = $event.detail.method || 'POST';
        buka = true;
    "
    x-cloak
>
    <!-- Overlay -->
    <div
        x-show="buka"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[100] bg-black/40 backdrop-blur-sm"
        @click="buka = false"
    ></div>

    <!-- Modal -->
    <div
        x-show="buka"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="fixed inset-0 z-[110] flex items-center justify-center p-4"
    >
        <div class="w-full max-w-sm rounded-2xl border border-bq-border bg-bq-surface p-6 shadow-2xl text-center" @click.stop>
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 mb-4">
                <svg class="h-6 w-6 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            
            <h3 class="text-lg font-bold text-bq-text mb-2" x-text="title"></h3>
            <p class="text-sm text-bq-text-muted mb-6" x-text="message"></p>

            <div class="flex items-center justify-center gap-3">
                <button type="button" @click="buka = false" class="rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm font-medium text-bq-text transition-all hover:bg-bq-background w-full">
                    Batal
                </button>
                
                <!-- If using formId -->
                <button 
                    x-show="formId"
                    type="button" 
                    @click="document.getElementById(formId).submit(); buka = false" 
                    class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-rose-600/25 transition-all hover:bg-rose-700 hover:shadow-lg w-full"
                >
                    Konfirmasi
                </button>
                
                <!-- If using actionUrl -->
                <form x-show="actionUrl && !formId" :action="actionUrl" method="POST" class="w-full">
                    @csrf
                    <input type="hidden" name="_method" :value="method">
                    <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-rose-600/25 transition-all hover:bg-rose-700 hover:shadow-lg w-full">
                        Konfirmasi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
