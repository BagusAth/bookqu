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
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {{-- Service Info --}}
                    <div class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Layanan Dipesan</span>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">{{ $booking->layanan?->namalayanan ?? 'Layanan' }}</h2>
                        <div class="flex items-center gap-2 pt-1">
                            <span class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-bold text-indigo-700">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 3"/>
                                </svg>
                                {{ $booking->layanan?->durasi ?? 60 }} {{ $booking->layanan?->satuan_durasi ?? 'menit' }}
                            </span>
                            @if($booking->layanan?->kapasitas)
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
                                    {{ \Carbon\Carbon::parse($booking->jam)->format('H:i') }} - {{ $endDateTime->format('H:i') }} WIB
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
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.969.54 1.761.817 2.796.817 3.182 0 5.768-2.587 5.768-5.766.001-3.181-2.585-5.766-5.768-5.766zm0 10.42c-.93 0-1.636-.26-2.527-.79l-.18-.108-1.58.414.421-1.54-.118-.188c-.604-.962-.976-1.745-.976-2.61 0-2.678 2.181-4.857 4.86-4.857 2.677 0 4.857 2.18 4.857 4.858 0 2.677-2.18 4.857-4.857 4.857z"/>
                        </svg>
                        <span>Bagikan ke WA</span>
                    </a>

                    <a
                        id="btn-invoice"
                        href="{{ route('booking.manage.invoice', ['booking_code' => $booking->booking_code, 'token' => $token]) }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50/70 px-3.5 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition-all cursor-pointer"
                    >
                        <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Unduh Invoice Resmi</span>
                    </a>
                </div>
            </div>
        </div>
