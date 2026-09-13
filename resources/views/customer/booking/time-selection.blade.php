@extends('customer.layouts.booking-shell')

@section('title', 'Pilih Jam')
@section('current_step', 3)
@section('back_url', route('customer.booking.date', $tenant->slug))
@section('back_label', 'Pilih Tanggal')

@section('content')
<div
    id="booking-time-root"
    data-tenant-slug="{{ $tenant->slug }}"
    data-selected-date="{{ $selectedDate }}"
    data-selected-date-label="{{ $selectedDateLabel }}"
    data-selected-time="{{ $selectedTime ?? '' }}"
    data-simulate="{{ $simulate ? 'true' : 'false' }}"
    x-data="bookingTimeSelection()"
>
    <form
        id="booking-time-form"
        class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]"
        method="POST"
        action="{{ route('customer.booking.select-time', $tenant->slug) }}"
        x-ref="confirmForm"
    >
        @csrf
        <input type="hidden" name="schedule_id" :value="selectedScheduleId" />
        <input type="hidden" name="jam" :value="selectedTime" />
        @if ($simulate)
            <input type="hidden" name="simulate" value="1" />
        @endif

        {{-- Left Column: Session Slot Choices --}}
        <section>
            <div class="mb-6">
                <a
                    href="{{ route('customer.booking.date', $tenant->slug) }}"
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
                        href="{{ route('customer.booking.date', $tenant->slug) }}"
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

        {{-- Right Column: Sticky Booking Summary --}}
        <x-customer.booking-sidebar
            :service="$service"
            buttonLabel="Lanjut ke Data Diri"
            buttonEnabledWhen="selectedTime"
            onButtonClick="handleConfirm()"
            :backUrl="route('customer.booking.date', $tenant->slug)"
            backLabel="Kembali ke Tanggal"
        />
    </form>
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
