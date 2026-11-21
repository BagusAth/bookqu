{{-- Payment time ran out (design A §7.8). The id and the exact block/hidden class are read by partials/payment/scripts. --}}
@php($restartUrl = $storefrontUrl ?? \App\Support\CustomerBookingRoutes::url('customer.booking.program', $tenant->slug))
<section id="state-expired-card" class="{{ $currentState === 'expired' ? 'block' : 'hidden' }}">
    <div class="sf-card-raised relative mx-auto -mt-10 max-w-[560px] px-6 py-8 text-center">
        <span class="mx-auto grid size-14 place-items-center rounded-sf-card-small bg-sf-warning-bg text-sf-warning">
            <x-customer.icon name="clock" :size="26" />
        </span>
        <h2 class="mt-3.5 text-xl font-bold">Waktu pembayaran habis</h2>
        <p class="mx-auto mt-1.5 max-w-[380px] text-sm leading-[21px] text-sf-muted">Jadwal yang ditahan sudah dilepas. Pilih jadwal lagi untuk memesan kembali.</p>
        <p class="sf-num mt-3 text-[13px] text-sf-muted">No. {{ $payment->order_id }}</p>
        <a href="{{ $restartUrl }}" class="sf-btn sf-btn-lg mt-5 hidden md:inline-flex">Pilih jadwal lagi</a>
        <x-customer.status-note tone="warning" class="mt-5 text-left">Sudah terlanjur membayar? Simpan bukti pembayaran dan hubungi bisnis.</x-customer.status-note>
    </div>
    {{-- The bar sits inside the section, so it shows only while this state is shown. --}}
    <x-customer.bottom-bar class="md:hidden" aria-label="Pesan lagi">
        <a href="{{ $restartUrl }}" class="sf-btn sf-btn-lg w-full">Pilih jadwal lagi</a>
    </x-customer.bottom-bar>
</section>
