<section id="state-pending-card" class="{{ $currentState === 'pending' ? 'block' : 'hidden' }} relative space-y-4">
    <x-ui.panel>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <x-customer.page-header title="Selesaikan pembayaran" />
            <x-customer.status label="Menunggu pembayaran" tone="warning" />
        </div>
        <p class="mt-3 text-bq-small text-bq-text-muted">Total pembayaran</p>
        <p class="bq-num text-bq-figure-small text-bq-ink">{{ $paymentTotal }}</p>

        <section id="countdown-card" class="mt-4 rounded-bq-panel border border-bq-warning-border bg-bq-warning-bg p-4">
            <p id="countdown-title" class="flex items-center gap-2 text-bq-label text-bq-warning-copy">
                <span id="countdown-dot" class="size-2 rounded-full bg-bq-warning" aria-hidden="true"></span>
                <span id="countdown-title-text">Sisa waktu pembayaran</span>
            </p>
            <p class="bq-num mt-2 text-bq-section tabular-nums" aria-hidden="true">
                <span id="countdown-min">00</span><span id="countdown-colon">:</span><span id="countdown-sec">00</span>
            </p>
            <p class="mt-1 text-bq-small">Bayar sebelum <strong class="bq-num">{{ $paymentDeadline }} WIB</strong>.</p>
        </section>

        <ol class="mt-4 list-inside list-decimal space-y-1 text-bq-body text-bq-text-muted">
            <li>Pilih Bayar sekarang.</li>
            <li>Pilih metode pembayaran yang tersedia dan ikuti petunjuknya.</li>
            <li>Kembali ke halaman ini untuk melihat status pembayaran.</li>
        </ol>

        <div id="inline-feedback-banner" class="mt-4 hidden" role="alert">
            <span id="inline-feedback-icon" aria-hidden="true"></span>
            <span id="inline-feedback-text" class="min-w-0 flex-1"></span>
            <button type="button" onclick="hideInlineFeedback()" aria-label="Tutup pesan" class="bq-btn bq-btn-ghost min-h-11">
                <x-owner.icon name="x" class="size-4" />
            </button>
        </div>

        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-bq-border bg-bq-surface p-4 sm:static sm:mt-4 sm:border-0 sm:p-0">
            <x-ui.button id="pay-button" variant="primary" class="min-h-11 w-full sm:w-auto">Bayar sekarang</x-ui.button>
        </div>
    </x-ui.panel>

    <x-ui.panel title="Rincian reservasi">
        <x-ui.kv :items="['Nomor pesanan' => $payment->order_id]" />
        <ul class="mt-2 divide-y divide-bq-border">
            @foreach ($displayBookings as $booking)
                <li class="flex flex-wrap justify-between gap-2 py-2 text-bq-body">
                    <span>{{ $booking->layanan?->namalayanan ?? 'Layanan' }}</span>
                    <span class="bq-num">{{ $sessionRanges[$booking->id]->start->locale('id')->translatedFormat('d M Y') }} · {{ $sessionRanges[$booking->id]->displayLabel() }} WIB</span>
                </li>
            @endforeach
        </ul>
        <div class="mt-3">
            @include('customer.partials.payment.action-buttons')
        </div>
        <p id="realtime-status-text" class="mt-3 text-bq-small text-bq-text-muted" role="status">Sistem memantau pembayaran Anda secara otomatis.</p>
    </x-ui.panel>

    <div id="loading-overlay" class="absolute inset-0 z-30 hidden items-center justify-center bg-bq-surface/95 p-4 text-center">
        <div>
            <p id="loading-spinner" class="text-bq-small text-bq-text-muted">Mohon tunggu…</p>
            <span id="success-icon" class="hidden justify-center text-bq-success" aria-hidden="true"><x-owner.icon name="check" class="size-6" /></span>
            <h2 id="overlay-title" class="text-bq-section">Membuka pembayaran</h2>
            <p id="overlay-desc" class="mt-1 text-bq-body text-bq-text-muted">Tunggu hingga halaman siap.</p>
        </div>
    </div>
</section>
