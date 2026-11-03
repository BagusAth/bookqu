@extends('customer.layouts.booking-shell')

@section('title', 'Pilih Jadwal')
@section('current_step', 2)
@section('back_url', \App\Support\CustomerBookingRoutes::url('customer.booking.program', $tenant->slug))
@section('back_label', 'Kembali ke Layanan')

@section('content')
@php
    $scheduleConfig = [
        'tenantSlug' => $tenant->slug,
        'slotsUrl' => \App\Support\CustomerBookingRoutes::url('customer.booking.schedule-slots', $tenant->slug),
        'initialDate' => $selectedDate,
        'initialDateLabel' => $selectedDateLabel,
        'dates' => $dates,
        'service' => $servicePayload,
        'initialMorningSlots' => $morningSlots,
        'initialAfternoonSlots' => $afternoonSlots,
        'initialEveningSlots' => $eveningSlots,
        'selectedTimes' => $selectedTimes,
        'selectedScheduleIds' => $selectedScheduleIds,
    ];
@endphp
<div id="booking-schedule-root" x-data="schedulePicker(@js($scheduleConfig))" class="w-full">

    {{-- Error Notice --}}
    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-xs sm:text-sm text-red-700 shadow-2xs">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="font-bold">{{ $errors->first() }}</span>
            </div>
        </div>
    @endif

    <p x-show="errorMessage" x-cloak role="alert" aria-live="polite" class="mb-4 rounded-xl bg-red-50 p-3 text-sm text-red-700" x-text="errorMessage"></p>

    {{-- Canonical Spec Anchors & Screen Reader Headings --}}
    <div class="sr-only">
        <h1>Pilih Jadwal</h1>
        <h2>Pilih Tanggal</h2>
        <p>Pilih tanggal yang tersedia untuk reservasi Anda.</p>
        <h2>Pilih Waktu</h2>
        <a href="{{ \App\Support\CustomerBookingRoutes::url('customer.booking.program', $tenant->slug) }}">Kembali ke Pilih Layanan</a>
    </div>

    {{-- Unified Booking Form --}}
    <form id="booking-schedule-form"
          method="POST"
          action="{{ \App\Support\CustomerBookingRoutes::url('customer.booking.select-schedule', $tenant->slug) }}"
          @submit="submit($event)">
        @csrf
        <input type="hidden" name="tanggal" :value="selectedDate" value="{{ $selectedDate }}" />
        <div class="hidden">
            <template x-for="slot in sortedSelectedSlots" :key="slot.id">
                <div>
                    <input type="hidden" name="jam[]" :value="slot.time" />
                    <input type="hidden" name="schedule_ids[]" :value="slot.id" />
                </div>
            </template>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_360px] xl:grid-cols-[minmax(0,1fr)_400px] items-start">

            {{-- ════════════════════════════════════════════════════════════
                 LEFT COLUMN: Horizontal Date Strip & Session Grids
                 ════════════════════════════════════════════════════════════ --}}
            <section class="min-w-0 space-y-6">

                {{-- Horizontal Date Picker Carousel --}}
                <div class="relative bg-white rounded-2xl border border-slate-200/80 p-2 sm:p-3 shadow-2xs">
                    {{-- Scroll Prev Button --}}
                    <button type="button"
                            @click="scrollDates('left')"
                            class="absolute -left-2.5 top-1/2 -translate-y-1/2 z-10 flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-md transition-all hover:bg-slate-50 hover:text-slate-900 active:scale-95 cursor-pointer"
                            aria-label="Scroll tanggal ke kiri">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    {{-- Scrollable Date Container --}}
                    <div x-ref="dateStrip"
                         class="flex items-center gap-2 sm:gap-3 overflow-x-auto no-scrollbar scroll-smooth px-6 py-1 select-none">
                        @foreach($dates as $d)
                            <button type="button"
                                    @click="selectDate(@js($d['date']))"
                                    {{ !$d['is_available'] ? 'disabled' : '' }}
                                    :class="{
                                        'border-2 border-[#4F46E5] bg-[#EEF2FF] text-[#4F46E5] shadow-xs font-bold scale-[1.03]': selectedDate === @js($d['date']),
                                        'border border-slate-200/70 bg-white text-slate-700 hover:border-[#4F46E5]/40 hover:bg-slate-50': selectedDate !== @js($d['date']) && @js($d['is_available']),
                                        'border border-transparent bg-slate-50/60 text-slate-300 cursor-not-allowed opacity-40': @js(!$d['is_available'])
                                    }"
                                    class="flex flex-col items-center justify-center rounded-2xl w-14 sm:w-16 h-18 sm:h-20 shrink-0 transition-all duration-150 cursor-pointer text-center relative focus:outline-none">

                                {{-- Day Name (Mon, Tue, Wed...) --}}
                                <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider"
                                      :class="selectedDate === @js($d['date']) ? 'text-[#4F46E5]' : (@js($d['is_available']) ? 'text-slate-500' : 'text-slate-300')">
                                    {{ $d['day_label'] }}
                                </span>

                                {{-- Day Number (13, 14, 15...) --}}
                                <span class="text-lg sm:text-xl font-extrabold mt-0.5 leading-none"
                                      :class="selectedDate === @js($d['date']) ? 'text-[#4F46E5]' : (@js($d['is_available']) ? 'text-[#0F172A]' : 'text-slate-300')">
                                    {{ $d['day_number'] }}
                                </span>

                                {{-- Active Indicator Dot --}}
                                <span x-show="selectedDate === @js($d['date'])" class="w-1.5 h-1.5 rounded-full bg-[#4F46E5] mt-1.5"></span>
                            </button>
                        @endforeach
                    </div>

                    {{-- Scroll Next Button --}}
                    <button type="button"
                            @click="scrollDates('right')"
                            class="absolute -right-2.5 top-1/2 -translate-y-1/2 z-10 flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-md transition-all hover:bg-slate-50 hover:text-slate-900 active:scale-95 cursor-pointer"
                            aria-label="Scroll tanggal ke kanan">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                {{-- Multi-Session Guidance Pill --}}
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div class="inline-flex items-center gap-2 text-xs font-semibold text-indigo-700 bg-indigo-50/80 border border-indigo-200/60 rounded-xl px-3.5 py-2">
                        <svg class="w-4 h-4 text-[#4F46E5] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Anda dapat memilih <strong>lebih dari 1 sesi berurutan</strong> (misal: 2 jam atau lebih).</span>
                    </div>

                    <button type="button"
                            x-show="selectedSlots.length > 1"
                            x-cloak
                            @click="selectedSlots = []"
                            class="text-xs text-slate-500 hover:text-red-600 transition font-medium cursor-pointer underline">
                        Hapus Pilihan (<span x-text="selectedSlots.length"></span> sesi)
                    </button>
                </div>

                {{-- Loading Skeleton during Date Fetching --}}
                <div x-show="loadingSlots" x-cloak class="space-y-6 animate-pulse">
                    <div class="h-4 w-36 bg-slate-200 rounded"></div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3.5">
                        <div class="h-20 bg-slate-100 rounded-2xl border border-slate-200/60"></div>
                        <div class="h-20 bg-slate-100 rounded-2xl border border-slate-200/60"></div>
                        <div class="h-20 bg-slate-100 rounded-2xl border border-slate-200/60"></div>
                        <div class="h-20 bg-slate-100 rounded-2xl border border-slate-200/60"></div>
                    </div>
                </div>

                {{-- Sessions Content --}}
                <div x-show="!loadingSlots" class="space-y-8">

                    {{-- ── 1. MORNING SESSIONS ── --}}
                    <div x-show="morningSlots.length > 0">
                        {{-- Category Title with Sun Icon --}}
                        <div class="flex items-center gap-2 mb-3.5 text-xs font-bold tracking-wider text-slate-500 uppercase">
                            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <circle cx="12" cy="12" r="4" />
                                <path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" />
                            </svg>
                            <span>Sesi Pagi</span>
                        </div>

                        {{-- Grid of Morning Time Slots --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-3.5">
                            <template x-for="slot in morningSlots" :key="slot.id">
                                <button type="button"
                                        @click="slot.is_available ? toggleSlot(slot) : null"
                                        :disabled="!slot.is_available"
                                        :class="{
                                            'bg-[#4F46E5] border-2 border-[#4F46E5] text-white shadow-md shadow-indigo-200/60 scale-[1.02]': isSlotSelected(slot),
                                            'bg-white border-1.5 border-slate-200 text-[#0F172A] hover:border-[#4F46E5]/60 hover:bg-indigo-50/20 active:scale-[0.98]': !isSlotSelected(slot) && slot.is_available,
                                            'bg-[#F8FAFC] border border-slate-200/80 text-slate-400 cursor-not-allowed opacity-60': !slot.is_available
                                        }"
                                        class="flex flex-col items-center justify-center p-3.5 sm:p-4 rounded-2xl transition-all duration-150 text-center select-none cursor-pointer focus:outline-none relative">

                                    {{-- Time Display (e.g. 08:00 - 09:00) --}}
                                    <span class="text-sm sm:text-base font-extrabold tracking-tight leading-tight"
                                          :class="isSlotSelected(slot) ? 'text-white' : (slot.is_available ? 'text-[#0F172A]' : 'text-slate-400')"
                                          x-text="slot.time_range || (slot.time + ' – ' + slot.end_time)">
                                    </span>

                                    {{-- Period (AM/PM) --}}
                                    <span class="text-[11px] font-bold uppercase tracking-wider mt-0.5"
                                          :class="isSlotSelected(slot) ? 'text-white/80' : 'text-slate-400'"
                                          x-text="slot.period || 'WIB'">
                                    </span>

                                    {{-- Multi-Session Sequence Badge when > 1 session selected --}}
                                    <template x-if="selectedSlots.length > 1 && isSlotSelected(slot)">
                                        <span class="mt-1.5 inline-flex items-center gap-1 text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-white/20 text-white">
                                            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span x-text="'Sesi ' + getSlotOrder(slot)"></span>
                                        </span>
                                    </template>
                                </button>
                            </template>

                            {{-- Server-rendered Fallback / Pre-mount Slots --}}
                            @foreach($morningSlots as $slot)
                                <div x-show="!mounted"
                                     class="flex flex-col items-center justify-center p-3.5 sm:p-4 rounded-2xl border transition-all text-center select-none {{ $slot['is_available'] ? 'bg-white border-slate-200 text-[#0F172A]' : 'bg-[#F8FAFC] border-slate-200/80 text-slate-400 opacity-60' }}">
                                    <span class="text-sm sm:text-base font-extrabold tracking-tight leading-tight">
                                        {{ $slot['time_range'] ?? ($slot['time'] . ' – ' . $slot['end_time']) }}
                                    </span>
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mt-0.5">
                                        {{ $slot['period'] ?? 'WIB' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ── 2. AFTERNOON SESSIONS ── --}}
                    <div x-show="afternoonSlots.length > 0">
                        {{-- Category Title with Afternoon Sun Icon --}}
                        <div class="flex items-center gap-2 mb-3.5 text-xs font-bold tracking-wider text-slate-500 uppercase">
                            <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <circle cx="12" cy="12" r="4" />
                                <path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2" />
                            </svg>
                            <span>Sesi Siang &amp; Sore</span>
                        </div>

                        {{-- Grid of Afternoon Time Slots --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-3.5">
                            <template x-for="slot in afternoonSlots" :key="slot.id">
                                <button type="button"
                                        @click="slot.is_available ? toggleSlot(slot) : null"
                                        :disabled="!slot.is_available"
                                        :class="{
                                            'bg-[#4F46E5] border-2 border-[#4F46E5] text-white shadow-md shadow-indigo-200/60 scale-[1.02]': isSlotSelected(slot),
                                            'bg-white border-1.5 border-slate-200 text-[#0F172A] hover:border-[#4F46E5]/60 hover:bg-indigo-50/20 active:scale-[0.98]': !isSlotSelected(slot) && slot.is_available,
                                            'bg-[#F8FAFC] border border-slate-200/80 text-slate-400 cursor-not-allowed opacity-60': !slot.is_available
                                        }"
                                        class="flex flex-col items-center justify-center p-3.5 sm:p-4 rounded-2xl transition-all duration-150 text-center select-none cursor-pointer focus:outline-none relative">
                                    <span class="text-sm sm:text-base font-extrabold tracking-tight leading-tight"
                                          :class="isSlotSelected(slot) ? 'text-white' : (slot.is_available ? 'text-[#0F172A]' : 'text-slate-400')"
                                          x-text="slot.time_range || (slot.time + ' – ' + slot.end_time)">
                                    </span>
                                    <span class="text-[11px] font-bold uppercase tracking-wider mt-0.5"
                                          :class="isSlotSelected(slot) ? 'text-white/80' : 'text-slate-400'"
                                          x-text="slot.period || 'WIB'">
                                    </span>
                                    <template x-if="selectedSlots.length > 1 && isSlotSelected(slot)">
                                        <span class="mt-1.5 inline-flex items-center gap-1 text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-white/20 text-white">
                                            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span x-text="'Sesi ' + getSlotOrder(slot)"></span>
                                        </span>
                                    </template>
                                </button>
                            </template>

                            {{-- Server-rendered Fallback / Pre-mount Slots --}}
                            @foreach($afternoonSlots as $slot)
                                <div x-show="!mounted"
                                     class="flex flex-col items-center justify-center p-3.5 sm:p-4 rounded-2xl border transition-all text-center select-none {{ $slot['is_available'] ? 'bg-white border-slate-200 text-[#0F172A]' : 'bg-[#F8FAFC] border-slate-200/80 text-slate-400 opacity-60' }}">
                                    <span class="text-sm sm:text-base font-extrabold tracking-tight leading-tight">
                                        {{ $slot['time_range'] ?? ($slot['time'] . ' – ' . $slot['end_time']) }}
                                    </span>
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mt-0.5">
                                        {{ $slot['period'] ?? 'WIB' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ── 3. EVENING SESSIONS (Optional) ── --}}
                    <div x-show="eveningSlots.length > 0">
                        {{-- Category Title with Moon Icon --}}
                        <div class="flex items-center gap-2 mb-3.5 text-xs font-bold tracking-wider text-slate-500 uppercase">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            <span>Sesi Malam</span>
                        </div>

                        {{-- Grid of Evening Time Slots --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-3.5">
                            <template x-for="slot in eveningSlots" :key="slot.id">
                                <button type="button"
                                        @click="slot.is_available ? toggleSlot(slot) : null"
                                        :disabled="!slot.is_available"
                                        :class="{
                                            'bg-[#4F46E5] border-2 border-[#4F46E5] text-white shadow-md shadow-indigo-200/60 scale-[1.02]': isSlotSelected(slot),
                                            'bg-white border-1.5 border-slate-200 text-[#0F172A] hover:border-[#4F46E5]/60 hover:bg-indigo-50/20 active:scale-[0.98]': !isSlotSelected(slot) && slot.is_available,
                                            'bg-[#F8FAFC] border border-slate-200/80 text-slate-400 cursor-not-allowed opacity-60': !slot.is_available
                                        }"
                                        class="flex flex-col items-center justify-center p-3.5 sm:p-4 rounded-2xl transition-all duration-150 text-center select-none cursor-pointer focus:outline-none relative">
                                    <span class="text-sm sm:text-base font-extrabold tracking-tight leading-tight"
                                          :class="isSlotSelected(slot) ? 'text-white' : (slot.is_available ? 'text-[#0F172A]' : 'text-slate-400')"
                                          x-text="slot.time_range || (slot.time + ' – ' + slot.end_time)">
                                    </span>
                                    <span class="text-[11px] font-bold uppercase tracking-wider mt-0.5"
                                          :class="isSlotSelected(slot) ? 'text-white/80' : 'text-slate-400'"
                                          x-text="slot.period || 'WIB'">
                                    </span>
                                    <template x-if="selectedSlots.length > 1 && isSlotSelected(slot)">
                                        <span class="mt-1.5 inline-flex items-center gap-1 text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-white/20 text-white">
                                            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span x-text="'Sesi ' + getSlotOrder(slot)"></span>
                                        </span>
                                    </template>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Empty State when no slots on this date --}}
                    <div x-show="morningSlots.length === 0 && afternoonSlots.length === 0 && eveningSlots.length === 0"
                         class="">
                        <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-8 sm:p-12 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 mb-3.5">
                                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-[#0F172A]">Tidak Ada Jadwal Tersedia</h3>
                            <p class="mt-1 text-xs sm:text-sm text-[#64748B] max-w-sm mx-auto">
                                Tidak ada sesi yang dapat dipesan pada tanggal ini. Silakan pilih tanggal lain pada kalender di atas.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ════════════════════════════════════════════════════════════
                 RIGHT COLUMN: Sticky Pastel Lavender Summary Card
                 Matching Screenshot Design with Multi-Session Breakdown
                 ════════════════════════════════════════════════════════════ --}}
            <aside class="w-full">
                <div class="sticky top-24 rounded-3xl bg-[#EEF1F8] p-6 sm:p-7 border border-indigo-100/60 shadow-sm space-y-5">

                    {{-- Service Header Info --}}
                    <div class="flex items-center gap-3.5">
                        {{-- Thumbnail Image / Icon --}}
                        <div class="w-14 h-14 rounded-2xl bg-white border border-slate-200/80 shadow-2xs flex items-center justify-center overflow-hidden shrink-0">
                            @if(!empty($servicePayload['image_url']))
                                <img src="{{ $servicePayload['image_url'] }}" alt="{{ $servicePayload['name'] }}" class="w-full h-full object-cover" />
                            @else
                                <span class="text-2xl" role="img" aria-label="Program">👍</span>
                            @endif
                        </div>

                        {{-- Service Title & Studio/Business --}}
                        <div class="flex-1 min-w-0">
                            <h2 class="text-base font-black text-[#0F172A] leading-snug line-clamp-2" x-text="service.name">
                                {{ $service->namalayanan }}
                            </h2>
                            <p class="text-xs text-[#64748B] font-medium mt-0.5 truncate">
                                {{ $tenant->namabisnis }}
                            </p>
                        </div>
                    </div>

                    {{-- Divider --}}
                    <div class="border-t border-slate-200/80"></div>

                    {{-- Metadata Rows --}}
                    <div class="space-y-3 text-xs sm:text-sm">
                        {{-- Date --}}
                        <div class="flex items-center justify-between">
                            <span class="text-[#64748B] font-medium">Tanggal</span>
                            <span class="font-bold text-[#0F172A]" x-text="selectedDateFormatted">
                                {{ $selectedDateFormatted }}
                            </span>
                        </div>

                        {{-- Time (Shows Range + Total Sessions) --}}
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-[#64748B] font-medium shrink-0">Waktu</span>
                            <span class="font-bold text-[#0F172A] text-right" x-text="selectedTimeFormatted">

                            </span>
                        </div>

                        {{-- Duration (Calculates 2 Hours, 3 Hours, etc.) --}}
                        <div class="flex items-center justify-between">
                            <span class="text-[#64748B] font-medium">Durasi</span>
                            <span class="font-bold text-[#0F172A]" x-text="selectedDurationFormatted">
                                {{ $servicePayload['duration'] }} menit
                            </span>
                        </div>
                    </div>

                    {{-- Multi-Session Detail List (shown if > 1 session selected) --}}
                    <template x-if="selectedSlots.length > 1">
                        <div class="pt-2 border-t border-slate-200/60 space-y-2">
                            <div class="flex items-center justify-between text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <span>Rincian Sesi</span>
                                <span x-text="selectedSlots.length + ' sesi dipilih'"></span>
                            </div>
                            <div class="space-y-1.5 max-h-32 overflow-y-auto no-scrollbar pr-0.5">
                                <template x-for="(s, idx) in sortedSelectedSlots" :key="s.id">
                                    <div class="flex items-center justify-between text-xs bg-white/70 rounded-xl px-3 py-2 border border-slate-200/60 shadow-2xs">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="w-4 h-4 rounded-full bg-[#4F46E5] text-white flex items-center justify-center text-[10px] font-bold shrink-0" x-text="idx + 1"></span>
                                            <span class="font-bold text-slate-800 truncate" x-text="s.time_range || (s.time + ' – ' + s.end_time)"></span>
                                        </div>
                                        <span class="font-extrabold text-[#4F46E5] shrink-0" x-text="s.price_label || formatRupiah(s.price)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Divider --}}
                    <div class="border-t border-slate-200/80"></div>

                    {{-- Pricing Breakdown --}}
                    <div class="space-y-2 text-xs sm:text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-[#64748B] font-medium" x-text="selectedSlots.length > 1 ? ('Subtotal (' + selectedSlots.length + ' Sesi)') : 'Subtotal'">
                                Subtotal
                            </span>
                            <span class="font-bold text-[#0F172A]" x-text="formatRupiah(subtotal)">
                                {{ $servicePayload['price_label'] }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-[#64748B] font-medium">Biaya Layanan</span>
                            <span class="font-bold text-[#0F172A]" x-text="serviceFee > 0 ? formatRupiah(serviceFee) : 'Rp0'">
                                Rp0
                            </span>
                        </div>

                        {{-- Total Row --}}
                        <div class="pt-2 flex items-baseline justify-between">
                            <span class="text-sm sm:text-base font-bold text-[#0F172A]">Total</span>
                            <span class="text-xl sm:text-2xl font-black text-[#4F46E5] tracking-tight" x-text="formatRupiah(total)">
                                {{ $servicePayload['price_label'] }}
                            </span>
                        </div>
                    </div>

                    {{-- Lanjutkan CTA Button --}}
                    <div class="pt-2">
                        <button type="submit"
                                id="schedule-checkout-btn"
                                :disabled="selectedSlots.length === 0 || isSubmitting || loadingSlots || !!errorMessage"
                                :class="{
                                    'bg-[#4F46E5] hover:bg-[#4338CA] text-white shadow-lg shadow-indigo-200/70 active:scale-[0.98] cursor-pointer': selectedSlots.length > 0 && !isSubmitting,
                                    'bg-slate-300 text-white cursor-not-allowed opacity-60': selectedSlots.length === 0 || isSubmitting
                                }"
                                class="w-full py-3.5 px-6 rounded-2xl font-bold text-sm sm:text-base transition-all duration-150 flex items-center justify-center gap-2">
                            <template x-if="isSubmitting">
                                <span class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>Memproses...</span>
                                </span>
                            </template>
                            <template x-if="!isSubmitting">
                                <span x-text="selectedSlots.length > 1 ? ('Lanjutkan ' + selectedSlots.length + ' Sesi') : (selectedSlots.length === 1 ? 'Lanjutkan' : 'Pilih Jam Terlebih Dahulu')"></span>
                            </template>
                            <span x-show="!mounted" class="{{ count($selectedTimes) > 0 ? '' : 'hidden' }}">Lanjutkan</span>
                        </button>
                    </div>

                    {{-- Security Footnote --}}
                    <div class="pt-1 flex items-center justify-center gap-1.5 text-[11px] font-medium text-slate-400 select-none">
                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                        <span>Pembayaran aman melalui BookQu</span>
                    </div>
                </div>
            </aside>

        </div>

        {{-- Mobile Sticky Bottom Bar (when screen < 1024px) --}}
        <div class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 px-4 py-3 shadow-lg flex items-center justify-between gap-3">
            <div class="flex flex-col min-w-0">
                <span class="text-[11px] text-[#64748B] font-medium truncate" x-text="selectedSlots.length > 0 ? (selectedDateFormatted + ' • ' + selectedSlots.length + ' Sesi (' + selectedDurationFormatted + ')') : 'Belum pilih jam'"></span>
                <span class="text-base font-extrabold text-[#4F46E5]" x-text="formatRupiah(total)">{{ $servicePayload['price_label'] }}</span>
            </div>
            <button type="submit"
                    :disabled="selectedSlots.length === 0 || isSubmitting || loadingSlots || !!errorMessage"
                    :class="{
                        'bg-[#4F46E5] text-white cursor-pointer': selectedSlots.length > 0 && !isSubmitting,
                        'bg-slate-300 text-white cursor-not-allowed opacity-60': selectedSlots.length === 0 || isSubmitting
                    }"
                    class="py-2.5 px-6 rounded-xl font-bold text-xs sm:text-sm shrink-0">
                <span x-text="selectedSlots.length > 1 ? ('Lanjutkan (' + selectedSlots.length + ')') : 'Lanjutkan'"></span>
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script defer src="{{ asset('js/booking-schedule.js') }}"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.15.12/dist/cdn.min.js"></script>
@endsection
