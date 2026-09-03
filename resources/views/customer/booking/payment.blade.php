<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ $tenant->namabisnis }} - Selesaikan Pembayaran</title>
        @vite('resources/css/app.css')
        <link rel="stylesheet" href="{{ asset('css/booking-program.css') }}" />
        <!-- Midtrans Snap JS -->
        <script type="text/javascript" src="{{ $snapUrl }}" data-client-key="{{ $clientKey }}"></script>
        <style>
            .loader {
                border: 4px solid #f3f3f3;
                border-radius: 50%;
                border-top: 4px solid #3498db;
                width: 40px;
                height: 40px;
                -webkit-animation: spin 1s linear infinite;
                animation: spin 1s linear infinite;
            }
            @-webkit-keyframes spin {
                0% { -webkit-transform: rotate(0deg); }
                100% { -webkit-transform: rotate(360deg); }
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    </head>
    <body class="booking-page bg-[#F9FAFB]">
        <div id="booking-payment-root" data-tenant-slug="{{ $tenant->slug }}">
            <header class="border-b border-[#E5E7EB] bg-white/95 backdrop-blur">
                <nav class="booking-shell mx-auto flex w-full max-w-[1280px] items-center justify-between px-6 py-4" x-data="{ navOpen: false }">
                    <div class="flex items-center gap-2">
                        <a href="/" class="flex items-center">
                            <img src="{{ asset('images/logo.png') }}" alt="BookQu Logo" class="h-8 w-auto" />
                        </a>
                    </div>
                    <div class="hidden items-center gap-8 text-sm font-medium text-[#6B7280] lg:flex">
                        <a class="transition hover:text-[#111827]" href="#">Features</a>
                        <a class="transition hover:text-[#111827]" href="#">Solutions</a>
                        <a class="transition hover:text-[#111827]" href="#">Pricing</a>
                        <a class="transition hover:text-[#111827]" href="#">About</a>
                    </div>
                    <div class="hidden items-center gap-4 lg:flex">
                        <a class="text-sm font-semibold text-[#6B7280] transition hover:text-[#111827]" href="#">Login</a>
                        <a class="rounded-xl bg-[#4F46E5] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#4338CA]" href="#">Get Started</a>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border border-[#E5E7EB] p-2 text-[#111827] transition hover:border-[#4F46E5] hover:text-[#4F46E5] lg:hidden"
                        @click="navOpen = !navOpen"
                        aria-label="Toggle navigation"
                    >
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div
                        class="absolute left-0 right-0 top-full border-t border-[#E5E7EB] bg-white px-6 py-4 shadow-sm lg:hidden"
                        x-cloak
                        x-show="navOpen"
                        x-transition
                    >
                        <div class="flex flex-col gap-4 text-sm font-medium text-[#6B7280]">
                            <a class="transition hover:text-[#111827]" href="#">Features</a>
                            <a class="transition hover:text-[#111827]" href="#">Solutions</a>
                            <a class="transition hover:text-[#111827]" href="#">Pricing</a>
                            <a class="transition hover:text-[#111827]" href="#">About</a>
                            <div class="flex items-center gap-3 pt-2">
                                <a class="flex-1 rounded-xl border border-[#E5E7EB] px-4 py-2 text-center text-sm font-semibold text-[#111827]" href="#">Login</a>
                                <a class="flex-1 rounded-xl bg-[#4F46E5] px-4 py-2 text-center text-sm font-semibold text-white" href="#">Get Started</a>
                            </div>
                        </div>
                    </div>
                </nav>
            </header>

            <main class="booking-shell mx-auto w-full max-w-2xl px-6 pb-12 pt-10">
                <div class="mb-8 text-center">
                    <h1 class="text-2xl font-bold text-[#111827] sm:text-3xl">Selesaikan Pembayaran</h1>
                    <p class="mt-2 text-sm text-[#6B7280] sm:text-base">Pilih metode pembayaran dan selesaikan transaksi Anda.</p>
                </div>

                <!-- Realtime Monitoring Indicator -->
                <div id="realtime-status-banner" class="mb-4 flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50/90 px-4 py-2.5 text-xs font-medium text-emerald-800 shadow-sm transition-all">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    </span>
                    <span id="realtime-status-text">Sistem memantau pembayaran Anda secara otomatis...</span>
                </div>

                <!-- Payment Detail Card -->
                <div class="relative overflow-hidden rounded-2xl border border-[#E5E7EB] bg-white p-6 mb-6 booking-shadow">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <div>
                            <p class="text-sm font-medium text-[#6B7280] mb-1">Order ID</p>
                            <p class="font-mono text-base font-semibold text-[#111827]">{{ $payment->order_id }}</p>
                        </div>
                        <div class="sm:text-right">
                            <p class="text-sm font-medium text-[#6B7280] mb-1">Total Bayar</p>
                            <p class="text-2xl font-bold text-[#4F46E5]">Rp {{ number_format($payment->jumlah, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="border-t border-[#E5E7EB] pt-6">
                        <p class="text-sm font-medium text-[#6B7280] mb-2">Batas Waktu Pembayaran:</p>
                        <div class="flex items-center gap-2 text-[#EA580C] font-semibold text-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span id="countdown">Memuat...</span>
                        </div>
                        <p class="text-xs text-[#9CA3AF] mt-1">Hingga {{ \Carbon\Carbon::parse($payment->expired_at)->translatedFormat('d M Y, H:i') }}</p>
                    </div>

                    <div class="mt-8">
                        <button id="pay-button" class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#4F46E5] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#4338CA] shadow-sm">
                            Pilih Metode Pembayaran
                        </button>
                        <p class="mt-3 text-center text-xs font-medium text-[#9CA3AF]">Didukung oleh Midtrans</p>
                    </div>
                    
                    <!-- Loading / Success Overlay -->
                    <div id="loading-overlay" class="absolute inset-0 z-10 hidden flex-col items-center justify-center bg-white/95 backdrop-blur-sm p-6 text-center transition-all">
                        <div id="loading-spinner" class="loader mb-4"></div>
                        <div id="success-icon" class="hidden mb-4 h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 animate-bounce">
                            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 id="overlay-title" class="text-base font-bold text-[#111827]">
                            Memproses Pembayaran...
                        </h3>
                        <p id="overlay-desc" class="mt-1 text-xs text-[#6B7280]">
                            Mohon jangan tutup halaman ini.
                        </p>
                    </div>
                </div>
                
                <div class="flex gap-3 rounded-xl border border-[#DBEAFE] bg-[#EFF6FF] p-4 text-sm text-[#1D4ED8]">
                    <svg class="w-5 h-5 shrink-0 text-[#2563EB]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="leading-relaxed">Setelah membayar lewat QRIS, Virtual Account, atau E-Wallet, website akan <strong>langsung mengonfirmasi otomatis</strong> tanpa perlu menekan tombol apa pun.</p>
                </div>
                
                <div class="mt-6 text-center">
                    <button id="check-status-btn" class="text-sm font-semibold text-[#4F46E5] transition hover:text-[#4338CA] hover:underline inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Cek Status Pembayaran Manual
                    </button>
                </div>
            </main>

            <footer class="border-t border-[#E5E7EB] bg-[#EDEBFA]">
                <div class="booking-shell mx-auto w-full max-w-[1280px] px-6 py-10">
                    <div class="grid gap-8 md:grid-cols-4">
                        <div class="md:col-span-1">
                            <a href="/" class="mb-4 flex items-center">
                                <img src="{{ asset('images/logo.png') }}" alt="BookQu Logo" class="h-8 w-auto" />
                            </a>
                            <p class="mt-3 text-sm text-[#6B7280]">
                                Platform manajemen booking terjangkau di Indonesia untuk membantu bisnis jasa dan profesional tampil digital.
                            </p>
                            <div class="mt-4 flex items-center gap-3 text-[#6B7280]">
                                <span class="h-8 w-8 rounded-full border border-[#E5E7EB] bg-white"></span>
                                <span class="h-8 w-8 rounded-full border border-[#E5E7EB] bg-white"></span>
                                <span class="h-8 w-8 rounded-full border border-[#E5E7EB] bg-white"></span>
                            </div>
                        </div>
                        <div>
                            <h5 class="text-sm font-semibold text-[#111827]">Produk</h5>
                            <ul class="mt-3 space-y-2 text-sm text-[#6B7280]">
                                <li>Fitur Utama</li>
                                <li>Integrasi Pembayaran</li>
                                <li>Mobile App</li>
                                <li>API Dokumentasi</li>
                            </ul>
                        </div>
                        <div>
                            <h5 class="text-sm font-semibold text-[#111827]">Solusi</h5>
                            <ul class="mt-3 space-y-2 text-sm text-[#6B7280]">
                                <li>Salon &amp; Spa</li>
                                <li>Klinik Kesehatan</li>
                                <li>Studio Foto</li>
                                <li>Konsultan Jasa</li>
                            </ul>
                        </div>
                        <div>
                            <h5 class="text-sm font-semibold text-[#111827]">Kontak</h5>
                            <ul class="mt-3 space-y-2 text-sm text-[#6B7280]">
                                <li>support@bookqu.com</li>
                                <li>+62 21 4567 8810</li>
                                <li>Sudirman CBD, Jakarta Selatan, Indonesia</li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-8 flex flex-col gap-3 border-t border-[#E5E7EB] pt-6 text-xs text-[#6B7280] md:flex-row md:items-center md:justify-between">
                        <span>&copy; 2026 BookQu. Hak Cipta Dilindungi Undang-Undang.</span>
                        <span>Ketentuan Privasi | Syarat &amp; Ketentuan</span>
                    </div>
                </div>
            </footer>
        </div>

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
                
                // Route URLs bound to the actual payment order_id (NOT numerical id)
                const callbackUrl = '{{ route("customer.booking.callback", [$tenant->slug, $payment]) }}';
                const checkStatusUrl = '{{ route("customer.booking.check-status", [$tenant->slug, $payment]) }}';

                let isProcessingSuccess = false;
                let pollInterval = null;
                let isChecking = false;

                // Cek jika expired (format ISO8601 agar JS tidak salah zona waktu)
                const expiredAt = new Date('{{ \Carbon\Carbon::parse($payment->expired_at)->toISOString() }}').getTime();
                
                // Countdown timer
                const timer = setInterval(function() {
                    const now = new Date().getTime();
                    const distance = expiredAt - now;
                    
                    if (distance < 0) {
                        clearInterval(timer);
                        stopPolling();
                        countdownEl.innerHTML = "WAKTU HABIS";
                        countdownEl.classList.replace('text-[#EA580C]', 'text-[#EF4444]');
                        payButton.disabled = true;
                        payButton.classList.replace('bg-[#4F46E5]', 'bg-[#9CA3AF]');
                        payButton.classList.replace('hover:bg-[#4338CA]', 'bg-[#9CA3AF]');
                        payButton.innerText = 'Waktu Pembayaran Habis';
                        
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

                    // Tampilkan ikon sukses animasi
                    loadingOverlay.classList.remove('hidden');
                    loadingOverlay.classList.add('flex');
                    loadingSpinner.classList.add('hidden');
                    successIcon.classList.remove('hidden');
                    successIcon.classList.add('flex');
                    overlayTitle.innerText = 'Pembayaran Berhasil Dikonfirmasi!';
                    overlayTitle.classList.add('text-emerald-600');
                    overlayDesc.innerText = 'Mengarahkan ke halaman e-ticket invoice...';

                    // Update banner
                    if (realtimeStatusBanner) {
                        realtimeStatusBanner.classList.replace('border-emerald-200', 'border-emerald-400');
                        realtimeStatusBanner.classList.replace('bg-emerald-50/90', 'bg-emerald-100');
                        realtimeStatusText.innerText = '✓ Pembayaran berhasil diterima!';
                    }

                    setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 1200);
                };

                // Kirim callback dari Midtrans Snap ke backend
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

                // Auto Poller: mengecek status ke backend di background
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
                                Cek Status Pembayaran Manual
                            `;
                            checkStatusBtn.disabled = false;
                        }
                    }
                };

                const startAutoPolling = () => {
                    if (pollInterval) clearInterval(pollInterval);
                    // Lakukan polling setiap 2.5 detik
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

                // Begitu tab kembali aktif setelah user bayar di HP/aplikasi lain, langsung cek!
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden && !isProcessingSuccess) {
                        checkPaymentStatus(true);
                    }
                });

                // Trigger Snap Popup
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

                // Start background monitoring immediately
                startAutoPolling();
            });
        </script>
    </body>
</html>
