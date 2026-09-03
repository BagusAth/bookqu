@extends('layouts.owner-layout')

@section('title', 'Appearance Setting')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    @include('components.owner.page-header', [
        'judul' => 'Appearance & Branding',
        'subjudul' => 'Kustomisasi tampilan visual, warna primer, tipografi, dan halaman pemesanan customer.',
    ])

    <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 sm:p-8 text-center shadow-xs">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-[#4F46E5] mb-4">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4 5 5 0 014-4h4a4 4 0 014 4 5 5 0 01-4 4H7zm0 0l2.5-5.5m7-10.5a3.5 3.5 0 115 5L12 21l-4.5-1 1-4.5 9.5-9.5z" />
            </svg>
        </div>
        <h3 class="text-lg font-bold text-bq-text">Pengaturan Tampilan &amp; Branding</h3>
        <p class="mx-auto mt-2 max-w-lg text-sm text-bq-text-muted">
            Atur tema warna, gaya kartu layanan, banner sampul, favicon, dan domain kustom untuk merepresentasikan identitas brand bisnis Anda.
        </p>
        <div class="mt-6 flex justify-center gap-3">
            <a href="{{ route('owner.landing-page') }}" class="rounded-xl bg-[#4F46E5] px-4 py-2 text-xs font-semibold text-white hover:bg-[#4338CA] transition">
                Buka Landing Page Editor &rarr;
            </a>
        </div>
    </div>
</div>
@endsection
