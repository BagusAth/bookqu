{{-- Modal Konfirmasi Batalkan & Ganti Jadwal --}}
<div
    x-show="showCancelModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
>
    <div
        x-show="showCancelModal"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="showCancelModal = false"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
    ></div>

    <div
        x-show="showCancelModal"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white p-6 shadow-2xl transition-all z-10"
    >
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-red-100 text-red-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-bold text-[#0F172A]">
                    Batalkan &amp; Ganti Jadwal?
                </h3>
                <p class="mt-1 text-xs text-[#64748B] leading-relaxed">
                    Tagihan saat ini akan dibatalkan dan slot jadwal akan kembali tersedia untuk dipesan pelanggan lain. Anda akan dialihkan ke daftar layanan untuk memilih jadwal baru.
                </p>

                <div class="mt-3 rounded-xl bg-[#F8FAFC] p-3 border border-[#E2E8F0] text-xs text-[#475569] space-y-1">
                    <div class="flex justify-between">
                        <span class="text-[#64748B]">Order ID:</span>
                        <span class="font-mono font-medium text-[#0F172A]">{{ $payment->order_id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#64748B]">Total:</span>
                        <span class="font-bold text-[#4F46E5]">Rp {{ number_format($payment->jumlah, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
            <button
                type="button"
                @click="showCancelModal = false"
                class="inline-flex justify-center rounded-xl border border-[#CBD5E1] bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#334155] hover:bg-[#F8FAFC] transition-colors cursor-pointer"
            >
                Kembali ke Pembayaran
            </button>
            <form method="POST" action="{{ route(\App\Support\CustomerBookingRoutes::name('customer.booking.cancel'), [$tenant->slug, $payment]) }}" class="inline">
                @csrf
                <button
                    type="submit"
                    class="w-full inline-flex justify-center rounded-xl bg-red-600 px-4 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition-colors cursor-pointer"
                >
                    Ya, Batalkan Pesanan
                </button>
            </form>
        </div>
    </div>
</div>
