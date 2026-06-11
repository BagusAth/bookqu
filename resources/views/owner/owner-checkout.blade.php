@extends('layouts.owner-layout')

@section('title', 'Checkout — Review Pesanan')

@section('content')
<div class="mx-auto max-w-5xl space-y-6" x-data="checkoutPage()">

    {{-- Stepper --}}
    <div class="flex items-center justify-center gap-0 py-2">
        <div class="flex items-center gap-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-bq-primary text-sm font-bold text-white shadow-md shadow-bq-primary/30">1</div>
            <span class="hidden text-sm font-semibold text-bq-primary sm:inline">Review Pesanan</span>
        </div>
        <div class="mx-3 h-0.5 w-8 rounded bg-bq-border sm:w-16"></div>
        <div class="flex items-center gap-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-bq-border text-sm font-semibold text-bq-text-muted">2</div>
            <span class="hidden text-sm font-medium text-bq-text-muted sm:inline">Pembayaran</span>
        </div>
        <div class="mx-3 h-0.5 w-8 rounded bg-bq-border sm:w-16"></div>
        <div class="flex items-center gap-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-bq-border text-sm font-semibold text-bq-text-muted">3</div>
            <span class="hidden text-sm font-medium text-bq-text-muted sm:inline">Invoice</span>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">

        {{-- Left Column: Form --}}
        <div class="lg:col-span-3 space-y-5">

            {{-- Page Title --}}
            <div>
                <h1 class="text-xl font-bold text-bq-text">Review Pesanan & Data Kontak</h1>
                <p class="mt-1 text-sm text-bq-text-muted">Pastikan semua informasi sudah benar sebelum melanjutkan ke pembayaran.</p>
            </div>

            <form action="{{ route('owner.checkout.process') }}" method="POST" class="space-y-5" id="checkout-form" @submit="isSubmitting = true">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plan->id }}">

                {{-- Data Kontak --}}
                <div class="rounded-xl border border-bq-border bg-bq-surface p-5 space-y-4">
                    <div class="flex items-center gap-2 border-b border-bq-border pb-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-bq-primary/10">
                            <svg class="h-4 w-4 text-bq-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <h2 class="text-sm font-semibold text-bq-text">Data Kontak Pembayar</h2>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label for="nama_pembayar" class="mb-1 block text-xs font-medium text-bq-text-muted uppercase tracking-wider">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_pembayar" id="nama_pembayar"
                                value="{{ old('nama_pembayar', $user->namalengkap) }}"
                                class="w-full rounded-lg border border-bq-border bg-bq-background px-4 py-2.5 text-sm text-bq-text placeholder-bq-text-subtle transition-colors focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                                placeholder="Masukkan nama lengkap" required>
                            @error('nama_pembayar')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email_pembayar" class="mb-1 block text-xs font-medium text-bq-text-muted uppercase tracking-wider">Email <span class="text-red-500">*</span></label>
                            <input type="email" name="email_pembayar" id="email_pembayar"
                                value="{{ old('email_pembayar', $user->email) }}"
                                class="w-full rounded-lg border border-bq-border bg-bq-background px-4 py-2.5 text-sm text-bq-text placeholder-bq-text-subtle transition-colors focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                                placeholder="email@contoh.com" required>
                            <p class="mt-1 text-xs text-bq-text-subtle">Invoice akan dikirim ke email ini</p>
                            @error('email_pembayar')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="hp_pembayar" class="mb-1 block text-xs font-medium text-bq-text-muted uppercase tracking-wider">Nomor WhatsApp <span class="text-red-500">*</span></label>
                            <input type="tel" name="hp_pembayar" id="hp_pembayar"
                                value="{{ old('hp_pembayar', $user->nomorhp) }}"
                                class="w-full rounded-lg border border-bq-border bg-bq-background px-4 py-2.5 text-sm text-bq-text placeholder-bq-text-subtle transition-colors focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                                placeholder="08xxxxxxxxxx" required>
                            @error('hp_pembayar')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Catatan Tambahan --}}
                <div class="rounded-xl border border-bq-border bg-bq-surface p-5 space-y-3">
                    <div class="flex items-center gap-2 border-b border-bq-border pb-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/10">
                            <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </div>
                        <h2 class="text-sm font-semibold text-bq-text">Catatan Tambahan <span class="text-xs font-normal text-bq-text-subtle">(Opsional)</span></h2>
                    </div>
                    <textarea name="catatan" id="catatan" rows="3"
                        class="w-full rounded-lg border border-bq-border bg-bq-background px-4 py-2.5 text-sm text-bq-text placeholder-bq-text-subtle transition-colors focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20 resize-none"
                        placeholder="Tulis catatan tambahan jika ada...">{{ old('catatan') }}</textarea>
                </div>

                {{-- Submit Button (Mobile) --}}
                <div class="lg:hidden">
                    <button type="submit" id="btn-checkout-submit-mobile"
                        class="w-full rounded-xl bg-bq-primary py-3.5 text-sm font-bold text-white shadow-lg shadow-bq-primary/30 transition-all hover:bg-bq-primary-hover hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isSubmitting">
                        <span x-show="!isSubmitting">Lanjutkan ke Pembayaran →</span>
                        <span x-show="isSubmitting" class="flex items-center justify-center gap-2">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Memproses...
                        </span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Right Column: Booking Summary --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Countdown Timer --}}
            <div class="rounded-xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 p-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/20">
                        <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-amber-700">Selesaikan dalam</p>
                        <p class="text-lg font-bold text-amber-800 tabular-nums" x-text="countdownDisplay">60:00</p>
                    </div>
                </div>
            </div>

            {{-- Plan Summary --}}
            <div class="rounded-xl border border-bq-border bg-bq-surface p-5 space-y-4">
                <div class="border-b border-bq-border pb-3">
                    <h2 class="text-base font-semibold text-bq-text">Ringkasan Pesanan</h2>
                    <p class="text-xs text-bq-text-muted">Review pilihan Anda</p>
                </div>

                {{-- Selected Plan --}}
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-bq-primary/10 shrink-0">
                        <svg class="h-5 w-5 text-bq-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-bq-text-muted uppercase tracking-wider">Paket Dipilih</p>
                        <p class="text-sm font-bold text-bq-text capitalize">{{ $plan->namapaket }}</p>
                    </div>
                </div>

                {{-- Plan Features --}}
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/10 shrink-0">
                        <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-bq-text-muted uppercase tracking-wider">Fitur</p>
                        <ul class="mt-1 space-y-1 text-xs text-bq-text-muted">
                            <li>• {{ $plan->isunlimited ? 'Unlimited' : $plan->maxlayanan }} layanan</li>
                            <li>• {{ $plan->isunlimited ? 'Unlimited' : number_format($plan->maxbooking) }} booking/bulan</li>
                            <li>• Durasi 1 bulan</li>
                        </ul>
                    </div>
                </div>

                {{-- Periode --}}
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-500/10 shrink-0">
                        <svg class="h-5 w-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-bq-text-muted uppercase tracking-wider">Periode</p>
                        <p class="text-sm font-semibold text-bq-text">{{ now()->format('d M Y') }} — {{ now()->addMonth()->format('d M Y') }}</p>
                    </div>
                </div>

                {{-- Price Breakdown --}}
                <div class="border-t border-bq-border pt-4 space-y-2.5">
                    <div class="flex justify-between text-sm">
                        <span class="text-bq-text-muted">Harga Paket {{ ucfirst($plan->namapaket) }}</span>
                        <span class="font-medium text-bq-text">Rp {{ number_format($plan->hargabulanan, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-bq-text-muted">Biaya Layanan Platform</span>
                        <span class="font-medium text-bq-text">Rp {{ number_format($biayaPlatform, 0, ',', '.') }}</span>
                    </div>
                    <div class="border-t border-dashed border-bq-border pt-2.5 flex justify-between">
                        <span class="text-sm font-bold text-bq-text">Total Pembayaran</span>
                        <span class="text-lg font-bold text-bq-primary">Rp {{ number_format($plan->hargabulanan + $biayaPlatform, 0, ',', '.') }}</span>
                    </div>
                </div>

                {{-- Submit Button (Desktop) --}}
                <div class="hidden lg:block pt-2">
                    <button type="submit" form="checkout-form" id="btn-checkout-submit"
                        class="w-full rounded-xl bg-bq-primary py-3.5 text-sm font-bold text-white shadow-lg shadow-bq-primary/30 transition-all hover:bg-bq-primary-hover hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isSubmitting">
                        <span x-show="!isSubmitting">Lanjutkan ke Pembayaran →</span>
                        <span x-show="isSubmitting" class="flex items-center justify-center gap-2">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Memproses...
                        </span>
                    </button>
                    <div class="mt-3 flex items-center justify-center gap-1.5 text-xs text-bq-text-subtle">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Pembayaran aman & terenkripsi
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

<script>
function checkoutPage() {
    return {
        isSubmitting: false,
        countdown: 3600, // 1 jam dalam detik
        countdownDisplay: '60:00',
        timer: null,

        init() {
            this.startCountdown();
        },

        startCountdown() {
            this.updateDisplay();
            this.timer = setInterval(() => {
                this.countdown--;
                this.updateDisplay();
                if (this.countdown <= 0) {
                    clearInterval(this.timer);
                    this.countdownDisplay = '00:00';
                }
            }, 1000);
        },

        updateDisplay() {
            const mins = Math.floor(this.countdown / 60);
            const secs = this.countdown % 60;
            this.countdownDisplay = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        },

        destroy() {
            if (this.timer) clearInterval(this.timer);
        }
    }
}
</script>
@endsection
