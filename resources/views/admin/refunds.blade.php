@extends('layouts.admin-layout')

@section('title', 'Refund')

@section('content')
    <x-owner.page-header title="Refund"
        purpose="Transfer refund ke rekening pelanggan, lalu catat referensinya atau alasan jika gagal. Pelanggan dan owner diberi tahu otomatis." />

    <x-ui.panel flush>
        @forelse ($refunds as $refund)
            <article class="space-y-4 border-b border-bq-border p-4 last:border-b-0 md:p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 space-y-1">
                        <h2 class="font-semibold text-bq-ink">Refund #{{ $refund->id }}</h2>
                        <p class="bq-num font-bq-display text-bq-figure-medium font-semibold text-bq-ink">{{ $refund->jumlah_formatted }}</p>
                        <p class="text-bq-small text-bq-text-muted">Pembayaran #{{ $refund->payment_id }}</p>
                    </div>
                    <x-owner.status-badge type="refund" :status="$refund->status" />
                </div>

                <dl class="grid gap-x-6 gap-y-2 text-bq-body sm:grid-cols-2">
                    <div class="min-w-0">
                        <dt class="text-bq-small text-bq-text-muted">Rekening pelanggan</dt>
                        <dd class="bq-num">{{ $refund->refundRequest?->maskedAccount() ?? 'Belum tersedia' }}</dd>
                    </div>
                    @if ($refund->refund_reference)
                        <div class="min-w-0">
                            <dt class="text-bq-small text-bq-text-muted">Referensi transfer</dt>
                            <dd class="bq-num truncate" title="{{ $refund->refund_reference }}">{{ $refund->refund_reference }}</dd>
                        </div>
                    @endif
                    @if ($refund->status === 'failed' && $refund->catatan)
                        <div class="min-w-0 sm:col-span-2">
                            <dt class="text-bq-small text-bq-text-muted">Alasan gagal</dt>
                            <dd class="text-bq-danger">{{ $refund->catatan }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($refund->status === 'pending')
                    <div class="space-y-4 border-t border-bq-border pt-4">
                        @if ($refund->refundRequest)
                            <form method="POST" action="{{ route('admin.refunds.transfer', $refund) }}">
                                @csrf
                                <x-ui.button type="submit" icon="external">Buka detail untuk transfer</x-ui.button>
                            </form>
                        @else
                            <x-ui.banner tone="warning" title="Rekening belum tersedia" description="Hubungi pelanggan sebelum mentransfer." />
                        @endif
                        <form method="POST" action="{{ route('admin.refunds.update', $refund) }}" class="flex flex-wrap items-end gap-2">
                            @csrf @method('PATCH')
                            <x-ui.field label="Referensi transfer" for="refund-reference-{{ $refund->id }}" class="min-w-52 flex-1">
                                <x-ui.input id="refund-reference-{{ $refund->id }}" name="reference" required maxlength="100" />
                            </x-ui.field>
                            <x-ui.button type="submit" variant="primary" name="status" value="processed" icon="check">Tandai sudah ditransfer</x-ui.button>
                        </form>
                        <form method="POST" action="{{ route('admin.refunds.update', $refund) }}" class="flex flex-wrap items-end gap-2">
                            @csrf @method('PATCH')
                            <x-ui.field label="Alasan gagal" for="refund-reason-{{ $refund->id }}" class="min-w-52 flex-1">
                                <x-ui.input id="refund-reason-{{ $refund->id }}" name="reason" required maxlength="500" />
                            </x-ui.field>
                            <x-ui.button type="submit" variant="danger" name="status" value="failed">Tandai gagal</x-ui.button>
                        </form>
                    </div>
                @elseif ($refund->status === 'failed')
                    <form method="POST" action="{{ route('admin.refunds.update', $refund) }}" class="border-t border-bq-border pt-4">
                        @csrf @method('PATCH')
                        <x-ui.button type="submit" name="status" value="pending">Siapkan percobaan ulang</x-ui.button>
                    </form>
                @endif
            </article>
        @empty
            <x-ui.empty icon="card" title="Belum ada refund" description="Refund yang disetujui owner akan muncul di sini untuk ditransfer." />
        @endforelse
    </x-ui.panel>

    {{ $refunds->links() }}
@endsection
