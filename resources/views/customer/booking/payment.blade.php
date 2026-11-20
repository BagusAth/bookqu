@extends('customer.layouts.booking-shell')

@section('title', 'Pembayaran')
@section('current_step', 3)

@section('head')
    @if (!empty($snapUrl) && !empty($clientKey))
        <script src="{{ $snapUrl }}" data-client-key="{{ $clientKey }}"></script>
    @endif
@endsection

@section('content')
    <div class="mx-auto max-w-2xl">
        @php($currentState = $paymentState)
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
