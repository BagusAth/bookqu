@extends('layouts.owner-layout')

@section('title', 'Checkout — Pembayaran')

@section('content')
<div class="mx-auto max-w-5xl space-y-6" x-data="paymentPage()">

    {{-- Stepper --}}
    <div class="flex items-center justify-center gap-0 py-2">
        <div class="flex items-center gap-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-bq-primary text-sm font-bold text-white shadow-md shadow-bq-primary/30">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <span class="hidden text-sm font-semibold text-bq-primary sm:inline">Review</span>
        </div>
        <div class="mx-3 h-0.5 w-8 rounded bg-bq-primary sm:w-16"></div>
        <div class="flex items-center gap-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-bq-primary text-sm font-bold text-white shadow-md shadow-bq-primary/30 animate-pulse">2</div>
            <span class="hidden text-sm font-semibold text-bq-primary sm:inline">Pembayaran</span>
        </div>
        <div class="mx-3 h-0.5 w-8 rounded bg-bq-border sm:w-16"></div>
        <div class="flex items-center gap-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-bq-border text-sm font-semibold text-bq-text-muted">3</div>
            <span class="hidden text-sm font-medium text-bq-text-muted sm:inline">Invoice</span>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">

        {{-- Left Column: Payment Area --}}
        <div class="lg:col-span-3 space-y-5">

            <div>
                <h1 class="text-xl font-bold text-bq-text">Selesaikan Pembayaran</h1>
                <p class="mt-1 text-sm text-bq-text-muted">Klik tombol di bawah untuk membuka metode pembayaran.</p>
            </div>

            {{-- Payment Card --}}
            <div class="rounded-xl border border-bq-border bg-bq-surface p-6 space-y-5">

                {{-- Status Indicator --}}
                <div class="flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 shrink-0">
                        <svg class="h-5 w-5 text-amber-600 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-amber-800">Menunggu Pembayaran</p>
                        <p class="text-xs text-amber-700">Order ID: <span class="font-mono font-bold">{{ $payment->order_id }}</span></p>
                    </div>
                </div>

                {{-- Payment Actions --}}
                <div class="space-y-3">
                    {{-- Open Midtrans Snap --}}
                    <button type="button" id="btn-pay-now"
                        @click="openSnap()"
                        class="w-full rounded-xl bg-bq-primary py-4 text-sm font-bold text-white shadow-lg shadow-bq-primary/30 transition-all hover:bg-bq-primary-hover hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Bayar Sekarang
                    </button>

                    {{-- Reopen Payment --}}
                    <button type="button" id="btn-reopen-payment"
                        @click="openSnap()"
                        class="w-full rounded-xl border border-bq-border bg-bq-background py-3.5 text-sm font-semibold text-bq-text transition-all hover:bg-bq-surface hover:border-bq-primary hover:text-bq-primary flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Buka Ulang Pembayaran
                    </button>

                    {{-- Check Status --}}
                    <button type="button" id="btn-check-status"
                        @click="checkStatus()"
                        :disabled="isChecking"
                        class="w-full rounded-xl border border-emerald-200 bg-emerald-50 py-3.5 text-sm font-semibold text-emerald-700 transition-all hover:bg-emerald-100 hover:border-emerald-300 flex items-center justify-center gap-2 disabled:opacity-50">
                        <template x-if="!isChecking">
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Cek Status Pembayaran
                            </span>
                        </template>
                        <template x-if="isChecking">
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Memeriksa...
                            </span>
                        </template>
                    </button>
                </div>

                {{-- Status Message --}}
                <div x-show="statusMessage" x-transition
                    :class="{
                        'bg-emerald-50 border-emerald-200 text-emerald-800': statusType === 'sukses',
                        'bg-amber-50 border-amber-200 text-amber-800': statusType === 'pending',
                        'bg-rose-50 border-rose-200 text-rose-800': statusType === 'gagal' || statusType === 'error'
                    }"
                    class="rounded-lg border p-3 text-sm font-medium">
                    <span x-text="statusMessage"></span>
                </div>

                {{-- Info --}}
                <div class="rounded-lg bg-bq-background p-4 space-y-2">
                    <p class="text-xs font-semibold text-bq-text uppercase tracking-wider">Instruksi Pembayaran</p>
                    <ul class="space-y-1.5 text-xs text-bq-text-muted">
                        <li class="flex items-start gap-2">
                            <span class="text-bq-primary font-bold mt-0.5">1.</span>
                            Klik <strong>"Bayar Sekarang"</strong> untuk membuka jendela pembayaran Midtrans.
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-bq-primary font-bold mt-0.5">2.</span>
                            Pilih metode pembayaran (QRIS, GoPay, Transfer Bank, dll).
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-bq-primary font-bold mt-0.5">3.</span>
                            Selesaikan pembayaran sesuai instruksi.
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-bq-primary font-bold mt-0.5">4.</span>
                            Jika pop-up tertutup, klik <strong>"Buka Ulang Pembayaran"</strong>.
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-bq-primary font-bold mt-0.5">5.</span>
                            Jika status tidak berubah otomatis, klik <strong>"Cek Status Pembayaran"</strong>.
                        </li>
                    </ul>
                </div>

                {{-- Supported Payments --}}
                <div class="pt-2 border-t border-bq-border">
                    <p class="text-xs text-bq-text-subtle text-center">Didukung oleh Midtrans — QRIS, GoPay, ShopeePay, BCA VA, BNI VA, Mandiri, BRI, dan lainnya.</p>
                </div>
            </div>
        </div>

        {{-- Right Column: Summary --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Countdown Timer --}}
            <div class="rounded-xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 p-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/20">
                        <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-amber-700">Batas waktu pembayaran</p>
                        <p class="text-lg font-bold tabular-nums" :class="countdown <= 300 ? 'text-red-600' : 'text-amber-800'" x-text="countdownDisplay">--:--</p>
                    </div>
                </div>
                <p x-show="countdown <= 300 && countdown > 0" class="mt-2 text-xs font-medium text-red-600 animate-pulse">⚠️ Waktu hampir habis! Segera selesaikan pembayaran.</p>
            </div>

            {{-- Order Summary --}}
            <div class="rounded-xl border border-bq-border bg-bq-surface p-5 space-y-4">
                <div class="border-b border-bq-border pb-3">
                    <h2 class="text-base font-semibold text-bq-text">Ringkasan Pesanan</h2>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-bq-text-muted">Order ID</span>
                        <span class="font-mono text-xs font-bold text-bq-text">{{ $payment->order_id }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-bq-text-muted">Paket</span>
                        <span class="font-semibold text-bq-text capitalize">{{ $plan->namapaket }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-bq-text-muted">Pembayar</span>
                        <span class="font-medium text-bq-text">{{ $payment->nama_pembayar }}</span>
                    </div>
                    <div class="border-t border-dashed border-bq-border pt-3 flex justify-between">
                        <span class="text-sm font-bold text-bq-text">Total</span>
                        <span class="text-lg font-bold text-bq-primary">Rp {{ number_format($payment->jumlah, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Back Link --}}
            <a href="{{ route('owner.subscription') }}" class="flex items-center gap-1.5 text-sm font-medium text-bq-text-muted transition-colors hover:text-bq-primary" id="btn-back-subscription">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Kembali ke Subscription
            </a>
        </div>
    </div>
</div>

{{-- Midtrans Snap JS --}}
<script src="{{ $snapUrl }}" data-client-key="{{ $clientKey }}"></script>

<script>
function paymentPage() {
    return {
        isChecking: false,
        statusMessage: '',
        statusType: '',
        countdown: {{ max(0, $payment->expired_at ? $payment->expired_at->diffInSeconds(now(), false) * -1 : 3600) }},
        countdownDisplay: '--:--',
        timer: null,
        snapOpened: false,

        init() {
            this.startCountdown();
            // Auto-open Snap setelah halaman dimuat
            this.$nextTick(() => {
                setTimeout(() => this.openSnap(), 800);
            });
        },

        startCountdown() {
            this.updateDisplay();
            this.timer = setInterval(() => {
                this.countdown--;
                this.updateDisplay();
                if (this.countdown <= 0) {
                    clearInterval(this.timer);
                    this.countdownDisplay = '00:00';
                    this.statusMessage = 'Waktu pembayaran telah habis. Silakan buat pesanan baru.';
                    this.statusType = 'gagal';
                }
            }, 1000);
        },

        updateDisplay() {
            if (this.countdown <= 0) {
                this.countdownDisplay = '00:00';
                return;
            }
            const mins = Math.floor(this.countdown / 60);
            const secs = this.countdown % 60;
            this.countdownDisplay = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        },

        openSnap() {
            if (this.countdown <= 0) {
                this.statusMessage = 'Waktu pembayaran telah habis. Silakan buat pesanan baru.';
                this.statusType = 'gagal';
                return;
            }

            const self = this;

            window.snap.pay('{{ $snapToken }}', {
                onSuccess: function(result) {
                    self.statusMessage = 'Pembayaran berhasil! Mengarahkan ke halaman invoice...';
                    self.statusType = 'sukses';
                    self.sendCallback(result);
                    setTimeout(() => {
                        window.location.href = '{{ route("owner.checkout.invoice", $payment->id) }}';
                    }, 1500);
                },
                onPending: function(result) {
                    self.statusMessage = 'Pembayaran sedang diproses. Silakan selesaikan pembayaran Anda.';
                    self.statusType = 'pending';
                    self.sendCallback(result);
                },
                onError: function(result) {
                    self.statusMessage = 'Pembayaran gagal. Silakan coba lagi.';
                    self.statusType = 'gagal';
                    self.sendCallback(result);
                },
                onClose: function() {
                    if (!self.statusMessage) {
                        self.statusMessage = 'Pop-up pembayaran ditutup. Klik "Buka Ulang Pembayaran" jika ingin melanjutkan.';
                        self.statusType = 'pending';
                    }
                }
            });
        },

        async sendCallback(result) {
            try {
                await fetch('{{ route("owner.checkout.callback", $payment->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ result: result })
                });
            } catch (e) {
                console.error('Callback error:', e);
            }
        },

        async checkStatus() {
            if (this.isChecking) return;
            this.isChecking = true;
            this.statusMessage = '';

            try {
                const response = await fetch('{{ route("owner.checkout.check-status", $payment->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                this.statusMessage = data.message || 'Status tidak diketahui';
                this.statusType = data.status || 'pending';

                if (data.status === 'sukses' && data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                }
            } catch (e) {
                this.statusMessage = 'Gagal memeriksa status. Coba lagi.';
                this.statusType = 'error';
            } finally {
                this.isChecking = false;
            }
        },

        destroy() {
            if (this.timer) clearInterval(this.timer);
        }
    }
}
</script>
@endsection
