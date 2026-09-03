@extends('layouts.owner-layout')

@section('title', 'Categories')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    @include('components.owner.page-header', [
        'judul' => 'Service Categories',
        'subjudul' => 'Kelompokkan layanan bisnis agar katalog reservasi tertata rapi dan mudah ditemukan customer.',
    ])

    <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 sm:p-8 text-center shadow-xs">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-[#4F46E5] mb-4">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-bq-text">Kategori Layanan</h3>
        <p class="mx-auto mt-2 max-w-lg text-sm text-bq-text-muted">
            Atur kategori seperti Hair, Treatment, Court Rental, dsb. Kategori mempermudah customer memfilter layanan pada halaman booking.
        </p>
        <div class="mt-6">
            <a href="{{ route('owner.programs') }}" class="rounded-xl bg-[#4F46E5] px-4 py-2 text-xs font-semibold text-white hover:bg-[#4338CA] transition">
                Buka Katalog Services &rarr;
            </a>
        </div>
    </div>
</div>
@endsection
