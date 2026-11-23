@extends('layouts.admin-layout')

@section('title', 'Review withdraw')

@section('content')
    <x-owner.page-header title="Review withdraw owner"
        purpose="Setujui, catat transfer manual beserta bukti, atau tolak dengan alasan. Dana yang ditolak atau gagal otomatis kembali ke saldo owner.">
        <x-slot:action>
            <form method="GET" class="flex items-center gap-2">
                <label for="payout-status" class="sr-only">Status withdraw</label>
                <x-ui.select id="payout-status" name="status" class="w-44">
                    <option value="">Semua status</option>
                    @foreach (['pending', 'approved', 'success', 'failed', 'rejected'] as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ \App\Domain\Payout\PayoutState::label($option) }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.button type="submit" icon="filter">Terapkan</x-ui.button>
            </form>
        </x-slot:action>
    </x-owner.page-header>

    <x-ui.panel flush>
        @forelse ($payouts as $payout)
            @php
                $payoutTone = match ((string) $payout->status) {
                    'pending', 'approved' => 'warning',
                    'success' => 'success',
                    'failed', 'rejected' => 'danger',
                    default => 'neutral',
                };
            @endphp
            <article class="space-y-4 border-b border-bq-border p-4 last:border-b-0 md:p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 space-y-1">
                        <p class="font-semibold text-bq-ink">
                            {{ $payout->tenant?->namabisnis ?? 'Tenant #'.$payout->idtenant }}
                            @if ($payout->tenant?->user?->email)
                                <span class="font-normal text-bq-small text-bq-text-muted">· {{ $payout->tenant->user->email }}</span>
                            @endif
                        </p>
                        <p class="bq-num font-bq-display text-bq-figure-medium font-semibold text-bq-ink">Rp{{ number_format((float) $payout->jumlah, 0, ',', '.') }}</p>
                        <p class="text-bq-small text-bq-text-muted">Saldo bisnis saat ini <span class="bq-num font-semibold text-bq-text">Rp{{ number_format((float) ($payout->tenant?->saldo_platform ?? 0), 0, ',', '.') }}</span></p>
                    </div>
                    <x-ui.badge :tone="$payoutTone">{{ \App\Domain\Payout\PayoutState::label((string) $payout->status) }}</x-ui.badge>
                </div>

                <dl class="grid gap-x-6 gap-y-2 text-bq-body sm:grid-cols-2 lg:grid-cols-4">
                    <div class="min-w-0">
                        <dt class="text-bq-small text-bq-text-muted">Rekening tujuan</dt>
                        <dd>
                            {{ $payout->bank_nama ?? '-' }} · <span class="bq-num">{{ $payout->nomor_rekening ?? '-' }}</span>
                            @if ($payout->payoutAccount?->changed_at && $payout->requested_at && $payout->payoutAccount->changed_at >= $payout->requested_at->copy()->subHours(24))
                                <x-ui.badge tone="warning" class="ml-1">Rekening baru (&lt; 24 jam)</x-ui.badge>
                            @endif
                        </dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-bq-small text-bq-text-muted">Atas nama</dt>
                        <dd class="truncate" title="{{ $payout->nama_pemilik_rekening }}">{{ $payout->nama_pemilik_rekening ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-bq-small text-bq-text-muted">Diajukan</dt>
                        <dd class="bq-num">{{ optional($payout->requested_at)->format('d M Y, H:i') ?? '-' }}</dd>
                    </div>
                    @if ($payout->approved_at)
                        <div>
                            <dt class="text-bq-small text-bq-text-muted">Disetujui</dt>
                            <dd class="bq-num">{{ optional($payout->approved_at)->format('d M Y, H:i') }}</dd>
                        </div>
                    @endif
                    @if ($payout->transfer_reference)
                        <div class="min-w-0">
                            <dt class="text-bq-small text-bq-text-muted">Referensi transfer</dt>
                            <dd class="bq-num truncate" title="{{ $payout->transfer_reference }}">{{ $payout->transfer_reference }}</dd>
                        </div>
                    @endif
                    @if ($payout->rejection_reason)
                        <div class="min-w-0 sm:col-span-2">
                            <dt class="text-bq-small text-bq-text-muted">Alasan</dt>
                            <dd class="text-bq-danger">{{ $payout->rejection_reason }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($payout->status === 'pending')
                    <div class="flex flex-wrap items-end gap-3 border-t border-bq-border pt-4">
                        <form method="POST" action="{{ route('admin.payouts.approve', $payout->id) }}">
                            @csrf
                            <x-ui.button type="submit" variant="primary" icon="check">Setujui</x-ui.button>
                        </form>
                        <form method="POST" action="{{ route('admin.payouts.reject', $payout->id) }}" class="flex min-w-0 flex-1 flex-wrap items-end gap-2">
                            @csrf
                            <x-ui.field label="Alasan penolakan" for="reject-reason-{{ $payout->id }}" class="min-w-52 flex-1">
                                <x-ui.input id="reject-reason-{{ $payout->id }}" name="rejection_reason" required maxlength="500" />
                            </x-ui.field>
                            <x-ui.button type="submit" variant="danger">Tolak</x-ui.button>
                        </form>
                    </div>
                @elseif ($payout->status === 'approved')
                    <div class="space-y-4 border-t border-bq-border pt-4">
                        <form method="POST" action="{{ route('admin.payouts.complete', $payout->id) }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <x-ui.field label="Referensi transfer" for="transfer-reference-{{ $payout->id }}" class="min-w-52 flex-1"
                                hint="Isi referensi transfer, unggah bukti, atau keduanya.">
                                <x-ui.input id="transfer-reference-{{ $payout->id }}" name="transfer_reference" maxlength="150" />
                            </x-ui.field>
                            <x-ui.field label="Bukti transfer" for="proof-{{ $payout->id }}" class="min-w-52">
                                <input id="proof-{{ $payout->id }}" type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" class="text-bq-body">
                            </x-ui.field>
                            <x-ui.button type="submit" variant="primary" icon="check">Tandai berhasil</x-ui.button>
                        </form>
                        <form method="POST" action="{{ route('admin.payouts.fail', $payout->id) }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <x-ui.field label="Alasan transfer gagal" for="fail-reason-{{ $payout->id }}" class="min-w-52 flex-1">
                                <x-ui.input id="fail-reason-{{ $payout->id }}" name="rejection_reason" required maxlength="500" />
                            </x-ui.field>
                            <x-ui.button type="submit" variant="danger">Transfer gagal</x-ui.button>
                        </form>
                    </div>
                @endif
            </article>
        @empty
            <x-ui.empty icon="wallet" title="Belum ada pengajuan withdraw"
                description="{{ $status ? 'Tidak ada pengajuan dengan status ini. Pilih status lain atau tampilkan semua.' : 'Pengajuan pencairan saldo owner akan muncul di sini.' }}" />
        @endforelse
    </x-ui.panel>

    {{ $payouts->links() }}
@endsection
