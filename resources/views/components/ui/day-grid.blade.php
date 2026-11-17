@props(['columns', 'hours', 'nowOffset' => null, 'minutes'])

<div {{ $attributes->class('bq-panel overflow-x-auto') }} role="region" aria-label="Jadwal menurut jam" tabindex="0">
    <div class="bq-day-grid" style="--bq-calendar-columns: {{ count($columns) }}; --bq-calendar-minutes: {{ $minutes }}">
        <span aria-hidden="true" class="border-b border-bq-border"></span>
        @foreach ($columns as $column)
            <div class="min-w-0 border-b border-l border-bq-border px-3 py-2">
                <p class="truncate text-bq-label" title="{{ $column['name'] }}">{{ $column['name'] }}</p>
            </div>
        @endforeach
        <div class="bq-day-grid-track relative" aria-hidden="true">
            @foreach ($hours as $hour)
                <span class="absolute inset-x-0 px-2 text-center font-bq-display text-bq-clock font-semibold text-bq-text-muted" style="top: calc({{ $hour['offset'] }} * var(--bq-calendar-minute))">{{ $hour['label'] }}</span>
            @endforeach
        </div>
        @foreach ($columns as $column)
            <section aria-label="{{ $column['name'] }}" class="bq-day-grid-track relative border-l border-bq-border">
                @if ($nowOffset !== null)
                    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 z-10 border-t-2 border-bq-accent" style="top: calc({{ $nowOffset }} * var(--bq-calendar-minute))"></div>
                @endif
                @foreach ($column['slots'] as $slot)
                    <div class="bq-day-grid-slot" style="--bq-slot-top: {{ $slot['offset'] }}; --bq-slot-length: {{ $slot['length'] }}; --bq-slot-lane: {{ $slot['lane'] }}; --bq-slot-lanes: {{ $column['lanes'] }}">
                        @include('owner.partials.calendar.grid-slot')
                    </div>
                @endforeach
            </section>
        @endforeach
    </div>
</div>
