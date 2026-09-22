@extends('customer.layouts.booking-shell')

@section('title', 'Selesaikan Pembayaran')
@section('current_step', 5)
@section('back_url', route(\App\Support\CustomerBookingRoutes::name('customer.booking.program'), $tenant->slug))
@section('back_label', 'Pilih Layanan Lain')

@section('head')
@if(!empty($snapUrl) && !empty($clientKey))
<script src="{{ $snapUrl }}" data-client-key="{{ $clientKey }}"></script>
@endif
@endsection

@section('content')
<div class="mx-auto max-w-2xl" x-data="{ showCancelModal: false, copied: false }">
    @php
        $currentState = $paymentState ?? ($payment->status === 'sukses' ? 'success' : ($payment->status === 'gagal' ? 'failed' : ($payment->status === 'kadaluarsa' || $payment->isExpired() ? 'expired' : 'pending')));
        $displayBookings = $payment->bookings && $payment->bookings->isNotEmpty() ? $payment->bookings : ($payment->booking ? collect([$payment->booking]) : collect());
        $firstBooking = $displayBookings->first();
        $layanan = $firstBooking?->layanan;
        $durasiMenit = (int) ($layanan?->durasi ?? 60);
    @endphp

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
                        Batas waktu: <strong class="text-[#0F172A]">{{ $expiredTime->format('H:i') }} WIB</strong>
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
    </div>

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
</div>
@endsection

@section('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    const snapToken = @json($payment->snap_token ?? null);
    const callbackUrl = '{{ route(\App\Support\CustomerBookingRoutes::name("customer.booking.callback"), [$tenant->slug, $payment]) }}';
    const checkStatusUrl = '{{ route(\App\Support\CustomerBookingRoutes::name("customer.booking.check-status"), [$tenant->slug, $payment]) }}';
    const csrfToken = '{{ csrf_token() }}';

    function showInlineFeedback(message, type = 'error') {
        const banner = document.getElementById('inline-feedback-banner');
        const text = document.getElementById('inline-feedback-text');
        const icon = document.getElementById('inline-feedback-icon');
        if (!banner || !text) return;

        text.innerText = message;
        if (type === 'error') {
            banner.className = 'mt-4 rounded-xl p-3 text-xs border border-red-200 bg-red-50 text-red-700 flex items-center gap-2';
            icon.innerHTML = '<svg class="w-4 h-4 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        } else {
            banner.className = 'mt-4 rounded-xl p-3 text-xs border border-blue-200 bg-blue-50 text-blue-700 flex items-center gap-2';
            icon.innerHTML = '<svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        }
    }

    function hideInlineFeedback() {
        const banner = document.getElementById('inline-feedback-banner');
        if (banner) banner.classList.add('hidden');
    }

    function showFailedState() {
        const pendingCard = document.getElementById('state-pending-card');
        const failedCard = document.getElementById('state-failed-card');
        const expiredCard = document.getElementById('state-expired-card');
        const loadingOverlay = document.getElementById('loading-overlay');

        if (loadingOverlay) loadingOverlay.classList.add('hidden');
        if (pendingCard) pendingCard.classList.add('hidden');
        if (expiredCard) expiredCard.classList.add('hidden');
        if (failedCard) {
            failedCard.classList.remove('hidden');
            failedCard.classList.add('block');
        }
    }

    function showExpiredState() {
        const pendingCard = document.getElementById('state-pending-card');
        const failedCard = document.getElementById('state-failed-card');
        const expiredCard = document.getElementById('state-expired-card');
        const loadingOverlay = document.getElementById('loading-overlay');

        if (loadingOverlay) loadingOverlay.classList.add('hidden');
        if (pendingCard) pendingCard.classList.add('hidden');
        if (failedCard) failedCard.classList.add('hidden');
        if (expiredCard) {
            expiredCard.classList.remove('hidden');
            expiredCard.classList.add('block');
        }
    }

    function openSnapPayment() {
        if (!snapToken) {
            showInlineFeedback('Token pembayaran tidak tersedia atau telah kadaluarsa.', 'error');
            return;
        }

        if (typeof snap === 'undefined') {
            showInlineFeedback('Sistem pembayaran (Midtrans Snap) belum termuat. Periksa koneksi internet Anda lalu coba lagi.', 'error');
            return;
        }

        hideInlineFeedback();

        snap.pay(snapToken, {
            onSuccess: function (result) {
                sendPaymentCallback(result);
            },
            onPending: function (result) {
                sendPaymentCallback(result);
            },
            onError: function (result) {
                sendPaymentCallback(result);
            },
            onClose: function () {
                checkPaymentStatus(true);
            }
        });
    }

    let isProcessingSuccess = false;
    let pollInterval = null;
    let isChecking = false;

    async function sendPaymentCallback(result) {
        if (isProcessingSuccess) return;

        const loadingOverlay = document.getElementById('loading-overlay');
        const overlayTitle = document.getElementById('overlay-title');
        const overlayDesc = document.getElementById('overlay-desc');

        if (loadingOverlay) {
            loadingOverlay.classList.remove('hidden');
            loadingOverlay.classList.add('flex');
            if (overlayTitle) overlayTitle.innerText = 'Memverifikasi pembayaran...';
            if (overlayDesc) overlayDesc.innerText = 'Mohon tunggu sebentar.';
        }

        try {
            const response = await fetch(callbackUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ result })
            });

            const data = await response.json();

            if (data.status === 'sukses' && data.redirect) {
                showSuccessRedirect(data.redirect);
            } else if (data.status === 'pending') {
                if (loadingOverlay) {
                    loadingOverlay.classList.add('hidden');
                    loadingOverlay.classList.remove('flex');
                }
                const realtimeText = document.getElementById('realtime-status-text');
                if (realtimeText) realtimeText.innerText = 'Menunggu penyelesaian pembayaran oleh penyedia...';
            } else if (data.status === 'gagal') {
                showFailedState();
            } else {
                if (loadingOverlay) {
                    loadingOverlay.classList.add('hidden');
                    loadingOverlay.classList.remove('flex');
                }
            }
        } catch (error) {
            console.error('Callback error:', error);
            if (loadingOverlay) {
                loadingOverlay.classList.add('hidden');
                loadingOverlay.classList.remove('flex');
            }
        }
    }

    function showSuccessRedirect(redirectUrl) {
        if (isProcessingSuccess) return;
        isProcessingSuccess = true;
        stopAutoPolling();

        const loadingOverlay = document.getElementById('loading-overlay');
        const loadingSpinner = document.getElementById('loading-spinner');
        const successIcon = document.getElementById('success-icon');
        const overlayTitle = document.getElementById('overlay-title');
        const overlayDesc = document.getElementById('overlay-desc');
        const realtimeStatusText = document.getElementById('realtime-status-text');

        if (loadingOverlay) {
            loadingOverlay.classList.remove('hidden');
            loadingOverlay.classList.add('flex');
        }
        if (loadingSpinner) loadingSpinner.classList.add('hidden');
        if (successIcon) {
            successIcon.classList.remove('hidden');
            successIcon.classList.add('flex', 'animate-bounce-in');
        }
        if (overlayTitle) {
            overlayTitle.innerHTML = 'Pembayaran Berhasil Dikonfirmasi! 🎉';
            overlayTitle.className = 'text-lg sm:text-xl font-black text-emerald-600';
        }
        if (overlayDesc) overlayDesc.innerText = 'Mengalihkan Anda ke bukti reservasi...';
        if (realtimeStatusText) realtimeStatusText.innerText = '✓ Pembayaran berhasil diterima!';

        setTimeout(() => {
            window.location.href = redirectUrl;
        }, 1200);
    }

    async function checkPaymentStatus(isSilent = false) {
        if (isProcessingSuccess || isChecking) return;
        isChecking = true;

        const checkStatusBtn = document.getElementById('check-status-btn');
        if (!isSilent && checkStatusBtn) {
            checkStatusBtn.innerText = 'Memverifikasi...';
            checkStatusBtn.disabled = true;
        }

        try {
            const response = await fetch(checkStatusUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const data = await response.json();

            if (data.status === 'sukses' && data.redirect) {
                showSuccessRedirect(data.redirect);
                return;
            }

            if (data.status === 'gagal') {
                stopAutoPolling();
                showFailedState();
                return;
            }

            if (data.status === 'kadaluarsa') {
                stopAutoPolling();
                showExpiredState();
                return;
            }

            if (!isSilent && data.message) {
                showInlineFeedback(data.message, 'info');
            }
        } catch (error) {
            console.error('Status check error:', error);
            if (!isSilent) {
                showInlineFeedback('Gagal memeriksa status pembayaran. Periksa koneksi internet Anda.', 'error');
            }
        } finally {
            isChecking = false;
            if (!isSilent && checkStatusBtn) {
                checkStatusBtn.innerHTML = `
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Periksa Status Pembayaran</span>
                `;
                checkStatusBtn.disabled = false;
            }
        }
    }

    function startAutoPolling() {
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(() => {
            if (!document.hidden) {
                checkPaymentStatus(true);
            }
        }, 2500);
    }

    function stopAutoPolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const payButton = document.getElementById('pay-button');
        const checkStatusBtn = document.getElementById('check-status-btn');
        const countdownCard = document.getElementById('countdown-card');
        const countdownTitle = document.getElementById('countdown-title');
        const countdownTitleText = document.getElementById('countdown-title-text');
        const countdownDot = document.getElementById('countdown-dot');
        const countdownMin = document.getElementById('countdown-min');
        const countdownSec = document.getElementById('countdown-sec');
        const countdownColon = document.getElementById('countdown-colon');

        // Expiry countdown: synchronized with server remaining seconds
        let remainingSeconds = {{ max(0, $payment->expired_at ? (int) now()->diffInSeconds($payment->expired_at, false) : 900) }};

        function updateCountdown() {
            if (remainingSeconds <= 0) {
                if (timer) clearInterval(timer);
                stopAutoPolling();
                if (countdownCard) {
                    countdownCard.className = "mt-4 mx-auto max-w-sm rounded-2xl border border-red-200 bg-red-50 p-3 sm:p-4 text-center";
                }
                if (countdownTitle) {
                    countdownTitle.className = "text-[11px] font-bold uppercase tracking-wider text-red-700 flex items-center justify-center gap-1.5";
                }
                if (countdownTitleText) {
                    countdownTitleText.innerText = "Waktu Pembayaran Habis";
                }
                if (countdownMin) countdownMin.innerText = "00";
                if (countdownSec) countdownSec.innerText = "00";
                showExpiredState();
                return;
            }

            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;

            const minStr = String(minutes).padStart(2, '0');
            const secStr = String(seconds).padStart(2, '0');

            if (countdownMin) countdownMin.innerText = minStr;
            if (countdownSec) countdownSec.innerText = secStr;

            // S5.1: Urgency state change when <= 3 minutes (180 seconds)
            if (remainingSeconds <= 180) {
                if (countdownCard && !countdownCard.classList.contains('border-red-300')) {
                    countdownCard.className = "mt-4 mx-auto max-w-sm rounded-2xl border border-red-300 bg-red-50/90 p-3 sm:p-4 transition-all duration-300";
                    if (countdownTitle) countdownTitle.className = "text-[11px] font-bold uppercase tracking-wider text-red-700 flex items-center justify-center gap-1.5";
                    if (countdownDot) countdownDot.className = "h-2 w-2 rounded-full bg-red-500 animate-ping";
                    if (countdownMin) countdownMin.className = "inline-flex h-11 w-12 items-center justify-center rounded-xl bg-red-600 text-white font-mono text-xl sm:text-2xl font-black shadow-xs transition-colors";
                    if (countdownColon) countdownColon.className = "text-2xl font-black text-red-600 -mt-4 transition-colors";
                    if (countdownSec) countdownSec.className = "inline-flex h-11 w-12 items-center justify-center rounded-xl bg-red-600 text-white font-mono text-xl sm:text-2xl font-black shadow-xs transition-colors";
                }
            }
        }

        updateCountdown();
        const timer = setInterval(function() {
            remainingSeconds--;
            updateCountdown();
        }, 1000);

        if (payButton) {
            payButton.addEventListener('click', openSnapPayment);
        }

        if (checkStatusBtn) {
            checkStatusBtn.addEventListener('click', () => checkPaymentStatus(false));
        }

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && !isProcessingSuccess) {
                checkPaymentStatus(true);
            }
        });

        // Start real-time background monitoring only if pending
        @if($currentState === 'pending')
        startAutoPolling();
        @endif
        // NOTE: Auto-launch of Snap popup modal on page load is REMOVED per Phase 3 prescriptive specification.
    });
</script>
@endsection
