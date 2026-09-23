@extends('layouts.owner-layout')

@section('title', 'Schedule Report')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">

    @include('components.owner.page-header', [
        'judul' => 'Schedule Report & Utilization',
        'subjudul' => 'Analisis efisiensi slot jadwal, okupansi, dan Utilization Rate operasional bisnis Anda.',
    ])

    {{-- ── LOCKED STATE HERO CARD ── --}}
    <div class="relative overflow-hidden rounded-3xl border border-amber-200/80 bg-gradient-to-b from-amber-50/60 via-white to-amber-50/30 p-8 sm:p-12 text-center shadow-sm">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 shadow-xs mb-5">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>

        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100/90 px-3 py-1 text-xs font-bold text-amber-800 uppercase tracking-wider border border-amber-300/50">
            <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
            Fitur Sedang Dikembangkan
        </span>

        <h2 class="text-2xl sm:text-3xl font-black text-[#231a3d] mt-4 tracking-tight">
            Laporan Okupansi &amp; Utilisasi Jadwal
        </h2>

        <p class="mt-3 text-sm text-[#6e6584] leading-relaxed max-w-lg mx-auto">
            Laporan analisis jam sibuk, tingkat okupansi slot, dan ekspor data jadwal saat ini sedang dipersiapkan dan akan segera tersedia pada pembaruan mendatang.
        </p>

        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('owner.dashboard') }}" class="craft-btn inline-flex items-center gap-2 rounded-xl bg-[#382186] px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-[#382186]/20 hover:bg-[#2d1a6d] transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Kembali ke Dashboard</span>
            </a>
            <a href="{{ route('owner.schedule') }}" class="craft-btn inline-flex items-center gap-2 rounded-xl border border-[#e7e2f7] bg-white px-5 py-2.5 text-xs font-bold text-[#231a3d] hover:bg-[#f7f7fa] shadow-2xs transition">
                <span>Kelola Jadwal &amp; Jam Operasional</span>
            </a>
        </div>

        {{-- Preview mockup cards (blurred decorative) --}}
        <div class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-4 opacity-40 filter blur-[1px] pointer-events-none select-none">
            <div class="rounded-2xl border border-[#e7e2f7] bg-white p-4 text-left">
                <p class="text-[11px] font-bold text-[#6e6584] uppercase">Tingkat Okupansi</p>
                <p class="text-xl font-black text-[#231a3d] mt-1">••%</p>
                <div class="mt-2 h-1.5 w-3/4 rounded-full bg-slate-200"></div>
            </div>
            <div class="rounded-2xl border border-[#e7e2f7] bg-white p-4 text-left">
                <p class="text-[11px] font-bold text-[#6e6584] uppercase">Jam Terpadat</p>
                <p class="text-xl font-black text-[#231a3d] mt-1">••:00 WIB</p>
                <div class="mt-2 h-1.5 w-1/2 rounded-full bg-slate-200"></div>
            </div>
            <div class="rounded-2xl border border-[#e7e2f7] bg-white p-4 text-left">
                <p class="text-[11px] font-bold text-[#6e6584] uppercase">Total Slot Aktif</p>
                <p class="text-xl font-black text-[#231a3d] mt-1">•• Slot</p>
                <div class="mt-2 h-1.5 w-2/3 rounded-full bg-slate-200"></div>
            </div>
        </div>
    </div>

</div>
@endsection
