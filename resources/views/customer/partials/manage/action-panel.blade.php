                {{-- Action Management Card --}}
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-bold text-slate-900 pb-4 border-b border-slate-100 flex items-center gap-2">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        Tindakan Reservasi
                    </h3>

                    <div class="mt-5 space-y-4">
                        @if(!empty($isMultiSlot))
                            <div class="rounded-2xl border border-amber-200 bg-amber-50/90 p-4 text-xs text-amber-800 space-y-1">
                                <p class="font-bold flex items-center gap-1.5 text-amber-900">
                                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Reservasi Multi-Slot
                                </p>
                                <p class="leading-relaxed">
                                    Booking ini merupakan bagian dari pemesanan multi-slot beruntun. Pembatalan atau pengubahan jadwal tidak dapat dilakukan per slot secara individual demi menjaga integritas jadwal. Silakan hubungi pengelola bisnis jika Anda memerlukan penyesuaian.
                                </p>
                            </div>
                        @endif

                        @if($booking->status === 'paid')

                            {{-- Reschedule Action Section --}}
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Ubah Jadwal (Reschedule)
                                    </span>
                                    @if($canReschedule)
                                        <span class="rounded-md bg-indigo-100 px-2 py-0.5 text-[10px] font-bold text-indigo-700">Tersedia</span>
                                    @else
                                        <span class="rounded-md bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600">Tidak Tersedia</span>
                                    @endif
                                </div>

                                @if($canReschedule)
                                    <p class="text-xs text-slate-500 leading-relaxed">
                                        Perlu menyesuaikan waktu kunjungan? Anda dapat memilih tanggal dan jam baru tanpa biaya tambahan.
                                    </p>
                                    @if($rescheduleDeadline)
                                        <p class="text-[11px] font-medium text-indigo-700 bg-indigo-50/90 rounded-lg px-2.5 py-1.5 border border-indigo-100">
                                            Batas waktu: {{ $rescheduleDeadline->translatedFormat('d M Y, H:i') }} WIB
                                        </p>
                                    @endif
                                    <a
                                        id="btn-reschedule"
                                        href="{{ route('booking.manage.reschedule.show', ['booking_code' => $booking->booking_code, 'token' => $booking->reschedule_token ?: $token]) }}"
                                        class="w-full flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-xs sm:text-sm font-bold text-white shadow-md shadow-indigo-600/20 hover:bg-indigo-700 active:scale-[0.98] transition-all cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span>Pilih Jadwal Baru</span>
                                    </a>
                                @else
                                    <p class="text-xs text-slate-500 leading-relaxed">
                                        @if(!empty($isMultiSlot))
                                            Booking multi-slot tidak dapat dijadwalkan ulang per slot secara individual.
                                        @else
                                            Batas pengubahan jadwal mandiri telah berlalu (minimal {{ $booking->tenant->reschedule_before_hours ?? 24 }} jam sebelum sesi).
                                        @endif
                                    </p>
                                    @if($merchantWaUrl)
                                        <a
                                            href="{{ $merchantWaUrl }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                                        >
                                            <span>Ajukan ke Merchant via WhatsApp</span>
                                        </a>
                                    @endif
                                @endif
                            </div>

                            {{-- Cancellation Action Section --}}
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Batalkan Booking
                                    </span>
                                    @if($canCancel)
                                        <span class="rounded-md bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-700">Tersedia</span>
                                    @else
                                        <span class="rounded-md bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600">Tidak Tersedia</span>
                                    @endif
                                </div>

                                @if($canCancel)
                                    <p class="text-xs text-slate-500 leading-relaxed">
                                        Pembatalan akan melepas slot jadwal dan dana Anda akan diproses untuk pengembalian (*refund*).
                                    </p>
                                    @if($cancelDeadline)
                                        <p class="text-[11px] font-medium text-rose-700 bg-rose-50/90 rounded-lg px-2.5 py-1.5 border border-rose-100">
                                            Batas pembatalan: {{ $cancelDeadline->translatedFormat('d M Y, H:i') }} WIB
                                        </p>
                                    @endif
                                    <button
                                        id="btn-cancel"
                                        type="button"
                                        @click="showCancelModal = true"
                                        class="w-full flex items-center justify-center gap-2 rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-rose-600 hover:bg-rose-50 hover:border-rose-300 active:scale-[0.98] transition-all cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        <span>Batalkan Booking Ini</span>
                                    </button>
                                @else
                                    <p class="text-xs text-slate-500 leading-relaxed">
                                        @if(!empty($isMultiSlot))
                                            Booking multi-slot tidak dapat dibatalkan per slot secara individual.
                                        @else
                                            Batas pembatalan mandiri telah berakhir (minimal {{ $booking->tenant->cancel_before_hours ?? 24 }} jam sebelum sesi).
                                        @endif
                                    </p>
                                @endif
                            </div>

                        @elseif($booking->status === 'cancelled')
                            {{-- Cancelled State Card --}}
                            <div class="rounded-2xl border border-rose-200 bg-rose-50/80 p-5 space-y-3">
                                <div class="flex items-center gap-2.5 text-rose-700 font-bold text-sm">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </div>
                                    <span>Booking Telah Dibatalkan</span>
                                </div>
                                <p class="text-xs text-rose-800 leading-relaxed">
                                    Reservasi ini telah dibatalkan secara resmi.
                                    @if($booking->refund)
                                        Pengembalian dana sebesar <strong>Rp {{ number_format($booking->refund->jumlah, 0, ',', '.') }}</strong> tercatat dengan status <strong>{{ ucfirst($booking->refund->status) }}</strong>.
                                    @endif
                                </p>
                                <a
                                    href="{{ $booking->tenant->slug ? url('/' . $booking->tenant->slug) : '/' }}"
                                    class="w-full flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white hover:bg-slate-800 transition-colors"
                                >
                                    <span>Pesan Sesi Baru di {{ $booking->tenant->namabisnis }}</span>
                                </a>
                            </div>

                        @elseif($booking->status === 'completed')
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-center space-y-2">
                                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 font-bold">
                                    ✓
                                </div>
                                <h4 class="text-sm font-bold text-emerald-900">Sesi Telah Selesai</h4>
                                <p class="text-xs text-emerald-700">Terima kasih telah menggunakan layanan dari {{ $booking->tenant->namabisnis }}.</p>
                            </div>
                        @endif
                    </div>
                </div>
