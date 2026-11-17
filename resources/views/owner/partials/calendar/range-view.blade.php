@if ($view === 'month')
    <section class="bq-panel p-3 sm:p-4" aria-label="Kalender bulan ini">
        <p class="mb-3 text-bq-small text-bq-text-muted">Batang menunjukkan slot terisi per tanggal. Klik tanggal untuk melihat harinya.</p>
        <div class="grid grid-cols-7 gap-1 text-center md:gap-2">
            @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $weekday)
                <span class="text-bq-small font-medium text-bq-text-muted">{{ $weekday }}</span>
            @endforeach
            @for ($offset = 0; $offset < $monthOffset; $offset++)
                <span aria-hidden="true"></span>
            @endfor
            @foreach ($days as $dayKey => $day)
                <a href="{{ route('owner.calendar', ['view' => 'day', 'date' => $dayKey] + $filters) }}"
                    title="{{ $day['label'] }} · {{ $day['occupied'] }}/{{ count($day['slots']) }} slot terisi{{ $day['closed'] ? ' · '.$day['closed'] : '' }}"
                    @if ($dayKey === $today) aria-current="date" @endif
                    @class(['grid min-w-0 content-between gap-1 rounded-bq-control border p-1.5 text-bq-body hover:bg-bq-primary-light md:p-2', 'border-bq-primary bg-bq-primary-light font-semibold' => $dayKey === $today, 'border-bq-border' => $dayKey !== $today])>
                    <span>{{ $day['number'] }}</span>
                    <span class="sr-only">{{ $day['label'] }}, {{ $day['occupied'] }} dari {{ count($day['slots']) }} slot terisi</span>
                    <span aria-hidden="true" class="h-1.5 overflow-hidden rounded-full bg-bq-surface-2">
                        <span class="block h-full rounded-full bg-bq-primary" style="width: {{ $day['occupancy'] }}%"></span>
                    </span>
                    @if ($day['closed'])<span class="truncate text-bq-small text-bq-warning" title="{{ $day['closed'] }}">Tutup</span>@endif
                </a>
            @endforeach
        </div>
    </section>
@else
    <div class="grid items-start gap-2 lg:grid-cols-7">
        @foreach ($days as $dayKey => $day)
            <section class="bq-panel min-w-0">
                <h2 class="border-b border-bq-border px-3 py-2 text-bq-label">
                    <a class="bq-link" href="{{ route('owner.calendar', ['view' => 'day', 'date' => $dayKey] + $filters) }}">{{ $day['label'] }}</a>
                </h2>
                @if ($day['closed'])
                    <p class="px-3 py-2 text-bq-small text-bq-warning">{{ $day['closed'] }}</p>
                @endif
                @if ($day['weekBookings'] === [])
                    <p class="px-3 py-2 text-bq-small text-bq-text-muted">Belum ada booking.</p>
                @else
                    <ul class="divide-y divide-bq-border">
                        @foreach ($day['weekBookings'] as $booking)
                            <li>
                                <button type="button" class="grid w-full min-w-0 gap-0.5 px-3 py-2 text-left hover:bg-bq-primary-light"
                                    data-booking-panel-url="{{ $booking['url'] }}"
                                    x-on:click="$dispatch('open-booking-panel', { url: $el.dataset.bookingPanelUrl, trigger: $el })">
                                    <span class="bq-num font-bq-display text-bq-clock font-semibold">{{ $booking['time'] }}</span>
                                    <span class="truncate font-semibold" title="{{ $booking['customer'] }}">{{ $booking['customer'] }}</span>
                                    @php [$statusLabel, $tone] = \App\Support\StatusBadge::for('booking', $booking['status']); @endphp
                                    <x-ui.status-dot :tone="$tone === 'info' ? 'brand' : $tone">{{ $statusLabel }}</x-ui.status-dot>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>
@endif
