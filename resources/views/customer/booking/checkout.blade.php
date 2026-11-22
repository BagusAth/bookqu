@extends('customer.layouts.booking-shell')

@section('title', 'Data & pembayaran')
@section('immersive', true)
@section('bottom_bar', true)

@php
    $scheduleUrl = \App\Support\CustomerBookingRoutes::url('customer.booking.schedule', $tenant->slug);
    $oldAddOnQuantities = collect(old('selected_addons', []))->pluck('quantity', 'id')->filter(fn ($quantity) => is_numeric($quantity))->all();
    $oldPromoCode = is_string(old('voucher_code')) ? old('voucher_code') : '';
    $checkoutConfig = [
        'slug' => $tenant->slug,
        'quoteUrl' => \App\Support\CustomerBookingRoutes::url('customer.booking.validate-voucher', $tenant->slug),
        'total' => $checkoutSummary['total'],
        'participants' => (int) old('peserta', 1),
        'voucherCode' => $oldPromoCode,
        'addOnOptions' => $addOnOptions,
        'quantities' => $oldAddOnQuantities,
        'name' => old('namapelanggan', ''),
        'email' => old('email', ''),
        'phone' => old('nomorhp', ''),
    ];
    $sessionTimes = collect($checkoutSummary['sessions'])->pluck('time');
    $timeLabel = $sessionTimes->count() > 1
        ? \Illuminate\Support\Str::before($sessionTimes->first(), ' – ').' – '.\Illuminate\Support\Str::after($sessionTimes->last(), ' – ')
        : $sessionTimes->first();
    $policyLines = [
        'Jadwal bisa diubah sendiri paling lambat '.($bookingPolicy['reschedule_before_hours'] ?? 24).' jam sebelum sesi.',
        ($bookingPolicy['refunds_enabled'] ?? false)
            ? 'Pengembalian dana bisa diajukan paling lambat '.($bookingPolicy['cancel_before_hours'] ?? 24).' jam sebelum sesi dan ditinjau bisnis.'
            : 'Pembatalan dan pengembalian dana diatur lewat bisnis.',
        'Jadwal ditahan selama pembayaran berlangsung.',
    ];
@endphp

@section('content')
    <x-customer.step-header title="Data & pembayaran" :step="3" :back-url="$scheduleUrl" back-label="Kembali ke jadwal" overlap>
        <dl class="mt-[18px] hidden grid-cols-3 gap-2.5 md:grid lg:hidden">
            <div class="rounded-sf-card-small bg-sf-hero-faint px-3.5 py-3">
                <dt class="text-xs text-sf-hero-soft">Layanan</dt>
                <dd class="truncate text-sm font-bold" title="{{ $service->namalayanan }}">{{ $service->namalayanan }}</dd>
            </div>
            <div class="rounded-sf-card-small bg-sf-hero-faint px-3.5 py-3">
                <dt class="text-xs text-sf-hero-soft">Jadwal</dt>
                <dd class="sf-num text-sm font-bold">{{ $checkoutSummary['date'] }} · {{ $timeLabel }}</dd>
            </div>
            <div class="rounded-sf-card-small bg-sf-hero-faint px-3.5 py-3">
                <dt class="text-xs text-sf-hero-soft">Ubah jadwal</dt>
                <dd class="text-sm font-bold">Sampai {{ $bookingPolicy['reschedule_before_hours'] ?? 24 }} jam sebelum</dd>
            </div>
        </dl>
    </x-customer.step-header>

    <div id="booking-checkout-root" data-tenant-slug="{{ $tenant->slug }}" x-data="customerCheckout(@js($checkoutConfig))"
        @booking-reset-submitting.window="submitting = false">
        <form id="booking-checkout-form" method="POST" @submit="submit($event)"
            action="{{ \App\Support\CustomerBookingRoutes::url('customer.booking.process-checkout', $tenant->slug) }}"
            class="relative mx-auto grid max-w-[1240px] gap-3.5 px-4 md:max-w-[760px] md:px-8 lg:max-w-[1240px] lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start lg:gap-6 lg:px-14">
            <input type="hidden" name="_token" value="{{ csrf_token() }}" x-ref="checkoutToken">
            <input type="hidden" name="checkout_fingerprint" value="{{ $checkoutFingerprint }}" x-ref="checkoutFingerprint">

            <div class="-mt-9 min-w-0 space-y-3.5 lg:-mt-12">
                {{-- Phone: collapsible order summary on the header. --}}
                <section aria-label="Ringkasan pesanan" class="sf-card-raised overflow-hidden md:hidden" x-data="{ summaryOpen: false }">
                    <button type="button" class="flex min-h-14 w-full items-center gap-3 px-4 py-3.5 text-left" x-on:click="summaryOpen = !summaryOpen"
                        x-bind:aria-expanded="summaryOpen.toString()" aria-controls="ringkasan-ponsel">
                        <span class="grid size-10 shrink-0 place-items-center rounded-sf-card-small bg-sf-tint-strong text-sf-primary-text">
                            <x-customer.icon name="calendar" :stroke="1.6" />
                        </span>
                        <span class="min-w-0 grow">
                            <span class="block truncate text-sm font-bold">{{ $service->namalayanan }}</span>
                            <span class="sf-num block text-xs text-sf-muted">{{ $timeLabel }} · <span x-text="summaryOpen ? 'Tutup rincian' : 'Lihat rincian'">Lihat rincian</span></span>
                        </span>
                        <span class="sf-num text-[15px] font-bold" x-text="totalLabel">{{ $checkoutSummary['total'] }}</span>
                    </button>
                    <div id="ringkasan-ponsel" x-show="summaryOpen" x-cloak class="border-t border-sf-divider px-4 pb-4 pt-3">
                        <dl class="space-y-2.5 text-sm">
                            @include('customer.partials.booking.checkout-summary-desktop')
                            <div class="flex justify-between gap-4 border-t border-sf-divider pt-2.5 text-[15px] font-bold">
                                <dt>Total</dt>
                                <dd class="sf-num" x-text="totalLabel">{{ $checkoutSummary['total'] }}</dd>
                            </div>
                        </dl>
                    </div>
                </section>

                @if ($pendingCheckout)
                    <x-customer.status-note tone="warning" title="Pembayaran sebelumnya belum selesai">
                        {{ $pendingCheckout['service'] }} · {{ $pendingCheckout['date'] }} · <span class="sf-num">{{ $pendingCheckout['times'] }} WIB</span>.
                        Bayar sebelum <span class="sf-num">{{ $pendingCheckout['deadline'] }}</span> atau batalkan dulu dari halaman pembayaran.
                        <a href="{{ $pendingCheckout['url'] }}" class="mt-1 block font-bold underline">Lanjutkan pembayaran</a>
                    </x-customer.status-note>
                @endif

                @if ($errors->any())
                    <x-customer.status-note tone="danger" role="alert" :title="'Periksa '.$errors->count().' isian.'">{{ $errors->first() }}</x-customer.status-note>
                @endif

                @include('customer.partials.booking.checkout-form')
            </div>

            {{-- Desktop: sticky summary with the pay button. --}}
            <x-customer.summary-panel class="relative hidden lg:-mt-12 lg:block" eyebrow="Ringkasan reservasi" :title="$service->namalayanan">
                @include('customer.partials.booking.checkout-summary-desktop')
                <x-slot:total><span x-text="totalLabel">{{ $checkoutSummary['total'] }}</span></x-slot:total>
                <x-slot:action>
                    <button type="submit" id="submit-checkout-btn" class="sf-btn sf-btn-lg w-full" disabled
                        :disabled="!ready || submitting" :aria-busy="submitting.toString()">
                        Lanjut ke pembayaran
                    </button>
                </x-slot:action>
                <x-slot:note>
                    <ul class="space-y-1">
                        @foreach ($policyLines as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                </x-slot:note>
            </x-customer.summary-panel>

            <x-customer.bottom-bar aria-label="Total dan pembayaran">
                <x-slot:summary>
                    <p class="text-[13px] text-sf-muted">Total</p>
                    <p class="sf-num text-xl font-bold" x-text="totalLabel">{{ $checkoutSummary['total'] }}</p>
                </x-slot:summary>
                <button type="submit" id="mobile-submit-checkout-btn" class="sf-btn sf-btn-lg px-6" disabled
                    :disabled="!ready || submitting" :aria-busy="submitting.toString()">Lanjut ke pembayaran</button>
            </x-customer.bottom-bar>
        </form>
    </div>
@endsection

@section('scripts')
    <script defer src="{{ asset('js/booking-checkout.js') }}?v={{ filemtime(public_path('js/booking-checkout.js')) }}"></script>
@endsection
