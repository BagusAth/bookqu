{{--
    Month calendar (design A §6, §7.2): seven columns, a dot for days with sessions or full, today ringed.
    A panel on tablet and desktop (`panel`), the body of the calendar sheet on phone.
    Alpine contract, provided by the page script from server data:
      monthLabel, canGoPrev, canGoNext, prevMonth(), nextMonth(), a select method (`select`, default selectDate) and
      calendarDays: [{ key, date, label, ariaLabel, inMonth, isToday, isSelected, isDisabled, hasSessions, isFull }].
--}}
@props(['panel' => true, 'headingId' => 'kalender-bulan', 'select' => 'selectDate'])

<section aria-labelledby="{{ $headingId }}" {{ $attributes->class(['sf-card p-[18px]' => $panel]) }}>
    <div class="flex items-center justify-between gap-3">
        <h2 id="{{ $headingId }}" class="text-[17px] font-bold" x-text="monthLabel"></h2>
        <div class="flex gap-1.5">
            <button type="button" class="sf-btn-secondary sf-btn-icon" aria-label="Bulan sebelumnya" x-on:click="prevMonth()" x-bind:disabled="!canGoPrev">
                <x-customer.icon name="chevron-left" :size="18" :stroke="2" />
            </button>
            <button type="button" class="sf-btn-secondary sf-btn-icon" aria-label="Bulan berikutnya" x-on:click="nextMonth()" x-bind:disabled="!canGoNext">
                <x-customer.icon name="chevron-right" :size="18" :stroke="2" />
            </button>
        </div>
    </div>
    <div class="mt-3 grid grid-cols-7 text-center text-xs font-semibold text-sf-muted" aria-hidden="true">
        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $weekday)
            <span>{{ $weekday }}</span>
        @endforeach
    </div>
    <div class="mt-1.5 grid grid-cols-7 gap-y-0.5">
        <template x-for="day in calendarDays" :key="day.key">
            <span class="flex justify-center">
                <button type="button" class="sf-tile size-11 gap-0.5 p-0 text-sm font-semibold"
                    x-bind:class="{ 'invisible': !day.inMonth }"
                    x-bind:disabled="day.isDisabled || !day.inMonth"
                    x-bind:aria-pressed="day.isSelected.toString()"
                    x-bind:aria-label="day.ariaLabel"
                    x-bind:data-today="day.isToday ? '' : null"
                    x-on:click="{{ $select }}(day.date)">
                    <span class="sf-num" x-text="day.label"></span>
                    <span class="sf-dot" aria-hidden="true"
                        x-bind:class="day.hasSessions ? 'text-sf-primary-text' : (day.isFull ? 'text-sf-disabled-text' : 'invisible')"></span>
                </button>
            </span>
        </template>
    </div>
    <div class="mt-2.5 flex flex-wrap gap-3.5 text-xs text-sf-muted">
        <span class="inline-flex items-center gap-1.5"><span class="sf-dot text-sf-primary-text" aria-hidden="true"></span>Ada sesi</span>
        <span class="inline-flex items-center gap-1.5"><span class="sf-dot text-sf-disabled-text" aria-hidden="true"></span>Penuh atau tutup</span>
    </div>
    {{ $slot }}
</section>
