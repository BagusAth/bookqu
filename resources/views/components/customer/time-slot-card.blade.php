@props(['slotVar' => 'slot'])

<button
    type="button"
    class="booking-time-slot"
    :class="{
        'booking-time-slot--available': {{ $slotVar }}.isAvailable,
        'booking-time-slot--selected': {{ $slotVar }}.isSelected,
        'booking-time-slot--disabled': {{ $slotVar }}.isDisabled
    }"
    :disabled="{{ $slotVar }}.isDisabled"
    @click="selectSlot({{ $slotVar }})"
>
    <span class="text-base font-semibold" x-text="{{ $slotVar }}.label"></span>
    <span class="booking-time-slot__period" x-text="{{ $slotVar }}.period"></span>
</button>
