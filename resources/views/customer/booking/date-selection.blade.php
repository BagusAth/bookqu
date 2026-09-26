@extends('customer.layouts.booking-shell')

@section('title', 'Pilih Tanggal')
@section('current_step', 2)
@section('back_url', route(\App\Support\CustomerBookingRoutes::name('customer.booking.program'), $tenant->slug))
@section('back_label', 'Kembali ke Pilih Layanan')

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
        class="max-w-4xl mx-auto w-full"
        method="POST"
        action="{{ route(\App\Support\CustomerBookingRoutes::name('customer.booking.select-date'), $tenant->slug) }}"
        x-ref="confirmForm"
    >
        @csrf
        <input type="hidden" name="tanggal" :value="selectedDate" />
        @if ($simulate)
            <input type="hidden" name="simulate" value="1" />
        @endif

        {{-- Left Column: Calendar & Details --}}
        <section>
            {{-- Service Context Bar (S2.1) --}}
            <div class="mb-6 flex items-center gap-3.5 rounded-2xl border border-[#E2E8F0] bg-white p-3.5 sm:p-4 shadow-xs">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#EEF2FF] text-[#4F46E5]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-[#64748B]">Layanan Terpilih</p>
                    <p class="text-sm font-bold text-[#0F172A] truncate">{{ $service->namalayanan ?? 'Layanan' }}</p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-sm sm:text-base font-extrabold text-[#4F46E5]">Rp {{ number_format($service->harga ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-[#64748B]">{{ $service->durasi ?? 60 }} {{ $service->satuan_durasi ?: 'menit' }}</p>
                </div>
            </div>

            <div class="mb-6">
                <h1 class="text-xl sm:text-2xl font-black text-[#0F172A] tracking-tight">Pilih Tanggal</h1>
                <p class="mt-1 text-sm text-[#64748B]">Pilih tanggal yang tersedia untuk reservasi Anda.</p>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-xs sm:text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Reusable Calendar Component --}}
            <x-customer.booking-calendar />

            {{-- Policy Notice --}}
            <div class="mt-6">
                <x-customer.booking-policy />
            </div>
        </section>

        {{-- Removed sidebar, auto-submit on selection --}}
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
