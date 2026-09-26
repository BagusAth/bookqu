    {{-- ── MODERN CANCEL CONFIRMATION MODAL ── --}}
    <div
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        x-show="showCancelModal"
        x-cloak
        role="dialog"
        aria-modal="true"
    >
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
            x-show="showCancelModal"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="showCancelModal = false"
        ></div>

        {{-- Modal Card --}}
        <div
            class="relative w-full max-w-md overflow-hidden rounded-3xl bg-white p-6 sm:p-7 shadow-2xl transition-all z-10"
            x-show="showCancelModal"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-base sm:text-lg font-bold text-slate-900">
                        Batalkan Booking Ini?
                    </h3>
                    <p class="mt-1 text-xs text-slate-500 leading-relaxed">
                        Tindakan ini tidak dapat diulang kembali. Sesi jadwal Anda akan dibatalkan dan slot waktu akan dibuka kembali untuk publik.
                    </p>
                </div>
            </div>

            {{-- Summary Card in Modal --}}
            <div class="mt-5 rounded-2xl bg-slate-50 p-4 border border-slate-100 text-xs space-y-2">
                <div class="flex justify-between">
                    <span class="text-slate-500">Kode Booking</span>
                    <span class="font-mono font-bold text-slate-900">{{ $booking->booking_code }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Layanan</span>
                    <span class="font-semibold text-slate-900">{{ $booking->layanan->namalayanan ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Jadwal Sesi</span>
                    <span class="font-semibold text-slate-900">
                        {{ \Carbon\Carbon::parse($booking->tanggalbooking)->translatedFormat('d M Y') }}, {{ \Carbon\Carbon::parse($booking->jam)->format('H:i') }} WIB
                    </span>
                </div>
                <div class="flex justify-between pt-2 border-t border-slate-200">
                    <span class="text-slate-600 font-medium">Estimasi Refund</span>
                    <span class="font-bold text-emerald-600 text-sm">{{ $booking->priceLabel }}</span>
                </div>
            </div>

            <p class="mt-3 text-[11px] text-slate-400 leading-relaxed text-center">
                Dana refund akan dikembalikan sesuai ketentuan sistem pembayaran dan kebijakan merchant {{ $booking->tenant->namabisnis }}.
            </p>

            {{-- Modal Buttons --}}
            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
                <button
                    type="button"
                    class="inline-flex justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 hover:bg-slate-50 active:scale-[0.98] transition-all cursor-pointer"
                    @click="showCancelModal = false"
                    :disabled="isCancelling"
                >
                    Kembali / Jangan Batalkan
                </button>
                <form
                    method="POST"
                    action="{{ route('booking.manage.cancel', ['booking_code' => $booking->booking_code, 'token' => $booking->cancellation_token ?: $token]) }}"
                    class="inline"
                    @submit="isCancelling = true"
                >
                    @csrf
                    <button
                        type="submit"
                        class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-md shadow-rose-600/20 hover:bg-rose-700 active:scale-[0.98] transition-all cursor-pointer"
                        :disabled="isCancelling"
                    >
                        <span x-show="!isCancelling">Ya, Batalkan Booking</span>
                        <span x-show="isCancelling" class="inline-flex items-center gap-2" x-cloak>
                            <span class="spinner"></span> Memproses...
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>
