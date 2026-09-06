<div
    x-data="{
        buka: false,
        title: '',
        message: '',
        formId: null,
        actionUrl: null,
        method: 'POST',
        confirmText: 'Ya, Hapus',
        cancelText: 'Batal'
    }"
    @open-confirm.window="
        title = $event.detail.title || 'Konfirmasi Tindakan';
        message = $event.detail.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
        formId = $event.detail.formId || null;
        actionUrl = $event.detail.actionUrl || null;
        method = $event.detail.method || 'POST';
        confirmText = $event.detail.confirmText || 'Ya, Hapus';
        cancelText = $event.detail.cancelText || 'Batal';
        buka = true;
    "
    @keydown.escape.window="buka = false"
    x-cloak
>
    <!-- Backdrop Overlay -->
    <div
        x-show="buka"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[100] bg-[#231a3d]/50 backdrop-blur-xs"
        @click="buka = false"
    ></div>

    <!-- Modal Dialog -->
    <div
        x-show="buka"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 pointer-events-none"
    >
        <div
            class="pointer-events-auto w-full max-w-sm sm:max-w-md rounded-3xl border border-[#e7e2f7] bg-white p-6 sm:p-7 shadow-[0_25px_60px_rgba(35,26,61,0.2)] text-center relative overflow-hidden"
            @click.stop
        >
            {{-- Top Accent Bar --}}
            <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-rose-500 via-rose-600 to-amber-500"></div>

            {{-- Warning Icon Badge --}}
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 border border-rose-100 mb-4 text-rose-600 shadow-2xs">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            
            <h3 class="text-base sm:text-lg font-extrabold text-[#231a3d] tracking-tight mb-2" x-text="title"></h3>
            <p class="text-xs sm:text-sm text-[#6e6584] leading-relaxed mb-6 font-medium max-w-xs sm:max-w-sm mx-auto" x-text="message"></p>

            <div class="flex items-center justify-center gap-2.5">
                <button
                    type="button"
                    @click="buka = false"
                    class="craft-btn w-full rounded-xl border border-[#e7e2f7] bg-[#f7f7fa] px-4 py-2.5 text-xs font-bold text-[#6e6584] hover:bg-[#e7e2f7] hover:text-[#231a3d] transition active:scale-[0.98] cursor-pointer"
                    x-text="cancelText"
                >
                </button>
                
                <!-- Form Submission via formId -->
                <button 
                    x-show="formId"
                    type="button" 
                    @click="if (formId && document.getElementById(formId)) { document.getElementById(formId).submit(); buka = false; }" 
                    class="craft-btn w-full rounded-xl bg-gradient-to-r from-rose-600 to-rose-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:from-rose-700 hover:to-rose-800 transition active:scale-[0.98] cursor-pointer"
                    x-text="confirmText"
                >
                </button>
                
                <!-- Dynamic form submission via actionUrl -->
                <form x-show="actionUrl && !formId" :action="actionUrl" method="POST" class="w-full">
                    @csrf
                    <input type="hidden" name="_method" :value="method">
                    <button
                        type="submit"
                        class="craft-btn w-full rounded-xl bg-gradient-to-r from-rose-600 to-rose-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:from-rose-700 hover:to-rose-800 transition active:scale-[0.98] cursor-pointer"
                        x-text="confirmText"
                    >
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
