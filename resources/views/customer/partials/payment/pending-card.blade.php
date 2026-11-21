{{--
    Waiting for payment (design A §7.4). The element ids are the contract of partials/payment/scripts
    (countdown, status pill, check button, feedback banner, overlay); keep them when changing the markup.
--}}
@php
    $firstBooking = $displayBookings->first();
    $countdownTotal = max(900, (int) $paymentSeconds);
@endphp

<section id="state-pending-card" class="{{ $currentState === 'pending' ? 'block' : 'hidden' }} relative space-y-4">
    <div class="lg:grid lg:grid-cols-[minmax(0,1fr)_360px] lg:items-start lg:gap-6">
        <div id="countdown-card" class="sf-card-raised relative -mt-10 overflow-hidden p-[18px] md:p-7">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span id="payment-status-pill" class="sf-pill" data-tone="warning" role="status"><span id="payment-status-text">Menunggu pembayaran</span></span>
                <span class="sf-num text-[13px] text-sf-muted">No. {{ $payment->order_id }}</span>
            </div>

            <div class="mt-[18px] grid gap-3.5 md:grid-cols-2 md:gap-6">
                <div>
                    <p class="text-sm text-sf-muted">Total pembayaran</p>
                    <p class="sf-num mt-0.5 text-[34px] font-bold leading-[1.1] tracking-[-0.8px] md:text-[42px]">{{ $paymentTotal }}</p>
                </div>
                <div>
                    <p id="countdown-title" class="text-sm text-sf-muted"><span id="countdown-title-text">Sisa waktu</span></p>
                    <p class="mt-0.5 flex items-baseline gap-1.5" aria-hidden="true">
                        <span class="sf-countdown-value sf-num text-[34px] font-bold leading-[1.1] tracking-[-0.5px] text-sf-primary-text md:text-[42px]"><span id="countdown-min">00</span><span id="countdown-colon">:</span><span id="countdown-sec">00</span></span>
                        <span class="text-[13px] text-sf-muted">menit</span>
                    </p>
                </div>
            </div>
            <div id="countdown-progress" role="progressbar" aria-label="Sisa waktu pembayaran" aria-valuemin="0"
                aria-valuemax="{{ $countdownTotal }}" aria-valuenow="{{ (int) $paymentSeconds }}" class="sf-meter mt-3.5">
                <span style="width: {{ round(min(100, max(0, $paymentSeconds / $countdownTotal * 100))) }}%"></span>
            </div>
            <p class="mt-2 text-[13px] text-sf-muted">Bayar sebelum <strong class="sf-num text-sf-text">{{ $paymentDeadline }} WIB</strong>. Setelah itu jadwal dilepas.</p>

            <div class="mt-[22px] hidden flex-wrap gap-2.5 md:flex">
                <button type="button" id="pay-button" data-pay-button class="sf-btn sf-btn-lg min-h-[54px] flex-[2_1_220px] text-base">Bayar sekarang</button>
                <button type="button" id="check-status-btn" data-check-status aria-busy="false" class="sf-btn-secondary min-h-[54px] flex-[1_1_180px]">Periksa status</button>
            </div>
            <button type="button" data-check-status aria-busy="false" class="sf-btn-secondary mt-4 w-full md:hidden">Periksa status</button>

            <div class="mt-[18px] rounded-sf-card-small bg-sf-tint px-4 py-3.5 text-sm leading-[21px]">
                <strong>Cara bayar:</strong> pilih Bayar sekarang, pilih metode dan ikuti petunjuknya, lalu kembali ke halaman ini. Status berubah sendiri saat pembayaran diterima.
            </div>

            <div id="inline-feedback-banner" class="hidden" role="alert">
                <span id="inline-feedback-icon" aria-hidden="true"></span>
                <span id="inline-feedback-text" class="min-w-0 flex-1"></span>
                <button type="button" onclick="hideInlineFeedback()" aria-label="Tutup pesan" class="sf-btn-icon grid min-h-11 place-items-center">
                    <x-customer.icon name="x" :size="16" :stroke="2" />
                </button>
            </div>

            <div class="mt-3.5 flex flex-wrap items-center justify-between gap-2">
                <span class="inline-flex items-center gap-2.5 text-[13px] text-sf-muted">
                    <span class="size-2 rounded-full bg-sf-success shadow-[0_0_0_4px_var(--sf-success-bg)]" aria-hidden="true"></span>
                    <span id="realtime-status-text" role="status">Status dipantau otomatis.</span>
                </span>
                @include('customer.partials.payment.action-buttons')
            </div>

            <div id="loading-overlay" class="absolute inset-0 z-30 hidden items-center justify-center bg-sf-surface/95 p-4 text-center" aria-live="polite">
                <div>
                    <p id="loading-spinner" class="text-[13px] text-sf-muted">Mohon tunggu…</p>
                    <span id="success-icon" class="hidden justify-center text-sf-success" aria-hidden="true"><x-customer.icon name="check" :size="28" :stroke="2.4" /></span>
                    <h2 id="overlay-title" class="text-lg font-bold">Membuka pembayaran</h2>
                    <p id="overlay-desc" class="mt-1 text-sm text-sf-muted">Tunggu hingga halaman siap.</p>
                </div>
            </div>
        </div>

        <aside aria-label="Rincian reservasi" class="sf-card mt-3 overflow-hidden md:mt-4 lg:-mt-10" x-data="{ detailsOpen: false }">
            <button type="button" class="flex min-h-[52px] w-full items-center justify-between gap-3 bg-sf-tint px-4 text-left md:hidden"
                x-on:click="detailsOpen = !detailsOpen" x-bind:aria-expanded="detailsOpen.toString()" aria-controls="rincian-pembayaran">
                <span class="text-[15px] font-bold">Rincian reservasi</span>
                <span class="text-[13px] text-sf-primary-text" x-text="detailsOpen ? 'Tutup' : 'Lihat'">Lihat</span>
            </button>
            <div class="hidden bg-sf-tint px-5 py-4 md:block">
                <p class="text-xs font-bold text-sf-primary-text">Rincian reservasi</p>
                <p class="mt-1 text-base font-bold">{{ $firstBooking?->layanan?->namalayanan ?? 'Reservasi' }}</p>
            </div>
            <dl id="rincian-pembayaran" class="flex-col gap-2.5 px-5 py-4 text-sm md:flex" x-bind:class="detailsOpen ? 'flex' : 'hidden'">
                <div class="flex justify-between gap-3">
                    <dt class="text-sf-muted">Nomor pesanan</dt>
                    <dd class="sf-num text-right font-semibold">{{ $payment->order_id }}</dd>
                </div>
                @foreach ($displayBookings as $booking)
                    <div class="flex justify-between gap-3">
                        <dt class="text-sf-muted">{{ $loop->count > 1 ? ($booking->layanan?->namalayanan ?? 'Sesi') : 'Jadwal' }}</dt>
                        <dd class="sf-num text-right font-semibold">{{ $sessionRanges[$booking->id]->start->locale('id')->translatedFormat('D, d M Y') }} · {{ $sessionRanges[$booking->id]->displayLabel() }} WIB</dd>
                    </div>
                @endforeach
                @if ($firstBooking?->namapelanggan)
                    <div class="flex justify-between gap-3">
                        <dt class="text-sf-muted">Pemesan</dt>
                        <dd class="text-right font-semibold">{{ $firstBooking->namapelanggan }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-3 border-t border-sf-divider pt-2.5">
                    <dt class="text-sf-muted">Total</dt>
                    <dd class="sf-num text-right font-bold">{{ $paymentTotal }}</dd>
                </div>
            </dl>
        </aside>
    </div>

    @if ($currentState === 'pending')
        <x-customer.bottom-bar class="md:hidden" aria-label="Bayar">
            <button type="button" data-pay-button class="sf-btn sf-btn-lg w-full">Bayar sekarang · <span class="sf-num">{{ $paymentTotal }}</span></button>
        </x-customer.bottom-bar>
    @endif
</section>
