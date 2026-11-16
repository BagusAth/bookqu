<div class="grid items-start gap-2 {{ $view === 'month' ? 'grid-cols-1 sm:grid-cols-7' : 'grid-cols-1 lg:grid-cols-7' }}">
    @if ($view === 'month')
        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $weekday)
            <span class="hidden px-2 text-xs font-semibold text-bq-text-muted sm:block">{{ $weekday }}</span>
        @endforeach
        @for ($offset = 0; $offset < $monthOffset; $offset++)
            <span class="hidden sm:block" aria-hidden="true"></span>
        @endfor
    @endif
    @foreach ($days as $dayKey => $day)
        <section @class(['min-w-0 rounded-xl border bg-bq-surface p-2.5', 'border-bq-primary ring-1 ring-bq-primary' => (string) $dayKey === (string) $today, 'border-bq-border' => (string) $dayKey !== (string) $today])>
            @if ($view === 'month')
                <details>
                    <summary class="cursor-pointer break-words text-sm font-semibold">
                        <span class="sm:hidden">{{ $day['label'] }}</span><span class="hidden sm:inline">{{ $day['number'] }}</span>
                        <span class="mt-1 block text-xs font-normal text-bq-text-muted"><span class="bq-num">{{ count($day['slots']) }}</span> slot · <span class="bq-num font-semibold text-bq-primary">{{ $day['bookingCount'] ?? 0 }}</span> booking</span>
                    </summary>
                    @include('owner.partials.calendar.day-entries')
                </details>
            @else
                <h2 class="text-sm font-bold text-bq-ink">{{ $day['label'] }}</h2>
                @include('owner.partials.calendar.day-entries')
            @endif
        </section>
    @endforeach
</div>
