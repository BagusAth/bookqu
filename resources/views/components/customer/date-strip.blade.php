@props(['dates', 'currentDate' => null, 'label' => 'Pilih tanggal'])

<div role="group" aria-label="{{ $label }}" x-ref="dateStrip"
    {{ $attributes->class('flex max-w-full gap-2 overflow-x-auto p-1') }}>
    @foreach ($dates as $day)
        <button type="button" @disabled(!$day['is_available'])
            x-on:click="selectDate(@js($day['date']))"
            x-bind:aria-pressed="selectedDate === @js($day['date'])"
            x-bind:class="{ 'bg-bq-primary-light border-bq-primary': selectedDate === @js($day['date']) }"
            @if ($currentDate === $day['date']) aria-current="date" @endif
            class="bq-btn bq-btn-secondary min-h-11 shrink-0 flex-col gap-0">
            {{ $day['label'] }}
            @if ($currentDate === $day['date'])
                <span class="text-bq-phone-nav font-medium">Jadwal saat ini</span>
            @endif
        </button>
    @endforeach
</div>
