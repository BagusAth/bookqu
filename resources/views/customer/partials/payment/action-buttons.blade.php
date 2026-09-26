{{-- Bottom Action Buttons --}}
<div class="flex flex-col sm:flex-row items-center justify-center gap-3">
    <button
        id="check-status-btn"
        type="button"
        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-[#CBD5E1] bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#334155] shadow-2xs hover:bg-[#F8FAFC] hover:border-[#94A3B8] transition-all cursor-pointer"
    >
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span>Periksa Status Pembayaran</span>
    </button>

    <button
        type="button"
        @click="showCancelModal = true"
        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-xl border border-transparent px-4 py-2.5 text-xs sm:text-sm font-medium text-[#64748B] hover:text-red-600 hover:bg-red-50/70 transition-all cursor-pointer"
    >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <span>Batalkan &amp; Ganti Jadwal</span>
    </button>
</div>
