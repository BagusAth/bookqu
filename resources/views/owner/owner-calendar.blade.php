@extends('layouts.owner-layout')

@section('title', 'Calendar')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    @include('components.owner.page-header', [
        'judul' => 'Calendar & Scheduling',
        'subjudul' => 'Visualisasikan seluruh jadwal booking, ketersediaan staf, dan resource secara real-time.',
    ])

    <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 sm:p-8 text-center shadow-xs">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-[#4F46E5] mb-4">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-bq-text">Operational Booking Calendar</h3>
        <p class="mx-auto mt-2 max-w-lg text-sm text-bq-text-muted">
            Tampilan kalender interaktif untuk mengelola jadwal harian, mingguan, dan bulanan serta alokasi staf dan resource tanpa risiko double booking.
        </p>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('owner.schedule') }}" class="rounded-xl border border-bq-border bg-bq-surface px-4 py-2 text-xs font-semibold text-bq-text hover:bg-bq-background transition">
                Kelola Slot Jadwal Saat Ini &rarr;
            </a>
            <a href="{{ route('owner.bookings') }}" class="rounded-xl bg-[#4F46E5] px-4 py-2 text-xs font-semibold text-white hover:bg-[#4338CA] transition">
                Lihat Daftar Transaksi Booking
            </a>
        </div>
    </div>
</div>
@endsection
