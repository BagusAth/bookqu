@php
    $byTime = collect($day['slots'])->groupBy('start');
    $showNow = $date->toDateString() === $today;
@endphp

<div class="space-y-3">
    @if ($day['closed'])
        <p class="rounded-lg bg-bq-warning-bg p-3 text-sm text-bq-warning">{{ $day['closed'] }}</p>
    @endif

    <div class="space-y-3 lg:hidden">
        @php $nowShown = false; @endphp
        @foreach ($byTime as $time => $slots)
            @if ($showNow && ! $nowShown && $time >= $todayTime)
                @include('owner.partials.calendar.now-line')
                @php $nowShown = true; @endphp
            @endif
            <section aria-label="Slot pukul {{ $time }}">
                <h2 class="bq-num mb-2 text-sm font-bold text-bq-ink">{{ $time }}</h2>
                <div class="space-y-2 border-l-2 border-bq-border pl-3">
                    @foreach ($slots as $slot)
                        @include('owner.partials.calendar.slot')
                    @endforeach
                </div>
            </section>
        @endforeach
        @if ($showNow && ! $nowShown) @include('owner.partials.calendar.now-line') @endif
    </div>

    @if ($gridServices->isNotEmpty())
        <div class="bq-panel hidden overflow-x-auto lg:block">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr>
                        <th scope="col" class="sticky left-0 bg-bq-surface-2 p-3 text-left text-xs font-semibold text-bq-text-muted">Jam</th>
                        @foreach ($gridServices as $service)
                            <th scope="col" class="min-w-44 bg-bq-surface-2 p-3 text-left text-xs font-semibold text-bq-ink">{{ $service->namalayanan }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php $nowShown = false; @endphp
                    @foreach ($byTime as $time => $slots)
                        @if ($showNow && ! $nowShown && $time >= $todayTime)
                            <tr><td colspan="{{ $gridServices->count() + 1 }}" class="p-2">@include('owner.partials.calendar.now-line')</td></tr>
                            @php $nowShown = true; @endphp
                        @endif
                        @php $byService = $slots->groupBy('service_id'); @endphp
                        <tr class="border-t border-bq-border">
                            <th scope="row" class="bq-num sticky left-0 bg-bq-surface p-3 text-left align-top font-bold text-bq-ink">{{ $time }}</th>
                            @foreach ($gridServices as $service)
                                <td class="space-y-2 p-2 align-top">
                                    @foreach ($byService->get($service->id, []) as $slot)
                                        @include('owner.partials.calendar.slot')
                                    @endforeach
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    @if ($showNow && ! $nowShown)
                        <tr><td colspan="{{ $gridServices->count() + 1 }}" class="p-2">@include('owner.partials.calendar.now-line')</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    @elseif ($showNow)
        <div class="hidden lg:block">@include('owner.partials.calendar.now-line')</div>
    @endif

    @foreach ($day['bookings'] as $booking)
        @include('owner.partials.calendar.booking')
    @endforeach
</div>
