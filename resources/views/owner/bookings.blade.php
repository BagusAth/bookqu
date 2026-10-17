@extends('layouts.owner-layout')

@section('title', 'Bookings')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    detailOpen: false,
    activeBooking: null,
    viewBooking(b) {
        this.activeBooking = b;
        this.detailOpen = true;
    }
}">

    {{-- ── Header ── --}}
    @include('components.owner.page-header', [
        'judul' => 'Bookings Management',
        'subjudul' => '',
    ])

    {{-- ── Flash Messages ── --}}
    @if (session('sukses'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 flex items-center gap-2">
            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('sukses') }}</span>
        </div>
    @endif
    @if ($errors->has('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 flex items-center gap-2">
            <svg class="h-5 w-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>{{ $errors->first('error') }}</span>
        </div>
    @endif

    {{-- ── Top Summary Stats ── --}}
    @include('owner.partials.bookings.summary-stats')

    {{-- ── Search & Filter Controls ── --}}
    @include('owner.partials.bookings.filters')

    {{-- ── Bookings Mobile Cards (Screen < sm) ── --}}
    @include('owner.partials.bookings.mobile-cards')

    {{-- ── Bookings Desktop Table (Screen >= sm) ── --}}
    @include('owner.partials.bookings.desktop-table')

    {{-- ── Pagination ── --}}
    @if ($daftarbooking->hasPages())
        <div class="flex justify-center">
            {{ $daftarbooking->appends(['status' => $filterstatus, 'katakunci' => $katakunci])->links() }}
        </div>
    @endif

    {{-- ── Centered Booking Detail Modal ── --}}
    @include('owner.partials.bookings.detail-modal')

    {{-- Component Modal Reschedule Booking --}}
    <x-owner.modal-reschedule-booking />

</div>
@endsection
