@extends('layouts.owner-layout')

@section('title', 'Reviews')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    @include('components.owner.page-header', [
        'judul' => 'Customer Reviews & Feedback',
        'subjudul' => 'Pantau kepuasan pelanggan, rating bintang, dan testimoni setelah sesi booking selesai.',
    ])

    <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 sm:p-8 text-center shadow-xs">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-[#4F46E5] mb-4">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </svg>
        </div>
        <h3 class="text-lg font-bold text-bq-text">Rating &amp; Ulasan Pelanggan</h3>
        <p class="mx-auto mt-2 max-w-lg text-sm text-bq-text-muted">
            Kelola ulasan customer, balas feedback, dan tampilkan ulasan terbaik di halaman profil booking publik usaha Anda.
        </p>
    </div>
</div>
@endsection
