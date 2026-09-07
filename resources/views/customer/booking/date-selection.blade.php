@extends('customer.layouts.booking-shell')

@section('title', 'Pilih Tanggal')
@section('current_step', 2)
@section('back_url', route('customer.booking.program', $tenant->slug))
@section('back_label', 'Pilih Layanan')

@section('content')
<div
    id="booking-date-root"
    data-tenant-slug="{{ $tenant->slug }}"
    data-min-date="{{ $minDate }}"
    data-max-date="{{ $maxDate }}"
    data-selected-date="{{ $selectedDate }}"
    data-simulate="{{ $simulate ? 'true' : 'false' }}"
    x-data="bookingDateSelection()"
    @pageshow.window="isSubmitting = false"
    @pagehide.window="isSubmitting = false"
    @popstate.window="isSubmitting = false"
>
    <form
        id="booking-date-form"
        class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]"
        method="POST"
        action="{{ route('customer.booking.select-date', $tenant->slug) }}"
        x-ref="confirmForm"
    >
        @csrf
        <input type="hidden" name="tanggal" :value="selectedDate" />
        @if ($simulate)
            <input type="hidden" name="simulate" value="1" />
        @endif

        {{-- Left Column: Calendar & Details --}}
        <section>
            <div class="mb-6">
                <a
                    href="{{ route('customer.booking.program', $tenant->slug) }}"
                    class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-[#64748B] hover:text-[#4F46E5] transition-colors mb-2"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Kembali ke Pemilihan Layanan
                </a>
                <h1 class="text-xl sm:text-2xl font-black text-[#0F172A] tracking-tight">Pilih Tanggal</h1>
                <p class="mt-1 text-sm text-[#64748B]">Tentukan tanggal sesi yang sesuai dengan ketersediaan Anda.</p>

                {{-- Quick Date Shortcuts --}}
                <div class="mt-4 flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar text-xs">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#94A3B8] shrink-0 mr-1">Pilihan Cepat:</span>
                    <button
                        type="button"
                        @click="selectQuickDate('today')"
                        :class="!isQuickDateAvailable('today') ? 'opacity-40 cursor-not-allowed bg-slate-50 border-slate-200 text-slate-400' : 'bg-white hover:bg-[#F8FAFC] hover:border-[#4F46E5] text-[#0F172A] border-[#CBD5E1] shadow-2xs'"
                        :disabled="!isQuickDateAvailable('today')"
                        class="inline-flex items-center gap-1 rounded-xl border px-3 py-1.5 font-semibold transition shrink-0 cursor-pointer"
                    >
                        <span>Hari Ini</span>
                    </button>
                    <button
                        type="button"
                        @click="selectQuickDate('tomorrow')"
                        :class="!isQuickDateAvailable('tomorrow') ? 'opacity-40 cursor-not-allowed bg-slate-50 border-slate-200 text-slate-400' : 'bg-white hover:bg-[#F8FAFC] hover:border-[#4F46E5] text-[#0F172A] border-[#CBD5E1] shadow-2xs'"
                        :disabled="!isQuickDateAvailable('tomorrow')"
                        class="inline-flex items-center gap-1 rounded-xl border px-3 py-1.5 font-semibold transition shrink-0 cursor-pointer"
                    >
                        <span>Besok</span>
                    </button>
                    <button
                        type="button"
                        @click="selectQuickDate('this_saturday')"
                        :class="!isQuickDateAvailable('this_saturday') ? 'opacity-40 cursor-not-allowed bg-slate-50 border-slate-200 text-slate-400' : 'bg-white hover:bg-[#F8FAFC] hover:border-[#4F46E5] text-[#0F172A] border-[#CBD5E1] shadow-2xs'"
                        :disabled="!isQuickDateAvailable('this_saturday')"
                        class="inline-flex items-center gap-1 rounded-xl border px-3 py-1.5 font-semibold transition shrink-0 cursor-pointer"
                    >
                        <span>Sabtu Ini</span>
                    </button>
                    <button
                        type="button"
                        @click="selectQuickDate('this_sunday')"
                        :class="!isQuickDateAvailable('this_sunday') ? 'opacity-40 cursor-not-allowed bg-slate-50 border-slate-200 text-slate-400' : 'bg-white hover:bg-[#F8FAFC] hover:border-[#4F46E5] text-[#0F172A] border-[#CBD5E1] shadow-2xs'"
                        :disabled="!isQuickDateAvailable('this_sunday')"
                        class="inline-flex items-center gap-1 rounded-xl border px-3 py-1.5 font-semibold transition shrink-0 cursor-pointer"
                    >
                        <span>Minggu Ini</span>
                    </button>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-xs sm:text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Selected Date Info Banner & Urgency Notice --}}
            <div x-show="selectedDate" x-cloak class="mb-4 rounded-xl border border-[#C7D2FE] bg-[#EEF2FF] p-3.5 flex items-center justify-between text-xs sm:text-sm">
                <div class="flex items-center gap-2 text-[#312E81]">
                    <svg class="h-4 w-4 text-[#4F46E5] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Tanggal Terpilih: <strong x-text="selectedDate"></strong></span>
                </div>
                <template x-if="getRemainingSlots(selectedDate) > 0 && getRemainingSlots(selectedDate) <= 3">
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-800 px-2 py-0.5 text-[11px] font-bold">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        Tersisa <span x-text="getRemainingSlots(selectedDate)"></span> slot!
                    </span>
                </template>
            </div>

            {{-- Reusable Calendar Component --}}
            <x-customer.booking-calendar />

            {{-- Policy Notice --}}
            <div class="mt-6">
                <x-customer.booking-policy />
            </div>
        </section>

        {{-- Right Column: Sticky Booking Summary --}}
        <x-customer.booking-sidebar
            :service="$service"
            buttonLabel="Lanjut Pilih Jam"
            buttonEnabledWhen="selectedDate"
            onButtonClick="handleConfirm()"
            :backUrl="route('customer.booking.program', $tenant->slug)"
            backLabel="Kembali ke Layanan"
        />
    </form>
</div>

{{-- Data Contracts --}}
<script type="application/json" id="booking-service-data">@json($servicePayload)</script>
<script type="application/json" id="booking-services-data">@json($servicePayload)</script>
<script type="application/json" id="booking-availability-data">@json($availabilityPayload)</script>
@endsection

@section('scripts')
<script defer src="{{ asset('js/booking-date.js') }}"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection
