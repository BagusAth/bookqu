@extends('customer.layouts.booking-shell')

@section('title', 'Selesaikan Pembayaran')
@section('current_step', 5)
@section('back_url', route(\App\Support\CustomerBookingRoutes::name('customer.booking.program'), $tenant->slug))
@section('back_label', 'Pilih Layanan Lain')

@section('head')
@if(!empty($snapUrl) && !empty($clientKey))
<script src="{{ $snapUrl }}" data-client-key="{{ $clientKey }}"></script>
@endif
@endsection

@section('content')
<div class="mx-auto max-w-2xl" x-data="{ showCancelModal: false, copied: false }">
    @php
        $currentState = $paymentState ?? ($payment->status === 'sukses' ? 'success' : ($payment->status === 'gagal' ? 'failed' : ($payment->isExpired() ? 'expired' : 'pending')));
        $displayBookings = $payment->bookings && $payment->bookings->isNotEmpty() ? $payment->bookings : ($payment->booking ? collect([$payment->booking]) : collect());
        $firstBooking = $displayBookings->first();
        $layanan = $firstBooking?->layanan;
        $durasiMenit = (int) ($layanan?->durasi ?? 60);
    @endphp

    {{-- State: FAILED --}}
    @include('customer.partials.payment.failed-state')

    {{-- State: EXPIRED --}}
    @include('customer.partials.payment.expired-state')

    {{-- State: PENDING --}}
    @include('customer.partials.payment.pending-card')

    {{-- Cancel Confirmation Modal --}}
    @include('customer.partials.payment.cancel-modal')
</div>
@endsection

@section('scripts')
@include('customer.partials.payment.scripts')
@endsection
