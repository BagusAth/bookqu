@extends('layouts.owner-layout')

@section('title', 'Additional Items')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    @include('components.owner.page-header', [
        'judul' => 'Additional Items (Add-ons)',
        'subjudul' => 'Kelola produk atau layanan tambahan yang dapat dibeli customer saat melakukan reservasi.',
    ])

    <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 sm:p-8 text-center shadow-xs">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-[#4F46E5] mb-4">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h3 class="text-lg font-bold text-bq-text">Produk &amp; Add-on Tambahan</h3>
        <p class="mx-auto mt-2 max-w-lg text-sm text-bq-text-muted">
            Tingkatkan *Average Order Value* dengan menawarkan add-on seperti Sewa Raket, Shuttlecock, Hair Mask, atau Suplemen saat customer memesan layanan utama.
        </p>
    </div>
</div>
@endsection
