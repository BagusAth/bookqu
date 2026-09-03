@extends('layouts.owner-layout')

@section('title', 'Vouchers')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    @include('components.owner.page-header', [
        'judul' => 'Promo Vouchers & Discounts',
        'subjudul' => 'Buat kode promo, diskon persentase, atau potongan harga tetap untuk meningkatkan konversi booking.',
    ])

    <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 sm:p-8 text-center shadow-xs">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-[#4F46E5] mb-4">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
        </div>
        <h3 class="text-lg font-bold text-bq-text">Kupon &amp; Voucher Diskon</h3>
        <p class="mx-auto mt-2 max-w-lg text-sm text-bq-text-muted">
            Kelola promosi berkala seperti WELCOME20, Flash Sale, atau diskon loyalitas dengan batas kuota pemakaian dan masa berlaku.
        </p>
    </div>
</div>
@endsection
