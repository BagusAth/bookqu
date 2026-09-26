@extends('layouts.owner-layout')

@section('title', 'Schedule Report')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">

    @php
        $currentPeriod = $period ?? 'all';
        $periodsList = [
            'all' => 'Semua',
            'today' => 'Hari Ini',
            'this_week' => 'Minggu Ini',
            'this_month' => 'Bulan Ini',
            'last_30_days' => '30 Hari Terakhir',
        ];
    @endphp

    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="inline-flex items-center gap-2 mb-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800">Fitur Sedang Dikembangkan</span>
                <span class="text-xs text-[#6e6584] font-medium">Laporan Okupansi &amp; Utilisasi Jadwal</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-[#231a3d] sm:text-3xl">Schedule Report &amp; Utilization</h1>
            <p class="mt-1 text-xs sm:text-sm text-[#6e6584] leading-relaxed">
                Analisis efisiensi slot jadwal, okupansi, dan Utilization Rate operasional bisnis Anda.
            </p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('owner.schedule-report.export', ['period' => $currentPeriod]) }}"
               class="craft-btn inline-flex items-center gap-2 rounded-xl bg-[#382186] px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-xs hover:bg-[#2d1a6d] transition cursor-pointer">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Export CSV</span>
            </a>
            <a href="{{ route('owner.schedule') }}"
               class="craft-btn inline-flex items-center gap-2 rounded-xl border border-[#e7e2f7] bg-white px-4 py-2.5 text-xs sm:text-sm font-bold text-[#231a3d] hover:bg-[#f7f7fa] shadow-2xs transition">
                <span>Kelola Jadwal</span>
            </a>
        </div>
    </div>

    {{-- Period Filter Tabs --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar">
        @foreach($periodsList as $pKey => $pLabel)
            <a href="{{ route('owner.schedule-report', ['period' => $pKey]) }}"
               class="whitespace-nowrap rounded-xl px-4 py-2 text-xs font-bold transition-all {{ $currentPeriod === $pKey ? 'bg-[#382186] text-white shadow-xs' : 'border border-[#e7e2f7] bg-white text-[#6e6584] hover:text-[#231a3d] hover:bg-[#f7f7fa]' }}">
                {{ $pLabel }}
            </a>
        @endforeach
    </div>

    {{-- Key Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Utilization Rate --}}
        <div class="rounded-2xl border border-[#e7e2f7] bg-white p-5 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-[#6e6584] uppercase tracking-wider">Utilization Rate</p>
                <p class="text-2xl sm:text-3xl font-black text-[#382186] mt-1">{{ $utilizationRate ?? 0 }}%</p>
                <p class="text-[11px] text-[#6e6584] mt-0.5">Tingkat okupansi slot</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#EEF2FF] text-[#4F46E5]">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
        </div>

        {{-- Total Slots --}}
        <div class="rounded-2xl border border-[#e7e2f7] bg-white p-5 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-[#6e6584] uppercase tracking-wider">Total Slots</p>
                <p class="text-2xl sm:text-3xl font-black text-[#231a3d] mt-1">{{ number_format($totalSlots ?? 0) }}</p>
                <p class="text-[11px] text-[#6e6584] mt-0.5">Kapasitas slot dibuat</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>

        {{-- Booked Slots --}}
        <div class="rounded-2xl border border-[#e7e2f7] bg-white p-5 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-[#6e6584] uppercase tracking-wider">Booked Slots</p>
                <p class="text-2xl sm:text-3xl font-black text-emerald-600 mt-1">{{ number_format($bookedSlots ?? 0) }}</p>
                <p class="text-[11px] text-[#6e6584] mt-0.5">Slot berhasil terisi</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        </div>

        {{-- Available Slots --}}
        <div class="rounded-2xl border border-[#e7e2f7] bg-white p-5 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-[#6e6584] uppercase tracking-wider">Available Slots</p>
                <p class="text-2xl sm:text-3xl font-black text-indigo-600 mt-1">{{ number_format($availableSlots ?? 0) }}</p>
                <p class="text-[11px] text-[#6e6584] mt-0.5">Slot kosong tersedia</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Secondary Metrics & Insights --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Peak & Low Hour --}}
        <div class="rounded-2xl border border-[#e7e2f7] bg-white p-5 shadow-xs">
            <h3 class="text-sm font-bold text-[#231a3d] mb-3 flex items-center gap-2">
                <svg class="h-4 w-4 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Analisis Jam Operasional</span>
            </h3>
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="rounded-xl bg-[#f7f7fa] p-3 border border-[#e7e2f7]">
                    <span class="text-[#6e6584] block mb-1">Jam Terpadat (Peak)</span>
                    <strong class="text-sm text-[#231a3d]">
                        {{ $peakHour !== null ? sprintf('%02d:00 WIB', $peakHour) : '-' }}
                    </strong>
                </div>
                <div class="rounded-xl bg-[#f7f7fa] p-3 border border-[#e7e2f7]">
                    <span class="text-[#6e6584] block mb-1">Jam Lengang (Low)</span>
                    <strong class="text-sm text-[#231a3d]">
                        {{ $lowHour !== null ? sprintf('%02d:00 WIB', $lowHour) : '-' }}
                    </strong>
                </div>
            </div>
        </div>

        {{-- Peak & Low Day --}}
        <div class="rounded-2xl border border-[#e7e2f7] bg-white p-5 shadow-xs">
            <h3 class="text-sm font-bold text-[#231a3d] mb-3 flex items-center gap-2">
                <svg class="h-4 w-4 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>Analisis Hari Operasional</span>
            </h3>
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="rounded-xl bg-[#f7f7fa] p-3 border border-[#e7e2f7]">
                    <span class="text-[#6e6584] block mb-1">Hari Terpadat (Peak)</span>
                    <strong class="text-sm text-[#231a3d]">{{ $peakDay ?? '-' }}</strong>
                </div>
                <div class="rounded-xl bg-[#f7f7fa] p-3 border border-[#e7e2f7]">
                    <span class="text-[#6e6584] block mb-1">Hari Lengang (Low)</span>
                    <strong class="text-sm text-[#231a3d]">{{ $lowDay ?? '-' }}</strong>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
