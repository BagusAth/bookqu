<article class="min-w-0 space-y-2 rounded-lg border border-bq-border p-2.5 text-xs {{ $slotColors[$slot['state']] ?? 'bg-bq-surface-2' }}">
    <div class="break-words">
        <p class="text-sm font-semibold text-bq-ink">{{ $slot['time'] }} · {{ $slot['service'] }}</p>
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
        <button type="button" class="rounded text-left bq-link"
            data-calendar-slot-id="{{ $slot['id'] }}" data-calendar-slot="{{ \Illuminate\Support\Js::encode($slot) }}"
            @if ($errors->any() && in_array(old('_form'), ['calendar-state-'.$slot['id'], 'calendar-price-'.$slot['id']], true))
                x-init="$nextTick(() => $el.click())"
            @endif
            @click="calendarTrigger = $el; slot = JSON.parse($el.dataset.calendarSlot); slotErrors = {}; feedback = ''; $dispatch('open-dialog', { id: 'calendar-slot', trigger: $el })">{{ $slot['walkIn'] && $slot['bookings'] === [] ? '+ Catat' : 'Kelola slot' }}</button>
    @endif
</article>
