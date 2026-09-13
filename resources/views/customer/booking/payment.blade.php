@extends('customer.layouts.booking-shell')

@section('title', 'Pembayaran')
@section('current_step', 5)
@section('back_url', route('customer.booking.program', $tenant->slug))
@section('back_label', 'Pilih Layanan Lain')

@section('head')
<script src="{{ $snapUrl }}" data-client-key="{{ $clientKey }}"></script>
@endsection

@section('content')
<div class="mx-auto max-w-2xl" x-data="{ showCancelModal: false, copied: false }">
    {{-- Header Content --}}
    <div class="text-center mb-6">
        <h1 class="text-xl sm:text-2xl font-black text-[#0F172A] tracking-tight">Selesaikan Pembayaran</h1>
        <p class="mt-1 text-sm text-[#64748B]">Selesaikan transaksi Anda sebelum batas waktu pembayaran berakhir.</p>
    </div>

    {{-- Main Payment Card --}}
    <div class="relative overflow-hidden rounded-2xl border border-[#E2E8F0] bg-white p-6 sm:p-8 shadow-sm mb-6">
        {{-- Order ID & Status & Total --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-[#F1F5F9]">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Kode Pesanan</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700 border border-amber-200/70">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        Menunggu Pembayaran
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-base sm:text-lg font-bold text-[#0F172A]">{{ $payment->order_id }}</span>
                    <button
                        type="button"
                        @click="navigator.clipboard.writeText('{{ $payment->order_id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="inline-flex items-center gap-1 rounded-lg bg-slate-100 hover:bg-slate-200 px-2.5 py-1 text-[11px] font-semibold text-[#475569] transition-colors cursor-pointer"
                        title="Salin Order ID"
                    >
                        <svg x-show="!copied" class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                        </svg>
                        <svg x-show="copied" x-cloak class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                        <span x-text="copied ? 'Tersalin' : 'Salin'">Salin</span>
                    </button>
                </div>
            </div>
            <div class="sm:text-right">
                <p class="text-xs font-semibold uppercase tracking-wider text-[#64748B] mb-1">Total Tagihan</p>
                <p class="text-2xl sm:text-3xl font-black text-[#4F46E5]">Rp {{ number_format($payment->jumlah, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Booking Details Preview --}}
        @if ($payment->booking)
            <div class="py-4 border-b border-[#F1F5F9]">
                <div class="rounded-xl bg-[#F8FAFC] p-4 border border-[#E2E8F0] grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs sm:text-sm">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#EEF2FF] text-[#4F46E5]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-[#64748B] text-xs block">Layanan</span>
                            <strong class="text-[#0F172A] font-semibold">{{ $payment->booking->layanan->namalayanan ?? 'Layanan' }}</strong>
                        </div>
                    </div>
                    <div class="flex items-center sm:justify-end gap-2.5">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#EEF2FF] text-[#4F46E5]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="sm:text-right">
                            <span class="text-[#64748B] text-xs block">Jadwal Sesi</span>
                            <strong class="text-[#0F172A] font-semibold">
                                {{ \Carbon\Carbon::parse($payment->booking->tanggalbooking)->translatedFormat('d M Y') }}, {{ $payment->booking->jam }} WIB
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Countdown Timer Box --}}
        <div class="pt-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-[#64748B] mb-1">Batas Waktu Pembayaran</p>
                <div class="flex items-center gap-2 text-[#EA580C] font-bold text-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span id="countdown">Memuat...</span>
                </div>
            </div>
            <p class="text-xs text-[#94A3B8] sm:text-right">
                Hingga {{ \Carbon\Carbon::parse($payment->expired_at)->translatedFormat('d M Y, H:i') }} WIB
            </p>
        </div>

        {{-- Action Button --}}
        <div class="mt-7">
            <button
                id="pay-button"
                type="button"
                class="w-full flex items-center justify-center gap-2.5 rounded-xl bg-[#4F46E5] px-6 py-4 text-sm sm:text-base font-bold text-white shadow-lg shadow-[#4F46E5]/25 transition-all hover:bg-[#4338CA] hover:shadow-xl hover:shadow-[#4F46E5]/30 active:scale-[0.99] cursor-pointer"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                <span>Buka Pilihan Pembayaran</span>
            </button>
            <p class="mt-2.5 text-center text-xs text-[#94A3B8]">
                Mendukung QRIS, GoPay, ShopeePay, Virtual Account BCA/Mandiri/BNI/BRI &amp; E-Wallet
            </p>
        </div>

        {{-- Realtime Auto-Detect Status Indicator (Subtle) --}}
        <div class="mt-6 pt-4 border-t border-[#F1F5F9] flex items-center justify-center gap-2 text-xs text-[#64748B]">
            <span class="relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
            </span>
            <span id="realtime-status-text">Sistem memantau pembayaran Anda secara otomatis</span>
        </div>

        {{-- Loading & Success Overlay --}}
        <div id="loading-overlay" class="absolute inset-0 z-20 hidden flex-col items-center justify-center bg-white/95 backdrop-blur-sm p-6 text-center transition-all">
            <div id="loading-spinner" class="loader mb-4"></div>
            <div id="success-icon" class="hidden mb-4 h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 animate-bounce">
                <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 id="overlay-title" class="text-base sm:text-lg font-bold text-[#0F172A]">
                Memproses Pembayaran...
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
            <span>Cek Status Pembayaran</span>
        </button>

        <button
            type="button"
            @click="showCancelModal = true"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-xl border border-transparent px-4 py-2.5 text-xs sm:text-sm font-medium text-[#64748B] hover:text-red-600 hover:bg-red-50/70 transition-all cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span>Batalkan &amp; Ganti Jadwal</span>
        </button>
    </div>

    {{-- Modal Konfirmasi Batalkan & Ganti Jadwal --}}
    <div
        x-show="showCancelModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
    >
        {{-- Backdrop --}}
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

        {{-- Modal Card --}}
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
                <form method="POST" action="{{ route('customer.booking.cancel', [$tenant->slug, $payment]) }}" class="inline">
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
    document.addEventListener('DOMContentLoaded', function () {
        const payButton = document.getElementById('pay-button');
        const checkStatusBtn = document.getElementById('check-status-btn');
        const loadingOverlay = document.getElementById('loading-overlay');
        const loadingSpinner = document.getElementById('loading-spinner');
        const successIcon = document.getElementById('success-icon');
        const overlayTitle = document.getElementById('overlay-title');
        const overlayDesc = document.getElementById('overlay-desc');
        const countdownEl = document.getElementById('countdown');
        const realtimeStatusText = document.getElementById('realtime-status-text');
        const realtimeStatusBanner = document.getElementById('realtime-status-banner');
        const csrfToken = '{{ csrf_token() }}';

        // Robust route URLs bound to order_id
        const callbackUrl = '{{ route("customer.booking.callback", [$tenant->slug, $payment]) }}';
        const checkStatusUrl = '{{ route("customer.booking.check-status", [$tenant->slug, $payment]) }}';

        let isProcessingSuccess = false;
        let pollInterval = null;
        let isChecking = false;

        // Expiry countdown
        const expiredAt = new Date('{{ \Carbon\Carbon::parse($payment->expired_at)->toISOString() }}').getTime();

        const timer = setInterval(function() {
            const now = new Date().getTime();
            const distance = expiredAt - now;

            if (distance < 0) {
                clearInterval(timer);
                stopPolling();
                countdownEl.innerHTML = "WAKTU HABIS";
                countdownEl.classList.replace('text-[#EA580C]', 'text-red-600');
                payButton.disabled = true;
                payButton.classList.replace('bg-[#4F46E5]', 'bg-gray-400');
                payButton.innerText = 'Waktu Pembayaran Telah Habis';

                setTimeout(() => window.location.reload(), 2000);
                return;
            }

            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            countdownEl.innerHTML = minutes + "m " + seconds + "s";
        }, 1000);

        const showSuccessState = (redirectUrl) => {
            if (isProcessingSuccess) return;
            isProcessingSuccess = true;
            stopPolling();

            loadingOverlay.classList.remove('hidden');
            loadingOverlay.classList.add('flex');
            loadingSpinner.classList.add('hidden');
            successIcon.classList.remove('hidden');
            successIcon.classList.add('flex');
            overlayTitle.innerText = 'Pembayaran Berhasil Dikonfirmasi!';
            overlayTitle.classList.add('text-emerald-600');
            overlayDesc.innerText = 'Mengarahkan ke halaman e-ticket invoice...';

            if (realtimeStatusBanner) {
                realtimeStatusBanner.classList.replace('border-emerald-200', 'border-emerald-400');
                realtimeStatusBanner.classList.replace('bg-emerald-50/90', 'bg-emerald-100');
                realtimeStatusText.innerText = '✓ Pembayaran berhasil diterima!';
            } else if (realtimeStatusText) {
                realtimeStatusText.innerText = '✓ Pembayaran berhasil diterima!';
            }

            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 1200);
        };

        const sendCallback = async (result) => {
            if (isProcessingSuccess) return;

            loadingOverlay.classList.remove('hidden');
            loadingOverlay.classList.add('flex');
            overlayTitle.innerText = 'Memverifikasi Pembayaran...';
            overlayDesc.innerText = 'Mohon tunggu sebentar.';

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
                    showSuccessState(data.redirect);
                } else if (data.status === 'pending') {
                    loadingOverlay.classList.add('hidden');
                    loadingOverlay.classList.remove('flex');
                    if (realtimeStatusText) {
                        realtimeStatusText.innerText = 'Menunggu pembayaran diselesaikan oleh bank...';
                    }
                } else {
                    loadingOverlay.classList.add('hidden');
                    loadingOverlay.classList.remove('flex');
                }
            } catch (error) {
                console.error('Callback error:', error);
                loadingOverlay.classList.add('hidden');
                loadingOverlay.classList.remove('flex');
            }
        };

        const checkPaymentStatus = async (isSilent = false) => {
            if (isProcessingSuccess || isChecking) return;
            isChecking = true;

            if (!isSilent) {
                checkStatusBtn.innerText = 'Mengecek...';
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
                    showSuccessState(data.redirect);
                    return;
                }

                if (data.status === 'gagal') {
                    stopPolling();
                    window.location.reload();
                    return;
                }

                if (!isSilent && data.message) {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Status check error:', error);
            } finally {
                isChecking = false;
                if (!isSilent) {
                    checkStatusBtn.innerHTML = `
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Cek Status Pembayaran Manual</span>
                    `;
                    checkStatusBtn.disabled = false;
                }
            }
        };

        const startAutoPolling = () => {
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = setInterval(() => {
                if (!document.hidden) {
                    checkPaymentStatus(true);
                }
            }, 2500);
        };

        const stopPolling = () => {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        };

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && !isProcessingSuccess) {
                checkPaymentStatus(true);
            }
        });

        payButton.addEventListener('click', function () {
            if (typeof snap === 'undefined') {
                alert('Midtrans Snap SDK gagal dimuat. Periksa koneksi internet Anda.');
                return;
            }

            snap.pay('{{ $snapToken }}', {
                onSuccess: function (result) {
                    sendCallback(result);
                },
                onPending: function (result) {
                    sendCallback(result);
                },
                onError: function (result) {
                    sendCallback(result);
                },
                onClose: function () {
                    checkPaymentStatus(true);
                }
            });
        });

        checkStatusBtn.addEventListener('click', () => checkPaymentStatus(false));

        // Start real-time background monitoring
        startAutoPolling();

        // Auto-launch Snap popup modal smoothly after initial render
        setTimeout(() => {
            if (typeof snap !== 'undefined' && payButton && !isProcessingSuccess) {
                payButton.click();
            }
        }, 500);
    });
</script>
@endsection
