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
                if (data.is_expired) {
                    showExpiredState();
                } else {
                    showFailedState();
                }
                return;
            }

            if (data.status === 'kadaluarsa' || data.is_expired) {
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
