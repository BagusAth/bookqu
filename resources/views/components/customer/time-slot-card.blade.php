@props(['slotVar' => 'slot'])

<button
    type="button"
    class="booking-time-slot relative flex flex-col items-center justify-center p-3 sm:p-4 rounded-xl border transition-all text-center"
    :class="{
        'border-[#E2E8F0] bg-white hover:border-[#4F46E5] hover:bg-[#EEF2FF]/40 cursor-pointer shadow-2xs': {{ $slotVar }}.isAvailable && !{{ $slotVar }}.isSelected,
        'border-[#4F46E5] bg-[#4F46E5] text-white shadow-md shadow-[#4F46E5]/25': {{ $slotVar }}.isSelected,
        'border-[#E2E8F0] bg-[#F8FAFC] opacity-60 cursor-not-allowed': {{ $slotVar }}.isDisabled
    }"
    :disabled="{{ $slotVar }}.isDisabled"
    :aria-selected="{{ $slotVar }}.isSelected"
    @click="selectSlot({{ $slotVar }})"
>
    {{-- Active Checkmark --}}
    <template x-if="{{ $slotVar }}.isSelected">
        <span class="absolute top-2 right-2 flex h-4 w-4 items-center justify-center rounded-full bg-white text-[#4F46E5]">
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </span>
    </template>

    {{-- Time Value --}}
    <span class="text-base sm:text-lg font-bold" :class="{{ $slotVar }}.isSelected ? 'text-white' : 'text-[#0F172A]'" x-text="{{ $slotVar }}.label"></span>
    <span class="text-[11px] font-medium" :class="{{ $slotVar }}.isSelected ? 'text-white/80' : 'text-[#64748B]'">WIB</span>

    {{-- Status Badge --}}
    <span
        class="mt-1.5 inline-block text-[10px] font-semibold px-2 py-0.5 rounded-full"
        :class="{
            'bg-white/20 text-white': {{ $slotVar }}.isSelected,
            'bg-emerald-50 text-emerald-700 border border-emerald-200': {{ $slotVar }}.isAvailable && !{{ $slotVar }}.isSelected,
            'bg-red-50 text-red-600 border border-red-200': {{ $slotVar }}.isBooked,
            'bg-slate-100 text-slate-500 border border-slate-200': {{ $slotVar }}.isPast
        }"
        x-text="{{ $slotVar }}.statusBadge || ({{ $slotVar }}.isAvailable ? 'Tersedia' : 'Tidak Tersedia')"
    ></span>
</button>
