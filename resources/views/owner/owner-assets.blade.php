@extends('layouts.owner-layout')

@section('title', 'Assets Management')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    @include('components.owner.page-header', [
        'judul' => 'Assets & Media Library',
        'subjudul' => 'Kelola seluruh file media, logo bisnis, foto cover, dan gambar katalog layanan.',
    ])

    <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 sm:p-8 text-center shadow-xs">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-[#4F46E5] mb-4">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </div>
        <h3 class="text-lg font-bold text-bq-text">Galeri &amp; Manajemen Aset Media</h3>
        <p class="mx-auto mt-2 max-w-lg text-sm text-bq-text-muted">
            Pusat penyimpanan media gambar Logo, Cover Banner, Service Gallery, dan Landing Page untuk mempercantik profil booking Anda.
        </p>
    </div>
</div>
@endsection
