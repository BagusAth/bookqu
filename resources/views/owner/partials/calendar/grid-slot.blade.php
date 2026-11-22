{{-- BQ-162: day-grid block as in the prototype (#/kalender/hari): booked, waiting, past, free or closed. --}}
@php
    $booking = $slot['bookings'][0] ?? null;
    $start = str_replace(':', '.', $slot['start']);
    $tone = match (true) {
        $slot['closed'] => 'bq-block-closed',
        $booking !== null && $booking['status'] === 'pending' => 'bq-block-pending',
        $booking !== null && $slot['state'] === 'Terlewat' => 'bq-block-past',
        $booking !== null => '',
        $slot['walkIn'] || $slot['edit'] => 'bq-block-free',
        default => 'bq-block-past',
    };
    $detail = $slot['capacity'] !== null ? $slot['capacity'].' peserta' : ($booking ? $slot['service'] : null);
    $title = $start.' · '.($booking ? $booking['customer'] : $slot['state']).' · '.$slot['service'];
@endphp
@if ($booking && count($slot['bookings']) === 1)
    <button type="button" class="bq-block {{ $tone }}" title="{{ $title }}"
        data-booking-panel-url="{{ $booking['url'] }}"
        x-on:click="$dispatch('open-booking-panel', { url: $el.dataset.bookingPanelUrl, trigger: $el })">
        <span class="bq-block-top"><time>{{ $start }}</time><b>{{ $booking['customer'] }}</b></span>
        @if ($detail)<span>{{ $booking['status'] === 'pending' ? 'Menunggu bayar' : $detail }}</span>@endif
    </button>
@elseif ($slot['walkIn'] || $slot['edit'] || $booking)
    <button type="button" class="bq-block {{ $tone }}" title="{{ $title }}"
        data-calendar-slot-id="{{ $slot['id'] }}" data-calendar-slot="{{ \Illuminate\Support\Js::encode($slot) }}"
        x-on:click="calendarTrigger = $el; slot = JSON.parse($el.dataset.calendarSlot); slotErrors = {}; feedback = ''; $dispatch('open-dialog', { id: 'calendar-slot', trigger: $el })">
        @if ($booking)
            <span class="bq-block-top"><time>{{ $start }}</time><b>{{ count($slot['bookings']) }} booking</b></span>
            @if ($detail)<span>{{ $detail }}</span>@endif
        @else
            <span class="bq-block-top"><time>{{ $start }}</time><b>Kosong</b></span>
            <span>{{ $slot['walkIn'] ? '+ Catat' : 'Kelola slot' }}{{ $slot['capacity'] !== null ? ' · '.$slot['capacity'].' peserta' : '' }}{{ $slot['price'] ? ' · '.$slot['price'] : '' }}</span>
        @endif
    </button>
@else
    <span class="bq-block {{ $tone }}" title="{{ $title }}">
        @if ($slot['closed'])
            <span class="text-bq-text-muted">Ditutup</span>
        @else
            <span class="bq-block-top"><time>{{ $start }}</time><b>{{ $slot['state'] === 'Terlewat' ? 'Lewat' : $slot['state'] }}</b></span>
        @endif
    </span>
@endif
