<article class="min-w-0 space-y-2 rounded-lg border border-bq-border bg-bq-background p-2 text-xs">
    <div class="break-words">
        <p class="font-semibold">{{ $slot['time'] }} · {{ $slot['service'] }}</p>
        <p class="mt-1 text-bq-text-muted">
            {{ $slot['state'] }}
            @if ($slot['capacity'] !== null) · {{ $slot['capacity'] }} peserta @endif
            @if ($slot['price'] !== null) · {{ $slot['price'] }} @endif
        </p>
    </div>
    @foreach ($slot['bookings'] as $booking)
        @include('owner.partials.calendar.booking')
    @endforeach
    @if ($slot['walkIn'] || $slot['edit'])
        <button type="button" class="rounded text-left font-semibold text-bq-primary underline"
            @click="slot = @js($slot); feedback = ''; $dispatch('open-dialog', { id: 'calendar-slot', trigger: $el })">Kelola slot</button>
    @endif
</article>
