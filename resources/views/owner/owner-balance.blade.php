@extends('layouts.owner-layout')

@section('title', 'Balance & Payout')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    @include('components.owner.page-header', [
        'judul' => 'Balance & Payouts',
        'subjudul' => 'Kelola saldo dana yang diterima melalui platform payment dan lakukan penarikan (withdrawal).',
    ])

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
            <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Available Balance</span>
            <p class="mt-2 text-2xl font-extrabold text-[#4F46E5]">Rp 5.250.000</p>
            <p class="mt-1 text-xs text-emerald-600 font-medium">Siap ditarik ke rekening bank</p>
        </div>
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
            <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Pending Settlement</span>
            <p class="mt-2 text-2xl font-bold text-bq-text">Rp 850.000</p>
            <p class="mt-1 text-xs text-bq-text-muted">Menunggu kliring pembayaran (H+1)</p>
        </div>
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
            <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Total Earnings</span>
            <p class="mt-2 text-2xl font-bold text-bq-text">Rp 18.500.000</p>
            <p class="mt-1 text-xs text-bq-text-muted">Akumulasi pendapatan bersih platform</p>
        </div>
    </div>

    <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 sm:p-8 text-center shadow-xs">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-[#4F46E5] mb-4">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
        </div>
        <h3 class="text-lg font-bold text-bq-text">Penarikan Dana (Withdrawal)</h3>
        <p class="mx-auto mt-2 max-w-lg text-sm text-bq-text-muted">
            Tarik saldo pendapatan reservasi langsung ke rekening bank terdaftar Anda dengan proses verifikasi instan.
        </p>
    </div>
</div>
@endsection
