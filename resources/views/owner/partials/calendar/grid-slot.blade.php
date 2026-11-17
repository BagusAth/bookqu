@php
    $booking = $slot['bookings'][0] ?? null;
    $label = $booking ? $booking['customer'] : ($slot['walkIn'] ? 'Kosong' : $slot['state']);
    $title = $slot['time'].' · '.$label;
    $classes = 'block h-full w-full truncate rounded-bq-control border border-bq-border px-2 text-left text-bq-small font-medium';
@endphp
@if ($booking && count($slot['bookings']) === 1)
    <button type="button" class="{{ $classes }} bg-bq-primary-light" title="{{ $title }}"
        data-booking-panel-url="{{ $booking['url'] }}"
        x-on:click="$dispatch('open-booking-panel', { url: $el.dataset.bookingPanelUrl, trigger: $el })">
        <span class="bq-num font-bq-display text-bq-clock font-semibold">{{ $slot['start'] }}</span> {{ $label }}
    </button>
@elseif ($slot['walkIn'] || $slot['edit'] || $booking)
    <button type="button" class="{{ $classes }} {{ $booking ? 'bg-bq-primary-light' : 'border-dashed bg-bq-surface' }}" title="{{ $title }}"
        data-calendar-slot-id="{{ $slot['id'] }}" data-calendar-slot="{{ \Illuminate\Support\Js::encode($slot) }}"
        x-on:click="calendarTrigger = $el; slot = JSON.parse($el.dataset.calendarSlot); slotErrors = {}; feedback = ''; $dispatch('open-dialog', { id: 'calendar-slot', trigger: $el })">
        <span class="bq-num font-bq-display text-bq-clock font-semibold">{{ $slot['start'] }}</span>
        {{ $booking ? count($slot['bookings']).' booking' : ($slot['walkIn'] ? '+ Catat' : $label) }}
    </button>
@else
    <span class="{{ $classes }} bg-bq-background text-bq-text-muted" title="{{ $title }}">{{ $slot['start'] }} · {{ $label }}</span>
@endif
