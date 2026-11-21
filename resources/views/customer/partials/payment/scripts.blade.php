<script>
    const snapToken = @js($payment->snap_token ?? null);
    const paymentUrl = @js($payment->payment_url ?? null);
    const callbackUrl = @js(\App\Support\CustomerBookingRoutes::url("customer.booking.callback", [$tenant->slug, $payment]));
    const checkStatusUrl = @js(\App\Support\CustomerBookingRoutes::url("customer.booking.check-status", [$tenant->slug, $payment]));
    const csrfToken = @js(csrf_token());

    function showInlineFeedback(message, type = 'error') {
        const banner = document.getElementById('inline-feedback-banner');
        const text = document.getElementById('inline-feedback-text');
        const icon = document.getElementById('inline-feedback-icon');
        if (!banner || !text) return;

        text.innerText = message;
        // Design A §2.3: status colours via the customer note (BQ-165).
        banner.className = 'sf-note mt-4 items-center';
        banner.dataset.tone = type === 'error' ? 'danger' : 'info';
        if (type === 'error') {
            icon.innerHTML = '<svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        } else {
            icon.innerHTML = '<svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        }
    }

    function hideInlineFeedback() {
        const banner = document.getElementById('inline-feedback-banner');
        if (banner) banner.classList.add('hidden');
    }

    function setPageTitle(text) {
        const title = document.getElementById('page-title');
        if (title) title.innerText = text;
    }

    function showFailedState() {
        setPageTitle('Pembayaran gagal');
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
        setPageTitle('Waktu pembayaran habis');
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
        if (paymentUrl) {
            window.location.href = paymentUrl;
            return;
        }

        if (!snapToken) {
            showInlineFeedback('Token pembayaran tidak tersedia atau telah kadaluarsa.', 'error');
            return;
        }

        if (typeof snap === 'undefined') {
            showInlineFeedback('Sistem pembayaran belum termuat. Periksa koneksi internet Anda lalu coba lagi.', 'error');
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
    // Polling stops after 5 minutes or 3 consecutive failures (BQ-051)
    const MAX_POLL_ATTEMPTS = 120;
    const MAX_POLL_FAILURES = 3;
    let pollAttempts = 0;
    let pollFailures = 0;

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
            successIcon.classList.add('flex');
        }
        if (overlayTitle) {
            overlayTitle.innerText = 'Pembayaran diterima';
            overlayTitle.className = 'mt-2 text-lg font-bold text-sf-success';
        }
        if (overlayDesc) overlayDesc.innerText = 'Membuka bukti reservasi…';
        if (realtimeStatusText) realtimeStatusText.innerText = 'Pembayaran diterima.';

        setTimeout(() => {
            window.location.href = redirectUrl;
        }, 1200);
    }

    async function checkPaymentStatus(isSilent = false) {
        if (isProcessingSuccess || isChecking) return;
        isChecking = true;

        const checkStatusBtn = document.getElementById('check-status-btn');
        if (!isSilent && checkStatusBtn) {
            setCheckingState(true);
        }

        try {
            const response = await fetch(checkStatusUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            // 419: this page's form token expired; 404: the access session ended. Retrying cannot help.
            if (response.status === 419 || response.status === 404) {
                stopAutoPolling();
                showInlineFeedback(response.status === 419
                    ? 'Sesi halaman ini telah berakhir. Muat ulang halaman untuk melihat status terbaru.'
                    : 'Akses ke halaman pembayaran ini telah berakhir. Muat ulang halaman atau buka kembali tautan dari email konfirmasi.', 'error');
                return;
            }

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const data = await response.json();
            pollFailures = 0;

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
            pollFailures++;
            if (pollFailures >= MAX_POLL_FAILURES) {
                stopAutoPolling();
            }
            if (!isSilent) {
                showInlineFeedback('Gagal memeriksa status pembayaran. Periksa koneksi internet Anda.', 'error');
            }
        } finally {
            isChecking = false;
            if (!isSilent && checkStatusBtn) {
                setCheckingState(false);
            }
        }
    }

    // Design A §7.4: checking state on the button and the status pill (BQ-165).
    function setCheckingState(checking) {
        const pill = document.getElementById('payment-status-pill');
        const pillText = document.getElementById('payment-status-text');
        const monitor = document.getElementById('realtime-status-text');
        document.querySelectorAll('[data-check-status]').forEach(button => {
            button.innerText = checking ? 'Memeriksa…' : 'Periksa status';
            button.disabled = checking;
            button.setAttribute('aria-busy', checking ? 'true' : 'false');
        });
        if (pill) pill.dataset.tone = checking ? 'info' : 'warning';
        if (pillText) pillText.innerText = checking ? 'Memeriksa pembayaran…' : 'Menunggu pembayaran';
        if (monitor && checking) monitor.innerText = 'Menghubungi penyedia pembayaran, tunggu sebentar.';
        if (monitor && !checking) monitor.innerText = 'Status dipantau otomatis.';
    }

    function startAutoPolling() {
        if (pollInterval) clearInterval(pollInterval);
        pollAttempts = 0;
        pollFailures = 0;
        pollInterval = setInterval(() => {
            if (++pollAttempts > MAX_POLL_ATTEMPTS) {
                stopAutoPolling();
                return;
            }
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
        const payButtons = document.querySelectorAll('[data-pay-button]');
        const countdownProgress = document.getElementById('countdown-progress');
        const countdownTotal = Math.max(900, @js($paymentSeconds));
        const checkStatusBtn = document.getElementById('check-status-btn');
        const countdownCard = document.getElementById('countdown-card');
        const countdownTitleText = document.getElementById('countdown-title-text');
        const countdownMin = document.getElementById('countdown-min');
        const countdownSec = document.getElementById('countdown-sec');

        // Expiry countdown: synchronized with server remaining seconds
        let remainingSeconds = @js($paymentSeconds);
        // Count against a fixed deadline: background tabs (e.g. while paying in an e-wallet app) throttle timers
        const deadlineAt = Date.now() + remainingSeconds * 1000;

        // Declared before updateCountdown() runs: an expired page must not hit the TDZ (BQ-048)
        let timer = null;

        function updateCountdown() {
            if (remainingSeconds <= 0) {
                if (timer) clearInterval(timer);
                stopAutoPolling();
                // Let the server reconcile at the deadline; a late success still redirects (BQ-064 T4-08)
                checkPaymentStatus(true);
                if (countdownCard) countdownCard.dataset.urgent = 'true';
                if (countdownProgress) countdownProgress.setAttribute('aria-valuenow', '0');
                if (countdownTitleText) {
                    countdownTitleText.innerText = "Waktu pembayaran habis";
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
            if (countdownProgress) {
                countdownProgress.setAttribute('aria-valuenow', String(remainingSeconds));
                countdownProgress.setAttribute('aria-valuetext', minutes + ' menit ' + seconds + ' detik');
                const bar = countdownProgress.firstElementChild;
                if (bar) bar.style.width = Math.max(0, Math.min(100, remainingSeconds / countdownTotal * 100)) + '%';
            }

            // S5.1: Urgency state change when <= 3 minutes (180 seconds)
            if (remainingSeconds <= 180 && countdownCard) {
                countdownCard.dataset.urgent = 'true';
            }
        }

        updateCountdown();
        if (remainingSeconds > 0) {
            timer = setInterval(function() {
                remainingSeconds = Math.max(0, Math.round((deadlineAt - Date.now()) / 1000));
                updateCountdown();
            }, 1000);
        }

        payButtons.forEach(button => button.addEventListener('click', openSnapPayment));

        document.querySelectorAll('[data-check-status]').forEach(button => button.addEventListener('click', () => checkPaymentStatus(false)));

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
