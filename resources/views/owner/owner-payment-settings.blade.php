@extends('layouts.owner-layout')

@section('title', 'Payment Setting')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    @include('components.owner.page-header', [
        'judul' => 'Payment Gateway & Settings',
        'subjudul' => 'Atur bagaimana bisnis Anda menerima pembayaran dari customer (Platform Payment vs Akun Gateway Sendiri).',
    ])

    <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 sm:p-8 text-center shadow-xs">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-[#4F46E5] mb-4">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
        </div>
        <h3 class="text-lg font-bold text-bq-text">Mode Pembayaran Bisnis</h3>
        <p class="mx-auto mt-2 max-w-lg text-sm text-bq-text-muted">
            Pilih antara <strong>BookQu Platform Payment</strong> (dana otomatis masuk ke saldo untuk penarikan) atau <strong>Hubungkan Midtrans Sendiri (BYOPG)</strong> langsung ke rekening bisnis Anda.
        </p>
        <div class="mt-6 flex justify-center gap-3">
            <a href="{{ route('owner.settings') }}" class="rounded-xl bg-[#4F46E5] px-4 py-2 text-xs font-semibold text-white hover:bg-[#4338CA] transition">
                Konfigurasi Kunci Pembayaran &rarr;
            </a>
        </div>
    </div>
</div>
@endsection
