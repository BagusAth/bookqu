{{-- State: PENDING (Primary Payment Card adhering strictly to Section 9 Information Hierarchy) --}}
<div id="state-pending-card" class="{{ $currentState === 'pending' ? 'block' : 'hidden' }}">
    {{-- Header Content --}}
    <div class="text-center mb-6">
        <h1 class="text-xl sm:text-2xl font-black text-[#0F172A] tracking-tight">Selesaikan Pembayaran</h1>
        <p class="mt-1 text-sm text-[#64748B]">Selesaikan pembayaran sebelum batas waktu agar reservasi Anda tetap aktif.</p>
    </div>

    <div class="relative overflow-hidden rounded-2xl border border-[#E2E8F0] bg-white p-5 sm:p-8 shadow-sm mb-6">
        {{-- 1. Total Tagihan & Prominent Countdown (S5.1) --}}
        <div class="text-center pb-6 border-b border-[#F1F5F9]">
            <p class="text-xs font-bold uppercase tracking-wider text-[#64748B] mb-1">Total Tagihan</p>
            <p class="text-3xl sm:text-4xl font-black text-[#4F46E5]">Rp {{ number_format($payment->jumlah, 0, ',', '.') }}</p>
            
            @php
                $expiredTime = $payment->expired_at ? \Carbon\Carbon::parse($payment->expired_at)->timezone('Asia/Jakarta') : now('Asia/Jakarta')->addMinutes(15);
            @endphp
            <div id="countdown-card" class="mt-4 mx-auto max-w-sm rounded-2xl border border-amber-200 bg-amber-50/70 p-3 sm:p-4 transition-all duration-300">
                <p id="countdown-title" class="text-[11px] font-bold uppercase tracking-wider text-amber-800 flex items-center justify-center gap-1.5">
                    <span id="countdown-dot" class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span id="countdown-title-text">Selesaikan Pembayaran Dalam:</span>
                </p>
                <div class="mt-2.5 flex items-center justify-center gap-2">
                    <div class="flex flex-col items-center">
                        <span id="countdown-min" class="inline-flex h-11 w-12 items-center justify-center rounded-xl bg-amber-600 text-white font-mono text-xl sm:text-2xl font-black shadow-xs transition-colors">00</span>
                        <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider mt-1">Menit</span>
                    </div>
                    <span id="countdown-colon" class="text-2xl font-black text-amber-600 -mt-4 transition-colors">:</span>
                    <div class="flex flex-col items-center">
                        <span id="countdown-sec" class="inline-flex h-11 w-12 items-center justify-center rounded-xl bg-amber-600 text-white font-mono text-xl sm:text-2xl font-black shadow-xs transition-colors">00</span>
                        <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider mt-1">Detik</span>
                    </div>
                </div>
                <p class="text-[11px] text-[#64748B] mt-2">
                    Bayar sebelum: <strong class="text-[#0F172A]">{{ $expiredTime->format('H:i') }} WIB</strong>
                </p>
            </div>
        </div>

        {{-- 2. Primary CTA & Step Guide (S5.2) --}}
        <div class="py-6 border-b border-[#F1F5F9]">
            {{-- Quick Step Guide (S5.2) --}}
            <div class="rounded-xl bg-[#F8FAFC] border border-[#E2E8F0] p-3.5 mb-4 text-xs text-[#475569]">
                <p class="font-bold text-[#0F172A] text-xs mb-2">Panduan Pembayaran Cepat:</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <div class="flex items-start gap-2 bg-white p-2.5 rounded-lg border border-[#F1F5F9]">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#4F46E5] text-white text-[10px] font-bold mt-0.5">1</span>
                        <span>Klik <strong>"Bayar Sekarang"</strong></span>
                    </div>
                    <div class="flex items-start gap-2 bg-white p-2.5 rounded-lg border border-[#F1F5F9]">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#4F46E5] text-white text-[10px] font-bold mt-0.5">2</span>
                        <span>Pilih metode (QRIS, VA, E-Wallet)</span>
                    </div>
                    <div class="flex items-start gap-2 bg-white p-2.5 rounded-lg border border-[#F1F5F9]">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#4F46E5] text-white text-[10px] font-bold mt-0.5">3</span>
                        <span>Selesai, e-ticket otomatis terbit</span>
                    </div>
                </div>
            </div>

            <button
                id="pay-button"
                type="button"
                class="w-full flex items-center justify-center gap-2.5 rounded-xl bg-[#4F46E5] px-6 py-4 text-base font-bold text-white shadow-lg shadow-[#4F46E5]/25 transition-all hover:bg-[#4338CA] hover:shadow-xl hover:shadow-[#4F46E5]/30 active:scale-[0.99] cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#4F46E5]"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                <span>Bayar Sekarang</span>
            </button>
            <p class="mt-2.5 text-center text-xs text-[#94A3B8]">
                Mendukung QRIS, GoPay, ShopeePay, Virtual Account BCA/Mandiri/BNI/BRI &amp; E-Wallet
            </p>

            {{-- Inline Feedback Notice (replaces window.alert) --}}
            <div id="inline-feedback-banner" class="hidden mt-4 rounded-xl p-3 text-xs border" role="alert" aria-live="polite">
                <div class="flex items-center gap-2">
                    <span id="inline-feedback-icon" class="shrink-0"></span>
                    <span id="inline-feedback-text" class="flex-1 font-medium"></span>
                    <button type="button" onclick="hideInlineFeedback()" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- 3. Detail Reservasi (Layanan, Tanggal, Sesi Range) --}}
        <div class="py-6 border-b border-[#F1F5F9]">
            <h2 class="text-xs font-bold uppercase tracking-wider text-[#64748B] mb-3">Detail Reservasi</h2>
            @if ($displayBookings->isNotEmpty())
                <div class="space-y-2.5">
                    @foreach ($displayBookings as $bItem)
                        @php
                            $slotStart = \Carbon\Carbon::parse(($bItem->tanggalbooking ? \Carbon\Carbon::parse($bItem->tanggalbooking)->format('Y-m-d') : now()->format('Y-m-d')) . ' ' . $bItem->jam);
                            $slotEnd = (clone $slotStart)->addMinutes($durasiMenit);
                        @endphp
                        <div class="rounded-xl bg-[#F8FAFC] p-4 border border-[#E2E8F0] grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs sm:text-sm">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#EEF2FF] text-[#4F46E5]">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <span class="text-[#64748B] text-xs block">Layanan</span>
                                    <strong class="text-[#0F172A] font-semibold truncate block">{{ $bItem->layanan->namalayanan ?? 'Layanan' }}</strong>
                                </div>
                            </div>
                            <div class="flex items-center sm:justify-end gap-2.5">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#EEF2FF] text-[#4F46E5]">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="sm:text-right">
                                    <span class="text-[#64748B] text-xs block">Tanggal &amp; Waktu</span>
                                    <strong class="text-[#0F172A] font-semibold">
                                        {{ \Carbon\Carbon::parse($bItem->tanggalbooking)->translatedFormat('d M Y') }},
                                        <span class="font-mono text-[#4F46E5]">{{ $slotStart->format('H:i') }} – {{ $slotEnd->format('H:i') }}</span> WIB
                                    </strong>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- 4. Secondary Metadata: Order ID & Status --}}
        <div class="pt-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-[#64748B] block mb-1">Order ID</span>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-sm font-bold text-[#0F172A]">{{ $payment->order_id }}</span>
                    <button
                        type="button"
                        @click="navigator.clipboard.writeText('{{ $payment->order_id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="inline-flex items-center gap-1 rounded-lg bg-slate-100 hover:bg-slate-200 px-2 py-0.5 text-[11px] font-semibold text-[#475569] transition-colors cursor-pointer"
                        title="Salin Order ID"
                    >
                        <svg x-show="!copied" class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                        </svg>
                        <svg x-show="copied" x-cloak class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                        <span x-text="copied ? 'Tersalin' : 'Salin'">Salin</span>
                    </button>
                </div>
            </div>
            <div class="sm:text-right">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#64748B] block mb-1">Status</span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-700 border border-amber-200/70">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                    Menunggu Pembayaran
                </span>
            </div>
        </div>

        {{-- Realtime Auto-Detect Status Monitoring (Section 10 Standard Copy) --}}
        <div class="mt-6 pt-4 border-t border-[#F1F5F9] flex items-center justify-center gap-2 text-xs text-[#64748B]" aria-live="polite">
            <span class="relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
            </span>
            <span class="sr-only">Sistem memantau pembayaran Anda secara otomatis</span>
            <span id="realtime-status-text">Kami sedang memantau status pembayaran Anda.</span>
        </div>

        {{-- Loading & Success Overlay --}}
        <div id="loading-overlay" class="absolute inset-0 z-20 hidden flex-col items-center justify-center bg-white/95 backdrop-blur-sm p-6 text-center transition-all" aria-live="polite">
            <div id="loading-spinner" class="loader mb-4"></div>
            <div id="success-icon" class="hidden mb-4 h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 animate-bounce">
                <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 id="overlay-title" class="text-base sm:text-lg font-bold text-[#0F172A]">
                Memverifikasi pembayaran...
            </h3>
            <p id="overlay-desc" class="mt-1 text-xs text-[#64748B]">
                Mohon jangan tutup halaman ini.
            </p>
        </div>
    </div>

    {{-- Bottom Action Buttons --}}
    @include('customer.partials.payment.action-buttons')
</div>
