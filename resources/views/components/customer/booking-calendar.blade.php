<div class="booking-calendar rounded-2xl border border-[#E2E8F0] bg-white p-5 sm:p-6 shadow-xs">
    {{-- Month Navigator --}}
    <div class="flex items-center justify-between pb-4 border-b border-[#F1F5F9]">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Pilih Tanggal</p>
            <h3 class="mt-1 text-lg sm:text-xl font-bold text-[#0F172A]" x-text="currentMonthLabel">Bulan</h3>
        </div>
        <div class="flex items-center gap-1.5 sm:gap-2">
            <button
                type="button"
                class="flex h-9 w-9 items-center justify-center rounded-xl border border-[#E2E8F0] bg-white text-[#475569] shadow-2xs transition hover:border-[#CBD5E1] hover:bg-[#F8FAFC] disabled:opacity-30 disabled:pointer-events-none"
                @click="prevMonth"
                :disabled="!canGoPrev"
                aria-label="Bulan sebelumnya"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button
                type="button"
                class="flex h-9 w-9 items-center justify-center rounded-xl border border-[#E2E8F0] bg-white text-[#475569] shadow-2xs transition hover:border-[#CBD5E1] hover:bg-[#F8FAFC] disabled:opacity-30 disabled:pointer-events-none"
                @click="nextMonth"
                :disabled="!canGoNext"
                aria-label="Bulan berikutnya"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Indonesian Day Headers --}}
    <div class="mt-4 grid grid-cols-7 gap-1 sm:gap-2 text-center text-[11px] sm:text-xs font-bold uppercase tracking-wider text-[#64748B]">
        <span>Sen</span>
        <span>Sel</span>
        <span>Rab</span>
        <span>Kam</span>
        <span>Jum</span>
        <span class="text-[#4F46E5]">Sab</span>
        <span class="text-[#EF4444]">Min</span>
    </div>

    {{-- Calendar Days Grid --}}
    <div class="booking-calendar__grid mt-2 grid grid-cols-7 gap-1 sm:gap-2">
        <template x-for="day in calendarDays" :key="day.key">
            <button
                type="button"
                class="booking-calendar__cell min-h-[56px] sm:min-h-[64px] rounded-xl flex flex-col items-center justify-center transition-all relative select-none"
                :class="{
                    'opacity-25 pointer-events-none': !day.isCurrentMonth,
                    'booking-calendar__cell--available hover:bg-[#EEF2FF] hover:border-[#C7D2FE] cursor-pointer': day.isAvailable && !day.isSelected,
                    'booking-calendar__cell--selected bg-[#4F46E5] text-white shadow-md font-bold': day.isSelected,
                    'booking-calendar__cell--disabled opacity-35 cursor-not-allowed': day.isDisabled && day.isCurrentMonth,
                    'border border-[#4F46E5] font-bold': day.isToday && !day.isSelected,
                    'bg-red-50 text-red-600 cursor-not-allowed': day.isFull && day.isCurrentMonth
                }"
                :disabled="day.isDisabled"
                :aria-selected="day.isSelected"
                @click="selectDate(day.date)"
            >
                <span class="text-xs sm:text-sm font-semibold" :class="day.isSelected ? 'text-white' : 'text-[#0F172A]'" x-text="day.label"></span>
                
                <template x-if="day.showSlots">
                    <span
                        class="mt-1 text-[9px] sm:text-[10px] px-1.5 py-0.5 rounded-full font-medium truncate max-w-full"
                        :class="{
                            'text-white/90': day.isSelected,
                            'bg-emerald-100 text-emerald-700': day.isAvailable && !day.isSelected,
                            'bg-red-100 text-red-600 font-bold': day.isFull && !day.isSelected
                        }"
                        x-text="slotLabel(day.date)"
                    ></span>
                </template>
            </button>
        </template>
    </div>

    {{-- Legend --}}
    <div class="mt-5 pt-4 border-t border-[#F1F5F9] flex flex-wrap items-center justify-between gap-3 text-xs text-[#64748B]">
        <div class="flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
            <span>Tersedia</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>
            <span>Penuh</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-full bg-[#4F46E5]"></span>
            <span>Dipilih</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-full bg-[#E2E8F0]"></span>
            <span>Tidak Tersedia</span>
        </div>
    </div>
</div>
