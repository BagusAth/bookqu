@extends('customer.layouts.booking-shell')

@section('title', 'Pembayaran')
@section('current_step', 5)

@section('head')
<script src="{{ $snapUrl }}" data-client-key="{{ $clientKey }}"></script>
@endsection

@section('content')
<div class="mx-auto max-w-2xl">
    {{-- Header Content --}}
    <div class="text-center mb-6">
        <h1 class="text-xl sm:text-2xl font-black text-[#0F172A] tracking-tight">Selesaikan Pembayaran</h1>
        <p class="mt-1 text-sm text-[#64748B]">Pilih metode pembayaran yang Anda inginkan dan selesaikan transaksi.</p>
    </div>

    {{-- Realtime Auto-Detect Status Banner --}}
    <div id="realtime-status-banner" class="mb-5 flex items-center justify-center gap-2.5 rounded-2xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-xs sm:text-sm font-semibold text-emerald-800 shadow-2xs transition-all">
        <span class="relative flex h-2.5 w-2.5">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
        </span>
        <span id="realtime-status-text">Sistem memantau pembayaran Anda secara otomatis...</span>
    </div>

    {{-- Main Payment Card --}}
    <div class="relative overflow-hidden rounded-2xl border border-[#E2E8F0] bg-white p-6 sm:p-8 shadow-sm mb-6">
        {{-- Order ID & Total --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-[#F1F5F9]">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-[#64748B] mb-1">Kode Pesanan (Order ID)</p>
                <p class="font-mono text-base sm:text-lg font-bold text-[#0F172A]">{{ $payment->order_id }}</p>
            </div>
            <div class="sm:text-right">
                <p class="text-xs font-semibold uppercase tracking-wider text-[#64748B] mb-1">Total Tagihan</p>
                <p class="text-2xl sm:text-3xl font-black text-[#4F46E5]">Rp {{ number_format($payment->jumlah, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Booking Details Preview --}}
        @if ($payment->booking)
            <div class="py-5 border-b border-[#F1F5F9] grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs sm:text-sm">
                <div>
                    <span class="text-[#64748B]">Layanan:</span>
                    <strong class="text-[#0F172A] block sm:inline ml-0 sm:ml-1">{{ $payment->booking->layanan->namalayanan ?? 'Layanan' }}</strong>
                </div>
                <div class="sm:text-right">
                    <span class="text-[#64748B]">Jadwal:</span>
                    <strong class="text-[#0F172A] block sm:inline ml-0 sm:ml-1">
                        {{ \Carbon\Carbon::parse($payment->booking->tanggalbooking)->translatedFormat('d M Y') }}, {{ $payment->booking->jam }} WIB
                    </strong>
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
        <div class="mt-8">
            <button
                id="pay-button"
                type="button"
                class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#4F46E5] px-6 py-4 text-sm sm:text-base font-bold text-white shadow-lg shadow-[#4F46E5]/25 transition-all hover:bg-[#4338CA] hover:shadow-xl hover:shadow-[#4F46E5]/30 active:scale-98 cursor-pointer"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                <span>Buka Pilihan Pembayaran</span>
            </button>
            <p class="mt-3 text-center text-xs font-medium text-[#94A3B8]">Mendukung QRIS, GoPay, ShopeePay, Virtual Account BCA/Mandiri/BNI/BRI &amp; E-Wallet</p>
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

    {{-- Seamless Info Box --}}
    <div class="flex gap-3 rounded-2xl border border-blue-200 bg-blue-50/70 p-4 text-xs sm:text-sm text-blue-900 mb-6">
        <svg class="w-5 h-5 shrink-0 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p class="leading-relaxed">
            Setelah menyelesaikan pembayaran di HP (QRIS/VA/E-Wallet), halaman ini akan <strong>otomatis mendeteksi status berhasil</strong> dan langsung mengalihkan Anda ke tiket invoice.
        </p>
    </div>

    {{-- Manual Check Status Fallback Button --}}
    <div class="text-center">
        <button
            id="check-status-btn"
            type="button"
            class="text-xs sm:text-sm font-semibold text-[#4F46E5] hover:text-[#4338CA] hover:underline inline-flex items-center gap-1.5 transition-colors cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>Cek Status Pembayaran Manual</span>
        </button>
    </div>
</div>
@endsection

@section('scripts')
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
    });
</script>
@endsection
