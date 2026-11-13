        {{-- ── DIGITAL RESERVATION PASS (HERO TICKET CARD) ── --}}
        <div class="overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-md relative">
            {{-- Top Header Ribbon --}}
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 px-6 py-5 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-indigo-300 text-xs font-semibold uppercase tracking-wider">
                        <span>Tiket Resmi Reservasi</span>
                        <span>•</span>
                        <span>{{ $booking->tenant->namabisnis }}</span>
                    </div>
                    <div class="flex items-center gap-3 mt-1.5">
                        <span class="font-mono text-xl sm:text-2xl font-black tracking-wider text-white select-all">{{ $booking->booking_code }}</span>
                        <button
                            type="button"
                            @click="copyCode('{{ $booking->booking_code }}')"
                            class="inline-flex items-center gap-1 rounded-lg bg-white/10 hover:bg-white/20 px-2.5 py-1 text-xs font-semibold text-white transition-all cursor-pointer"
                            title="Salin Kode Booking"
                        >
                            <svg x-show="!copied" class="h-3.5 w-3.5 text-indigo-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                            </svg>
                            <svg x-show="copied" x-cloak class="h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span x-text="copied ? 'Disalin!' : 'Salin'">Salin</span>
                        </button>
                    </div>
                </div>

                {{-- Status Badge --}}
                <div class="flex flex-col sm:items-end">
                    @php
                        $statusBadge = match($booking->status) {
                            'paid'      => ['bg' => 'bg-emerald-500/15 border-emerald-400/40 text-emerald-300', 'pulse' => 'bg-emerald-400', 'label' => 'Terkonfirmasi'],
                            'pending'   => ['bg' => 'bg-amber-500/15 border-amber-400/40 text-amber-300', 'pulse' => 'bg-amber-400', 'label' => 'Menunggu Bayar'],
                            'cancelled' => ['bg' => 'bg-rose-500/15 border-rose-400/40 text-rose-300', 'pulse' => 'bg-rose-400', 'label' => 'Dibatalkan'],
                            'completed' => ['bg' => 'bg-indigo-500/15 border-indigo-400/40 text-indigo-300', 'pulse' => 'bg-indigo-400', 'label' => 'Selesai'],
                            default     => ['bg' => 'bg-slate-500/15 border-slate-400/40 text-slate-300', 'pulse' => 'bg-slate-400', 'label' => $booking->status],
                        };
                    @endphp
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1 text-xs font-bold tracking-wide {{ $statusBadge['bg'] }}">
                        <span class="h-2 w-2 rounded-full {{ $statusBadge['pulse'] }} {{ in_array($booking->status, ['paid', 'pending']) ? 'animate-pulse' : '' }}"></span>
                        {{ $statusBadge['label'] }}
                    </span>
                    <span class="text-[11px] text-slate-400 mt-1">Dipesan {{ $booking->created_at->translatedFormat('d M Y, H:i') }} WIB</span>
                </div>
            </div>

            {{-- Ticket Body --}}
            <div class="p-6 sm:p-8">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {{-- Service Info --}}
                    <div class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Layanan Dipesan</span>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">{{ $booking->layanan?->namalayanan ?? 'Layanan' }}</h2>
                        <div class="flex items-center gap-2 pt-1">
                            <span class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-bold text-indigo-700">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 3"/>
                                </svg>
                                {{ $timeRange->durationMinutes() !== null ? $timeRange->durationMinutes().' menit' : '-' }}
                            </span>
                            @if(($booking->layanan?->kapasitas ?? 1) > 1)
                                <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">
                                    Maks {{ $booking->layanan->kapasitas }} orang
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Schedule Date & Time --}}
                    <div class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Jadwal Sesi</span>
                        <div class="flex items-start gap-2.5">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 mt-0.5">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="3"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-base sm:text-lg font-bold text-slate-900 leading-snug">
                                    {{ \Carbon\Carbon::parse($booking->tanggalbooking)->translatedFormat('l, d F Y') }}
                                </p>
                                <p class="text-sm font-semibold text-indigo-600">
                                    {{ $timeRange->label(' – ') }} WIB
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Summary --}}
                    <div class="space-y-1 md:col-span-2 lg:col-span-1">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Pembayaran</span>
                        <div class="flex items-baseline gap-2">
                            <p class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $booking->priceLabel }}</p>
                        </div>
                        <p class="text-xs font-medium text-emerald-600 flex items-center gap-1 pt-0.5">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            Pembayaran Berhasil &amp; Terverifikasi
                        </p>
                    </div>
                </div>

                {{-- Previous Reschedule Notice if any --}}
                @if($booking->rescheduled_from_date)
                    <div class="mt-6 rounded-2xl border border-[#C7D2FE] bg-[#EEF2FF]/70 p-4 text-xs sm:text-sm text-[#312E81] flex items-start gap-3">
                        <svg class="w-5 h-5 flex-shrink-0 text-[#4F46E5] mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <strong>Jadwal sesi telah diubah.</strong>
                            Jadwal sebelumnya adalah <strong>{{ \Carbon\Carbon::parse($booking->rescheduled_from_date)->translatedFormat('l, d M Y') }}</strong> pukul <strong>{{ \Carbon\Carbon::parse($booking->rescheduled_from_time)->format('H:i') }} WIB</strong>.
                        </div>
                    </div>
                @endif

                {{-- Quick Utility Actions Inside Ticket --}}
                <div class="mt-6 pt-6 border-t border-slate-100 flex flex-wrap items-center gap-2 sm:gap-3">
                    <a
                        href="{{ $gCalUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 hover:border-slate-300 transition-all cursor-pointer"
                    >
                        <svg class="h-4 w-4 text-[#4285F4]" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 002 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                        </svg>
                        <span>Google Calendar</span>
                    </a>

                    <a
                        href="{{ $waShareUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50/80 px-3.5 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition-all cursor-pointer"
                    >
                        <svg class="h-4 w-4 text-emerald-600 fill-current" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        <span>Bagikan ke WA</span>
                    </a>

                    {{-- The invoice exists only for a paid or completed booking (BQ-065 T5-09) --}}
                    @if(in_array($booking->status, ['paid', 'completed'], true))
                    <a
                        id="btn-invoice"
                        href="{{ route('booking.manage.invoice', ['booking_code' => $booking->booking_code]) }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50/70 px-3.5 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition-all cursor-pointer"
                    >
                        <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Unduh Invoice Resmi</span>
                    </a>
                    @endif
                </div>
            </div>
        </div>
