@extends('customer.layouts.booking-shell')

@section('title', 'Pilih Jam')
@section('current_step', 3)
@section('back_url', route(\App\Support\CustomerBookingRoutes::name('customer.booking.date'), $tenant->slug))
@section('back_label', 'Pilih Tanggal')

@section('content')
<div
    id="booking-time-root"
    data-tenant-slug="{{ $tenant->slug }}"
    data-selected-date="{{ $selectedDate }}"
    data-selected-date-label="{{ $selectedDateLabel }}"
    data-selected-times="{{ json_encode($selectedTimes ?? []) }}"
    data-simulate="{{ $simulate ? 'true' : 'false' }}"
    x-data="bookingTimeSelection()"
    @pageshow.window="isSubmitting = false"
    @pagehide.window="isSubmitting = false"
    @popstate.window="isSubmitting = false"
>
    <form
        id="booking-time-form"
        class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px] max-w-6xl mx-auto w-full"
        method="POST"
        action="{{ route(\App\Support\CustomerBookingRoutes::name('customer.booking.select-time'), $tenant->slug) }}"
        x-ref="confirmForm"
    >
        @csrf
        {{-- Send each selected slot as jam[] and schedule_ids[] arrays --}}
        <template x-for="item in sortedSelectedTimes" :key="item.id">
            <div>
                <input type="hidden" name="jam[]" :value="item.time" />
                <input type="hidden" name="schedule_ids[]" :value="item.id" />
            </div>
        </template>
        @if ($simulate)
            <input type="hidden" name="simulate" value="1" />
        @endif

        {{-- Left Column: Session Slot Choices --}}
        <section>
            <div class="mb-6">
                <a
                    href="{{ route(\App\Support\CustomerBookingRoutes::name('customer.booking.date'), $tenant->slug) }}"
                    class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-[#64748B] hover:text-[#4F46E5] transition-colors mb-2"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Kembali ke Pemilihan Tanggal
                </a>
                <h1 class="text-xl sm:text-2xl font-black text-[#0F172A] tracking-tight">Pilih Jam Sesi</h1>
                <p class="mt-1 text-sm text-[#64748B]">
                    Slot waktu yang tersedia untuk <strong class="text-[#0F172A]">{{ $selectedDateLabel }}</strong>.
                    <span class="text-[#4F46E5] font-semibold">Anda bisa memilih lebih dari 1 jam.</span>
                </p>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-xs sm:text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="space-y-6">
                {{-- Sesi Pagi --}}
                <div x-show="groupedSlots.morning.length" x-cloak class="rounded-2xl border border-[#E2E8F0] bg-white p-5 sm:p-6 shadow-2xs">
                    <div class="flex items-center gap-2 mb-4 pb-3 border-b border-[#F1F5F9]">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </span>
                        <h3 class="text-sm font-bold text-[#0F172A]">Sesi Pagi</h3>
                        <span class="text-xs text-[#64748B]">(05:00 - 11:59 WIB)</span>
                    </div>
                    <div class="booking-time-grid">
                        <template x-for="slot in groupedSlots.morning" :key="slot.id">
                            <x-customer.time-slot-card slot-var="slot" />
                        </template>
                    </div>
                </div>

                {{-- Sesi Siang & Sore --}}
                <div x-show="groupedSlots.afternoon.length" x-cloak class="rounded-2xl border border-[#E2E8F0] bg-white p-5 sm:p-6 shadow-2xs">
                    <div class="flex items-center gap-2 mb-4 pb-3 border-b border-[#F1F5F9]">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-orange-50 text-orange-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </span>
                        <h3 class="text-sm font-bold text-[#0F172A]">Sesi Siang &amp; Sore</h3>
                        <span class="text-xs text-[#64748B]">(12:00 - 17:59 WIB)</span>
                    </div>
                    <div class="booking-time-grid">
                        <template x-for="slot in groupedSlots.afternoon" :key="slot.id">
                            <x-customer.time-slot-card slot-var="slot" />
                        </template>
                    </div>
                </div>

                {{-- Sesi Malam --}}
                <div x-show="groupedSlots.evening.length" x-cloak class="rounded-2xl border border-[#E2E8F0] bg-white p-5 sm:p-6 shadow-2xs">
                    <div class="flex items-center gap-2 mb-4 pb-3 border-b border-[#F1F5F9]">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </span>
                        <h3 class="text-sm font-bold text-[#0F172A]">Sesi Malam</h3>
                        <span class="text-xs text-[#64748B]">(18:00 - 23:59 WIB)</span>
                    </div>
                    <div class="booking-time-grid">
                        <template x-for="slot in groupedSlots.evening" :key="slot.id">
                            <x-customer.time-slot-card slot-var="slot" />
                        </template>
                    </div>
                </div>

                {{-- Empty State --}}
                <div class="rounded-2xl border border-dashed border-[#CBD5E1] bg-white p-10 text-center" x-show="!hasSlots" x-cloak>
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#F1F5F9] text-[#64748B] mb-3">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-[#0F172A]">Tidak Ada Slot Tersedia</h4>
                    <p class="text-xs text-[#64748B] mt-1">Semua slot pada tanggal ini sudah penuh atau tidak tersedia. Silakan pilih tanggal lain.</p>
                    <a
                        href="{{ route(\App\Support\CustomerBookingRoutes::name('customer.booking.date'), $tenant->slug) }}"
                        class="mt-4 inline-flex items-center gap-1.5 rounded-xl border border-[#CBD5E1] bg-white px-4 py-2 text-xs font-bold text-[#0F172A] hover:bg-[#F8FAFC]"
                    >
                        &larr; Pilih Tanggal Lain
                    </a>
                </div>
            </div>

            {{-- Booking Policy --}}
            <div class="mt-6">
                <x-customer.booking-policy />
            </div>
        </section>

        {{-- Right Column: Sticky Summary + Confirm Button --}}
        <aside class="hidden lg:block lg:sticky lg:top-24 h-fit">
            <div class="rounded-2xl border border-[#E2E8F0] bg-white p-6 shadow-sm transition-all"
                 :class="selectedCount > 0 ? 'border-[#4F46E5]/30 shadow-md shadow-[#4F46E5]/5' : ''">
                <div class="border-b border-[#F1F5F9] pb-4">
                    <h2 class="text-base font-bold text-[#0F172A]">Ringkasan Jam Terpilih</h2>
                    <p class="text-xs text-[#64748B] mt-0.5">Pilih satu atau lebih slot waktu lalu klik Lanjut</p>
                </div>

                {{-- Service Info --}}
                <div class="mt-4 flex items-start gap-3 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-3.5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#EEF2FF] text-[#4F46E5]">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Layanan</p>
                        <p class="text-sm font-bold text-[#0F172A] truncate" x-text="service?.name || '-'"></p>
                    </div>
                </div>

                {{-- Date Info --}}
                <div class="mt-3 flex items-start gap-3 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-3.5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#EEF2FF] text-[#4F46E5]">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Tanggal</p>
                        <p class="text-sm font-bold text-[#0F172A] truncate" x-text="selectedDateLabel"></p>
                    </div>
                </div>

                {{-- Selected Times List --}}
                <div class="mt-4">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Jam Terpilih</p>
                        <span class="text-xs font-bold text-[#4F46E5] bg-[#EEF2FF] px-2 py-0.5 rounded-full" x-show="selectedCount > 0" x-text="selectedCount + ' slot'" x-cloak></span>
                    </div>

                    {{-- Empty state --}}
                    <div x-show="selectedCount === 0" class="rounded-xl border border-dashed border-[#CBD5E1] bg-[#F8FAFC] p-4 text-center">
                        <svg class="h-8 w-8 mx-auto text-[#94A3B8] mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-xs text-[#94A3B8] font-medium">Klik slot waktu di sebelah kiri untuk memilih</p>
                    </div>

                    {{-- Selected chips --}}
                    <div x-show="selectedCount > 0" class="space-y-2" x-cloak>
                        <template x-for="item in sortedSelectedTimes" :key="item.id">
                            <div class="flex items-center justify-between rounded-xl border border-[#4F46E5]/20 bg-[#EEF2FF] px-3.5 py-2.5 transition-all">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#4F46E5] text-white">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                    <span class="text-sm font-bold text-[#0F172A]" x-text="item.time + ' WIB'"></span>
                                </div>
                                <button type="button"
                                    class="flex h-6 w-6 items-center justify-center rounded-full text-[#64748B] hover:bg-red-100 hover:text-red-600 transition-colors"
                                    @click.prevent="selectSlot(timeSlots.find(s => s.id === item.id))"
                                    title="Hapus"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Pricing --}}
                <div class="mt-4 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-4" x-show="selectedCount > 0" x-cloak>
                    <div class="flex items-center justify-between text-xs text-[#64748B] mb-1" x-show="selectedCount > 1">
                        <span x-text="selectedCount + ' × ' + perSlotLabel"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-[#0F172A]">Total Estimasi</span>
                        <span class="text-xl font-extrabold text-[#4F46E5]" x-text="totalLabel"></span>
                    </div>
                    <p class="mt-1 text-[11px] text-[#94A3B8]">Harga final akan dikonfirmasi di halaman checkout</p>
                </div>

                {{-- Submit CTA --}}
                <button
                    type="button"
                    @click="handleConfirm()"
                    :disabled="!canSubmit"
                    class="mt-5 w-full flex items-center justify-center gap-2 rounded-xl py-3.5 px-4 text-sm font-bold shadow-md transition-all"
                    :class="canSubmit
                        ? 'bg-[#4F46E5] text-white shadow-[#4F46E5]/20 hover:bg-[#4338CA] hover:shadow-lg hover:shadow-[#4F46E5]/30 cursor-pointer active:scale-[0.98]'
                        : 'bg-[#E2E8F0] text-[#94A3B8] cursor-not-allowed shadow-none'"
                >
                    <template x-if="isSubmitting">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <span x-text="isSubmitting ? 'Memproses...' : (canSubmit ? 'Lanjut ke Data Diri →' : 'Pilih minimal 1 jam')"></span>
                </button>

                <a
                    href="{{ route(\App\Support\CustomerBookingRoutes::name('customer.booking.date'), $tenant->slug) }}"
                    class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl border border-[#E2E8F0] bg-white py-2.5 px-4 text-xs font-semibold text-[#64748B] transition hover:border-[#CBD5E1] hover:bg-[#F8FAFC] hover:text-[#0F172A]"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Kembali ke Pilih Tanggal
                </a>
            </div>
        </aside>
    </form>

    {{-- Mobile Bottom Floating Action Bar --}}
    <div class="booking-mobile-bar lg:hidden" x-show="selectedCount > 0" x-cloak x-transition>
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <p class="text-[11px] text-[#64748B] truncate" x-text="selectedCount + ' jam terpilih'"></p>
                <div class="flex items-baseline gap-1.5">
                    <p class="text-sm font-bold text-[#0F172A]" x-text="selectedTimesLabel"></p>
                </div>
            </div>
            <button
                type="button"
                @click="handleConfirm()"
                :disabled="!canSubmit"
                class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl bg-[#4F46E5] hover:bg-[#4338CA] px-5 py-2.5 text-sm font-bold text-white shadow-md transition-all active:scale-95 shrink-0"
            >
                <span>Lanjut</span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </button>
        </div>
    </div>
</div>

{{-- Data Contracts --}}
<script type="application/json" id="booking-service-data">@json($servicePayload)</script>
<script type="application/json" id="booking-services-data">@json($servicePayload)</script>
<script type="application/json" id="booking-time-slots-data">@json($timeSlotsPayload ?? $slotsPayload ?? [])</script>
@endsection

@section('scripts')
<script defer src="{{ asset('js/booking-time.js') }}"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection
