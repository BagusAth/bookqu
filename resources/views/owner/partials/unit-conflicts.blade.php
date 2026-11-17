<ul class="divide-y divide-bq-border text-bq-small">
    @foreach ($conflicts as $conflict)
        <li class="py-2">
            <p class="font-semibold">{{ $conflict['unit_name'] }}</p>
            @foreach ($conflict['booking_ids'] as $bookingId)
                @if ($detail = $bookingDetails[$bookingId] ?? null)
                    <p>
                        <button type="button" class="bq-link" x-data title="{{ $detail['code'] }}"
                            @click="$dispatch('open-booking-panel', { url: @js($detail['url']), trigger: $el })">{{ $detail['customer'] }}</button>
                        · {{ $detail['session'] }}
                    </p>
                @endif
            @endforeach
        </li>
    @endforeach
</ul>
