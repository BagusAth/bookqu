@extends('layouts.owner-layout')

@section('title', 'Schedule Report')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    @include('components.owner.page-header', [
        'judul' => 'Schedule Report & Utilization',
        'subjudul' => 'Analisis seberapa optimal jadwal, kapasitas staf, dan resource bisnis Anda digunakan.',
    ])

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
            <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Utilization Rate</span>
            <p class="mt-2 text-3xl font-extrabold text-[#4F46E5]">84%</p>
            <p class="mt-1 text-xs text-emerald-600 font-medium">+6% vs bulan lalu</p>
        </div>
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
            <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Peak Hours</span>
            <p class="mt-2 text-2xl font-bold text-bq-text">14:00 – 19:00</p>
            <p class="mt-1 text-xs text-bq-text-muted">Permintaan tertinggi pada sesi sore &amp; malam</p>
        </div>
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
            <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Peak Days</span>
            <p class="mt-2 text-2xl font-bold text-bq-text">Jumat – Minggu</p>
            <p class="mt-1 text-xs text-bq-text-muted">Okupansi rata-rata mencapai 92%</p>
        </div>
    </div>

    <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 sm:p-8 text-center shadow-xs">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-[#4F46E5] mb-4">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-bq-text">Laporan Efisiensi Kapasitas Bisnis</h3>
        <p class="mx-auto mt-2 max-w-lg text-sm text-bq-text-muted">
            Modul ini menganalisis utilisasi staf dan resource untuk membantu Anda menetapkan strategi harga dinamis (*peak-hour pricing*) dan promosi di jam sepi.
        </p>
    </div>
</div>
@endsection
