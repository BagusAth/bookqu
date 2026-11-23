{{-- BQ-162: one row per conflict, as in the prototype (#/unit): date and unit, both bookings, a link to the first. --}}
<div class="divide-y divide-bq-border">
    @foreach ($conflicts as $index => $conflict)
        @php $details = collect($conflict['booking_ids'])->map(fn ($id) => $bookingDetails[$id] ?? null)->filter()->values(); @endphp
        @if ($details->isNotEmpty())
            <div class="flex min-w-0 items-center gap-3 px-5 py-3">
                @if ($upcoming ?? false)<span class="bq-count">{{ $index + 1 }}</span>@endif
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold text-bq-ink">{{ $details->first()['date_label'] }} · {{ $conflict['unit_name'] }}</p>
                    <p class="truncate text-bq-small text-bq-text-muted" title="{{ $details->map(fn ($d) => $d['time'].' '.$d['customer'])->join(' dan ') }}">{{ $details->map(fn ($d) => $d['time'].' '.$d['customer'])->join(' dan ') }}</p>
                </div>
                <x-ui.button size="sm" x-data @click="$dispatch('open-booking-panel', { url: {{ \Illuminate\Support\Js::from($details->first()['url']) }}, trigger: $el })">Lihat booking</x-ui.button>
            </div>
        @endif
    @endforeach
</div>
