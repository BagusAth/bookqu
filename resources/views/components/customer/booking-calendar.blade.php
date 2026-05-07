<div class="booking-calendar rounded-2xl border border-[#E5E7EB] bg-white p-6 booking-shadow">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#6B7280]">Select a date</p>
            <h3 class="mt-2 text-lg font-semibold text-[#111827]" x-text="currentMonthLabel">Month</h3>
        </div>
        <div class="flex items-center gap-2">
            <button
                type="button"
                class="booking-calendar__nav-btn"
                @click="prevMonth"
                :disabled="!canGoPrev"
                aria-label="Previous month"
            >
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button
                type="button"
                class="booking-calendar__nav-btn"
                @click="nextMonth"
                :disabled="!canGoNext"
                aria-label="Next month"
            >
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>

    <div class="mt-5 grid grid-cols-7 gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-[#6B7280]">
        <span class="text-center">Mon</span>
        <span class="text-center">Tue</span>
        <span class="text-center">Wed</span>
        <span class="text-center">Thu</span>
        <span class="text-center">Fri</span>
        <span class="text-center">Sat</span>
        <span class="text-center">Sun</span>
    </div>

    <div class="booking-calendar__grid mt-3">
        <template x-for="day in calendarDays" :key="day.key">
            <button
                type="button"
                class="booking-calendar__cell"
                :class="{
                    'booking-calendar__cell--inactive': !day.isCurrentMonth,
                    'booking-calendar__cell--available': day.isAvailable,
                    'booking-calendar__cell--selected': day.isSelected,
                    'booking-calendar__cell--disabled': day.isDisabled && day.isCurrentMonth,
                    'booking-calendar__cell--today': day.isToday,
                    'booking-calendar__cell--full': day.isFull
                }"
                :disabled="day.isDisabled"
                @click="selectDate(day.date)"
            >
                <span class="booking-calendar__date text-sm font-semibold" x-text="day.label"></span>
                <span
                    class="booking-calendar__slots"
                    x-show="day.showSlots"
                    x-text="slotLabel(day.date)"
                    x-cloak
                ></span>
            </button>
        </template>
    </div>
</div>
