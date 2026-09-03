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
