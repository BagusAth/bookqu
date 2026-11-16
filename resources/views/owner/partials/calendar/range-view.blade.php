<div class="grid items-start gap-2 {{ $view === 'month' ? 'grid-cols-1 sm:grid-cols-7' : 'grid-cols-1 lg:grid-cols-7' }}">
    @if ($view === 'month')
        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $weekday)
            <span class="hidden px-2 text-xs font-semibold text-bq-text-muted sm:block">{{ $weekday }}</span>
        @endforeach
        @for ($offset = 0; $offset < $monthOffset; $offset++)
            <span class="hidden sm:block" aria-hidden="true"></span>
        @endfor
    @endif
    @foreach ($days as $day)
        <section class="min-w-0 rounded-xl border border-bq-border bg-bq-surface p-2">
            @if ($view === 'month')
                <details>
                    <summary class="cursor-pointer break-words text-sm font-semibold">
                        <span class="sm:hidden">{{ $day['label'] }}</span><span class="hidden sm:inline">{{ $day['number'] }}</span>
                        <span class="block text-xs font-normal text-bq-text-muted">{{ count($day['slots']) }} slot</span>
                    </summary>
                    @include('owner.partials.calendar.day-entries')
                </details>
            @else
                <h2 class="text-sm font-semibold">{{ $day['label'] }}</h2>
                @include('owner.partials.calendar.day-entries')
            @endif
        </section>
    @endforeach
</div>
