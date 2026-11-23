@extends('layouts.admin-layout')

@section('title', 'Transfer refund')

@section('content')
    <x-ui.button variant="ghost" size="sm" icon="chevron-left" :href="route('admin.refunds')">Kembali ke refund</x-ui.button>

    <x-owner.page-header title="Transfer refund #{{ $refund->id }}"
        purpose="Transfer jumlah ini ke rekening di bawah, lalu catat referensinya di halaman refund." />

    <x-ui.panel class="max-w-lg">
        <p class="bq-num font-bq-display text-bq-figure-small font-semibold text-bq-ink">{{ $refund->jumlah_formatted }}</p>
        <dl class="mt-4 grid grid-cols-[minmax(0,2fr)_minmax(0,3fr)] gap-x-4 gap-y-2 border-t border-bq-border pt-4 text-bq-body">
            <dt class="text-bq-text-muted">Bank</dt>
            <dd>{{ $account->bank_nama }}</dd>
            <dt class="text-bq-text-muted">Nomor rekening</dt>
            <dd class="bq-num font-semibold">{{ $account->nomor_rekening }}</dd>
            <dt class="text-bq-text-muted">Atas nama</dt>
            <dd>{{ $account->nama_pemilik_rekening }}</dd>
        </dl>
    </x-ui.panel>
@endsection
