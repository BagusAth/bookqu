@php
    $byTime = collect($day['slots'])->groupBy('start');
    $showNow = $date->toDateString() === $today;
@endphp

<div class="space-y-3">
    @if ($day['closed'])
        <p class="rounded-lg bg-bq-warning-bg p-3 text-sm text-bq-warning">{{ $day['closed'] }}</p>
    @endif

    <x-ui.day-rail class="space-y-3 md:hidden" label="Slot menurut jam">
        @php $nowShown = false; @endphp
        @foreach ($byTime as $time => $slots)
            @if ($showNow && ! $nowShown && $time >= $todayTime)
                <li>@include('owner.partials.calendar.now-line')</li>
                @php $nowShown = true; @endphp
            @endif
            <li>
            <section aria-label="Slot pukul {{ $time }}">
                <h2 class="bq-num mb-2 text-sm font-bold text-bq-ink">{{ $time }}</h2>
                <div class="space-y-2 border-l-2 border-bq-border pl-3">
                    @foreach ($slots as $slot)
                        @include('owner.partials.calendar.slot')
                    @endforeach
                </div>
            </section>
            </li>
        @endforeach
        @if ($showNow && ! $nowShown)<li>@include('owner.partials.calendar.now-line')</li>@endif
    </x-ui.day-rail>

    @if ($dayGrid['columns'] !== [])
        <div class="hidden space-y-3 md:block">
            <x-ui.button size="sm" icon="filter" x-data x-on:click="$dispatch('open-dialog', { id: 'calendar-service-columns', trigger: $el })">
                {{ $unitMode ? 'Unit' : 'Layanan' }} {{ count($dayGrid['columns']) }} dari {{ count($dayGrid['options']) }}
            </x-ui.button>
            <x-ui.day-grid :columns="$dayGrid['columns']" :hours="$dayGrid['hours']" :minutes="$dayGrid['minutes']" :now-offset="$dayGrid['nowOffset']" />
            <x-ui.sheet id="calendar-service-columns" :title="$unitMode ? 'Kolom unit' : 'Kolom layanan'">
                <form method="GET" action="{{ route('owner.calendar') }}" class="space-y-3"
                    x-data="{ chosen: @js(array_map('strval', $dayGrid['selected'])) }">
                    <input type="hidden" name="view" value="day">
                    <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                    @foreach ($filters as $field => $value)
                        <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                    @endforeach
                    <p class="text-bq-text-muted">Paling banyak 6 kolom supaya nama tetap terbaca.</p>
                    <div class="grid gap-1">
                        @foreach ($dayGrid['options'] as $column)
                            <x-ui.checkbox name="columns[]" value="{{ $column['id'] }}" label="{{ $column['name'] }}" x-model="chosen"
                                x-bind:disabled="chosen.length >= 6 && !chosen.includes($el.value)" />
                        @endforeach
                    </div>
                    <div class="flex justify-end gap-2">
                        <x-ui.button x-on:click="hide()">Batal</x-ui.button>
                        <x-ui.button type="submit" variant="primary">Tampilkan</x-ui.button>
                    </div>
                </form>
            </x-ui.sheet>
        </div>
    @elseif ($unitMode)
        <x-ui.empty title="Belum ada slot untuk unit" class="hidden md:grid" />
    @endif

    @foreach ($day['bookings'] as $booking)
        @include('owner.partials.calendar.booking')
    @endforeach
</div>
