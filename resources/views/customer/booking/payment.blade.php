@extends('customer.layouts.booking-shell')

@section('title', 'Pembayaran')
@section('immersive', true)
@section('bottom_bar', true)

@section('head')
    @if (!empty($snapUrl) && !empty($clientKey))
        <script src="{{ $snapUrl }}" data-client-key="{{ $clientKey }}"></script>
    @endif
@endsection

@section('content')
    @php($currentState = $paymentState)
    @include('customer.partials.payment.brand-header', ['title' => match ($currentState) {
        'failed' => 'Pembayaran gagal',
        'expired' => 'Waktu pembayaran habis',
        default => 'Selesaikan pembayaran',
    }])

    <div class="mx-auto max-w-[1120px] px-4 md:max-w-[744px] md:px-8 lg:max-w-[1120px] lg:px-14">
        @include('customer.partials.payment.failed-state')
        @include('customer.partials.payment.expired-state')
        @include('customer.partials.payment.pending-card')
        @include('customer.partials.payment.cancel-modal')
    </div>
@endsection

@section('scripts')
    <script defer src="{{ asset('js/owner-ui.js') }}"></script>
    @include('customer.partials.payment.scripts', ['currentState' => $paymentState])
@endsection
