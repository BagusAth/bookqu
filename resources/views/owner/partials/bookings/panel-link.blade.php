<button type="button" class="font-semibold text-bq-primary underline"
    @click="$dispatch('open-booking-panel', { url: @js(route('owner.bookings.panel', $booking->id)), trigger: $el })">
    {{ $booking->booking_code ?? 'BKQ-' . $booking->id }}
</button>
