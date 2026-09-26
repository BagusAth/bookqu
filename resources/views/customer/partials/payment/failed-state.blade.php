{{-- State: FAILED (Server-rendered or dynamically revealed) --}}
<div id="state-failed-card" class="{{ $currentState === 'failed' ? 'block' : 'hidden' }} relative overflow-hidden rounded-2xl border border-red-200 bg-white p-6 sm:p-8 shadow-sm mb-6 text-center">
    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100 text-red-600 mb-4">
        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </div>
    <h1 class="text-xl sm:text-2xl font-black text-[#0F172A] tracking-tight">Pembayaran Tidak Berhasil</h1>
    <p class="mt-2 text-sm text-[#64748B] max-w-md mx-auto">
        Pembayaran belum berhasil diproses. Reservasi Anda belum dikonfirmasi.
    </p>

    <div class="mt-4 rounded-xl bg-[#F8FAFC] p-4 border border-[#E2E8F0] max-w-md mx-auto text-xs text-[#475569] space-y-1.5 text-left">
        <div class="flex justify-between">
            <span class="text-[#64748B]">Order ID:</span>
            <span class="font-mono font-bold text-[#0F172A]">{{ $payment->order_id }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-[#64748B]">Total:</span>
            <span class="font-bold text-[#4F46E5]">Rp {{ number_format($payment->jumlah, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
        @if(!empty($payment->snap_token) && $payment->status === 'pending')
            <button
                type="button"
                onclick="openSnapPayment()"
                class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl bg-[#4F46E5] px-6 py-3 text-sm font-bold text-white shadow-md hover:bg-[#4338CA] transition-colors cursor-pointer"
            >
                Coba Bayar Lagi
            </button>
        @else
            <a
                href="{{ route(\App\Support\CustomerBookingRoutes::name('customer.booking.program'), $tenant->slug) }}"
                class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl bg-[#4F46E5] px-6 py-3 text-sm font-bold text-white shadow-md hover:bg-[#4338CA] transition-colors"
            >
                Buat Reservasi Baru
            </a>
        @endif
        <a
            href="{{ route(\App\Support\CustomerBookingRoutes::name('customer.booking.program'), $tenant->slug) }}"
            class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl border border-[#CBD5E1] bg-white px-6 py-3 text-sm font-semibold text-[#334155] hover:bg-[#F8FAFC] transition-colors"
        >
            Kembali ke Pemesanan
        </a>
    </div>
</div>
