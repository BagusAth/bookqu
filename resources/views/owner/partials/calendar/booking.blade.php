<button type="button" class="block w-full space-y-1 break-words rounded-lg border border-bq-border bg-bq-surface p-2.5 text-left text-xs transition-colors hover:border-bq-primary"
    @click="$dispatch('open-booking-panel', { url: @js($booking['url']), trigger: $el })">
    <span class="block text-sm font-semibold text-bq-ink">{{ $booking['customer'] }}</span>
    <span class="block text-bq-text-muted">{{ $booking['time'] }} · {{ $booking['service'] }}</span>
    <x-owner.status-badge type="booking" :status="$booking['status']" />
</button>
