{{-- Payment failed (design A §7.8). The id and the exact block/hidden class are read by partials/payment/scripts. --}}
@php($restartUrl = $storefrontUrl ?? \App\Support\CustomerBookingRoutes::url('customer.booking.program', $tenant->slug))
<section id="state-failed-card" class="{{ $currentState === 'failed' ? 'block' : 'hidden' }}">
    <div class="sf-card-raised relative mx-auto -mt-10 max-w-[560px] px-6 py-8 text-center">
        <span class="mx-auto grid size-14 place-items-center rounded-sf-card-small bg-sf-danger-bg text-sf-danger">
            <x-customer.icon name="alert" :size="26" />
        </span>
        <h2 class="mt-3.5 text-xl font-bold">Pembayaran gagal</h2>
        <p class="mx-auto mt-1.5 max-w-[380px] text-sm leading-[21px] text-sf-muted">Pesanan ini tidak bisa dibayar lagi. Pilih jadwal lagi untuk membuat pesanan baru.</p>
        <dl class="sf-num mx-auto mt-3 flex max-w-[320px] justify-between gap-4 text-[13px] text-sf-muted">
            <div><dt class="sr-only">Nomor pesanan</dt><dd>No. {{ $payment->order_id }}</dd></div>
            <div><dt class="sr-only">Total</dt><dd class="font-semibold text-sf-text">{{ $paymentTotal }}</dd></div>
        </dl>
        <div class="mt-5 hidden flex-wrap justify-center gap-2 md:flex">
            <a href="{{ $restartUrl }}" class="sf-btn sf-btn-lg">Coba bayar lagi</a>
            @if ($merchantWaUrl ?? null)
                <a href="{{ $merchantWaUrl }}" target="_blank" rel="noopener noreferrer" class="sf-btn-secondary sf-btn-lg">Hubungi bisnis</a>
            @endif
        </div>
    </div>
    {{-- The bar sits inside the section, so it shows only while this state is shown. --}}
    <x-customer.bottom-bar class="md:hidden" aria-label="Coba lagi">
        <a href="{{ $restartUrl }}" class="sf-btn sf-btn-lg w-full">Coba bayar lagi</a>
    </x-customer.bottom-bar>
</section>
