@extends('layouts.owner-layout')

@section('title', 'Calendar')

@section('content')
<div
    class="mx-auto max-w-7xl space-y-6"
    x-data="{
        viewMode: '{{ $view }}',
        selectedSlot: null,
        modalOpen: false,
        walkinMode: false,
        mobileWeekDay: '{{ $currentDate->toDateString() }}',

        openDetail(slot) {
            if (slot.is_booking && slot.raw_booking_id) {
                const url = @js(route('owner.bookings.panel', ['booking' => 'BOOKING_ID'])).replace('BOOKING_ID', slot.raw_booking_id);
                this.$dispatch('open-booking-panel', { url, trigger: document.activeElement });
                return;
            }
            this.selectedSlot = slot;
            this.walkinMode = false;
            this.modalOpen = true;
        }
    }"
    @keydown.escape.window="modalOpen = false"
>
    {{-- Header Section --}}
    @include('owner.partials.calendar.header')

    {{-- Controls Bar: View Switcher, Date Navigator, Filters --}}
    @include('owner.partials.calendar.controls')

    {{-- Calendar Views --}}
    @include('owner.partials.calendar.week-view')
    @include('owner.partials.calendar.day-view')
    @include('owner.partials.calendar.month-view')

    {{-- Detail Modal Drawer --}}
    @include('owner.partials.calendar.detail-modal')
</div>

<script>
function updateCalendarFilter(key, val) {
    const url = new URL(window.location.href);
    url.searchParams.set(key, val);
    return url.toString();
}
</script>
@endsection
