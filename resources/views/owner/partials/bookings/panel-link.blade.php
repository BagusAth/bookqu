<button type="button" class="bq-num rounded text-left font-bold bq-link-offset-2 hover:underline"
    @click.stop="$dispatch('open-booking-panel', { url: @js(route('owner.bookings.panel', $booking->id)), trigger: $el })">
    {{ $booking->booking_code ?? 'BKQ-' . $booking->id }}
</button>
