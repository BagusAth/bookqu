<button type="button" class="block w-full break-words rounded-lg border border-bq-border bg-bq-surface p-2 text-left text-xs"
    @click="$dispatch('open-booking-panel', { url: @js($booking['url']), trigger: $el })">
    <span class="block font-semibold">{{ $booking['customer'] }}</span>
    <span class="block text-bq-text-muted">{{ $booking['time'] }} · {{ $booking['service'] }}</span>
    <x-owner.status-badge type="booking" :status="$booking['status']" />
</button>
