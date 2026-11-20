@extends('customer.layouts.booking-shell')

@section('title', 'Data pemesan')
@section('current_step', 3)
@section('back_url', \App\Support\CustomerBookingRoutes::url('customer.booking.schedule', $tenant->slug))
@section('back_label', 'Kembali ke jadwal')

@section('content')
    @php
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
    @endphp

    <div id="booking-checkout-root" data-tenant-slug="{{ $tenant->slug }}" x-data="customerCheckout(@js($checkoutConfig))"
        class="mx-auto max-w-5xl space-y-4" @booking-reset-submitting.window="submitting = false">
        <x-customer.page-header title="Data pemesan" description="Pastikan nama, email, dan nomor WhatsApp Anda sudah benar." />
        @if ($errors->any())
            <x-ui.banner :title="$errors->first()" tone="danger" />
        @endif
        @if ($pendingCheckout)
            <x-customer.pending-checkout-banner :pending="$pendingCheckout" />
        @endif

        <form id="booking-checkout-form" method="POST" @submit="submit($event)"
            action="{{ \App\Support\CustomerBookingRoutes::url('customer.booking.process-checkout', $tenant->slug) }}"
            class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_340px]">
            <input type="hidden" name="_token" value="{{ csrf_token() }}" x-ref="checkoutToken">
            <input type="hidden" name="checkout_fingerprint" value="{{ $checkoutFingerprint }}" x-ref="checkoutFingerprint">

            @include('customer.partials.booking.checkout-form')

            <aside class="hidden space-y-3 lg:sticky lg:top-24 lg:block lg:self-start">
                @include('customer.partials.booking.checkout-summary-desktop')
                <button type="submit" id="submit-checkout-btn" class="bq-btn bq-btn-primary min-h-11 w-full"
                    disabled :disabled="!ready || submitting">Lanjut ke pembayaran</button>
            </aside>

            <div class="fixed inset-x-0 bottom-0 z-40 border-t border-bq-border bg-bq-surface px-4 py-3 lg:hidden">
                <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-2">
                    <x-ui.button x-on:click="$dispatch('open-dialog', { id: 'checkout-summary', trigger: $el })" class="min-h-11">
                        <span class="text-bq-small">Ringkasan</span>
                        <span class="bq-num whitespace-nowrap" x-text="totalLabel">{{ $checkoutSummary['total'] }}</span>
                    </x-ui.button>
                    <button type="submit" id="mobile-submit-checkout-btn" class="bq-btn bq-btn-primary min-h-11"
                        disabled :disabled="!ready || submitting">Lanjut ke pembayaran</button>
                </div>
            </div>

            <x-ui.sheet id="checkout-summary" title="Rincian pemesanan" x-on:keydown.escape.window="if (open) hide()">
                @include('customer.partials.booking.checkout-summary-desktop')
            </x-ui.sheet>
        </form>
    </div>
@endsection

@section('scripts')
    <script defer src="{{ asset('js/owner-ui.js') }}"></script>
    <script defer src="{{ asset('js/booking-checkout.js') }}"></script>
@endsection
