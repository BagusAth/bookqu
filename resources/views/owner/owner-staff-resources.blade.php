@extends('layouts.owner-layout')

@section('title', 'Staff & Resources')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{ tab: 'staff' }">
    @include('components.owner.page-header', [
        'judul' => 'Staff & Resources',
        'subjudul' => 'Kelola tim profesional dan aset operasional yang dibutuhkan untuk menjalankan layanan.',
    ])

    {{-- Tabs Navigation --}}
    <div class="flex border-b border-bq-border gap-6">
        <button
            @click="tab = 'staff'"
            class="pb-3 text-sm font-bold border-b-2 transition-all cursor-pointer flex items-center gap-2"
            :class="tab === 'staff' ? 'border-[#4F46E5] text-[#4F46E5]' : 'border-transparent text-bq-text-muted hover:text-bq-text'"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Staff Management
        </button>
        <button
            @click="tab = 'resources'"
            class="pb-3 text-sm font-bold border-b-2 transition-all cursor-pointer flex items-center gap-2"
            :class="tab === 'resources' ? 'border-[#4F46E5] text-[#4F46E5]' : 'border-transparent text-bq-text-muted hover:text-bq-text'"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            Physical Resources &amp; Rooms
        </button>
    </div>

    {{-- Staff Tab --}}
    <div x-show="tab === 'staff'" x-cloak class="rounded-2xl border border-bq-border bg-bq-surface p-6 sm:p-8 text-center shadow-xs">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-[#4F46E5] mb-4">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
        </div>
        <h3 class="text-lg font-bold text-bq-text">Kelola Staf &amp; Terapis / Instruktur</h3>
        <p class="mx-auto mt-2 max-w-lg text-sm text-bq-text-muted">
            Atur profil staf, jadwal kerja, komisi, dan layanan yang ditugaskan kepada masing-masing anggota tim.
        </p>
    </div>

    {{-- Resources Tab --}}
    <div x-show="tab === 'resources'" x-cloak class="rounded-2xl border border-bq-border bg-bq-surface p-6 sm:p-8 text-center shadow-xs">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-[#4F46E5] mb-4">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </div>
        <h3 class="text-lg font-bold text-bq-text">Aset Fisik, Lapangan, &amp; Ruangan</h3>
        <p class="mx-auto mt-2 max-w-lg text-sm text-bq-text-muted">
            Kelola ketersediaan aset fisik seperti Court 1, Court 2, Kursi Salon, atau Studio Room agar tidak terjadi bentrok pemakaian.
        </p>
    </div>
</div>
@endsection
