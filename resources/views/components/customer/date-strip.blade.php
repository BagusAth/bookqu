{{--
    Scrollable date strip with snap (design A §6, §7.2). Dates are prepared by the server:
    ['date' => 'Y-m-d', 'label' => 'Sen, 23 Nov', 'is_available' => bool]. Selection is Alpine state
    (`selectedDate`, `selectDate()`); availability is never decided here.
    `heading` and `calendarSheet` (a sheet id) add the month title and the "Kalender" button above the strip.
--}}
@props(['dates', 'currentDate' => null, 'label' => 'Pilih tanggal', 'heading' => null, 'calendarSheet' => null])

@if ($heading || $calendarSheet)
    <div class="flex items-center justify-between gap-3 pb-2.5">
        @if ($heading)
            <h2 class="text-[17px] font-bold">{{ $heading }}</h2>
        @endif
        @if ($calendarSheet)
            <button type="button" class="sf-btn-secondary px-3 text-[13px]"
                x-on:click="$dispatch('open-dialog', { id: @js($calendarSheet), trigger: $el })">
                <x-customer.icon name="calendar" :size="16" :stroke="2" />
                Kalender
            </button>
        @endif
    </div>
@endif

<div role="group" aria-label="{{ $label }}" x-ref="dateStrip" {{ $attributes->class('sf-scroll-row max-w-full gap-2 p-1') }}>
    @foreach ($dates as $day)
        @php
            $date = \Illuminate\Support\Carbon::parse($day['date'])->locale('id');
            $isCurrent = $currentDate === $day['date'];
        @endphp
        <button type="button" @disabled(! $day['is_available'])
            x-on:click="selectDate(@js($day['date']))"
            aria-pressed="false"
            x-bind:aria-pressed="(selectedDate === @js($day['date'])).toString()"
            aria-label="{{ $day['label'] ?? $date->translatedFormat('l, d F') }}{{ $day['is_available'] ? '' : ', tidak ada sesi' }}"
            @if ($isCurrent) aria-current="date" @endif
            class="sf-tile min-h-[76px] min-w-[60px] shrink-0 gap-0.5 px-2">
            <span class="text-xs font-semibold">{{ $date->translatedFormat('D') }}</span>
            <span class="sf-num text-xl leading-6">{{ $date->format('j') }}</span>
            @if ($isCurrent)
                <span class="sf-tile-note">Jadwal saat ini</span>
            @else
                <span @class(['sf-dot', 'text-sf-primary-text' => $day['is_available'], 'text-sf-line' => ! $day['is_available']]) aria-hidden="true"></span>
            @endif
        </button>
    @endforeach
</div>
