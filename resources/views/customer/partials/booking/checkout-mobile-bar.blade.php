{{-- Mobile Bottom Floating Action Bar --}}
<div class="booking-mobile-bar lg:hidden">
    <div class="max-w-4xl mx-auto flex items-center justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-[11px] text-[#64748B] truncate">{{ $service->namalayanan }}</p>
            <div class="flex items-baseline gap-1.5">
                <p id="mobile-total-display" class="text-base font-black text-[#4F46E5]">
                    Rp {{ number_format($hargaAkhir, 0, ',', '.') }}
                </p>
            </div>
        </div>
        <button
            type="submit"
            id="mobile-submit-checkout-btn"
            class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl bg-[#4F46E5] hover:bg-[#4338CA] px-5 py-2.5 text-sm font-bold text-white shadow-md transition-all active:scale-95 shrink-0"
        >
            <span>Lanjut ke Pembayaran</span>
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
        </button>
    </div>
</div>
