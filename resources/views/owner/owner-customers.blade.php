@extends('layouts.owner-layout')

@section('title', 'Customer List')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    @include('components.owner.page-header', [
        'judul' => 'Customer Database (CRM)',
        'subjudul' => 'Basis data relasi pelanggan, riwayat pemesanan, total belanja, dan catatan personal.',
    ])

    <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 sm:p-8 text-center shadow-xs">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-[#4F46E5] mb-4">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
        </div>
        <h3 class="text-lg font-bold text-bq-text">Database Pelanggan &amp; Riwayat Belanja</h3>
        <p class="mx-auto mt-2 max-w-lg text-sm text-bq-text-muted">
            Lihat profil pelanggan, total pengeluaran (*Lifetime Value*), riwayat kehadiran (*no-show tracking*), dan preferensi khusus setiap customer.
        </p>
    </div>
</div>
@endsection
