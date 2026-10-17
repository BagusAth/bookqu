{{-- State: EXPIRED (Server-rendered or dynamically revealed) --}}
<div id="state-expired-card" class="{{ $currentState === 'expired' ? 'block' : 'hidden' }} relative overflow-hidden rounded-2xl border border-amber-200 bg-white p-6 sm:p-8 shadow-sm mb-6 text-center">
    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600 mb-4">
        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>
    <h1 class="text-xl sm:text-2xl font-black text-[#0F172A] tracking-tight">Waktu Pembayaran Habis</h1>
    <p class="mt-2 text-sm text-[#64748B] max-w-md mx-auto">
        Batas waktu pembayaran telah berakhir sehingga reservasi ini tidak dapat dilanjutkan.
    </p>

    <div class="mt-4 rounded-xl bg-[#F8FAFC] p-4 border border-[#E2E8F0] max-w-md mx-auto text-xs text-[#475569] space-y-1.5 text-left">
        <div class="flex justify-between">
            <span class="text-[#64748B]">Order ID:</span>
            <span class="font-mono font-bold text-[#0F172A]">{{ $payment->order_id }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-[#64748B]">Status:</span>
            <span class="font-bold text-red-600">Kadaluarsa</span>
        </div>
    </div>

    <div class="mt-6">
        <a
            href="{{ route(\App\Support\CustomerBookingRoutes::name('customer.booking.program'), $tenant->slug) }}"
            class="inline-flex justify-center items-center rounded-xl bg-[#4F46E5] px-6 py-3.5 text-sm font-bold text-white shadow-md hover:bg-[#4338CA] transition-colors"
        >
            Buat Reservasi Baru
        </a>
    </div>
</div>
