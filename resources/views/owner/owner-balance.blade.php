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
            <p class="mt-2 text-2xl font-extrabold text-[#4F46E5]">Rp {{ number_format($availableBalance, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-emerald-600 font-medium">Siap ditarik ke rekening bank</p>
        </div>
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
            <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Pending Settlement</span>
            <p class="mt-2 text-2xl font-bold text-bq-text">Rp {{ number_format($pendingSettlement, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-bq-text-muted">Menunggu pembayaran pelanggan</p>
        </div>
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
            <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Total Earnings</span>
            <p class="mt-2 text-2xl font-bold text-bq-text">Rp {{ number_format($totalEarnings, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-bq-text-muted">Akumulasi pendapatan bersih reservasi</p>
        </div>
    </div>

    {{-- Withdrawal Action Card --}}
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
        <div class="mt-6 flex justify-center">
            <a href="{{ route('owner.settings.payment-setting') }}" class="inline-flex items-center gap-2 rounded-xl bg-bq-primary px-6 py-2.5 text-xs font-bold text-white hover:bg-bq-primary-hover shadow-sm transition">
                <span>Kelola Pembayaran &amp; Tarik Saldo</span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </div>

    {{-- Payouts Table --}}
    <div class="rounded-2xl border border-bq-border bg-bq-surface shadow-xs overflow-hidden">
        <div class="p-5 border-b border-bq-border flex items-center justify-between">
            <div>
                <h3 class="font-bold text-sm text-bq-text">Riwayat Penarikan Dana Terbaru</h3>
                <p class="text-xs text-bq-text-muted mt-0.5">Catatan transfer saldo platform ke rekening Anda</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-bq-border bg-bq-background/60 font-semibold uppercase tracking-wider text-bq-text-muted">
                        <th class="px-5 py-3.5">Waktu Pengajuan</th>
                        <th class="px-5 py-3.5">Nominal Penarikan</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bq-border">
                    @forelse ($payouts as $p)
                        <tr class="hover:bg-bq-background/40 transition">
                            <td class="px-5 py-4 whitespace-nowrap font-mono text-bq-text-muted">
                                {{ $p->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap font-bold text-emerald-700">
                                Rp {{ number_format($p->jumlah, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $p->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ ucfirst($p->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-xs text-bq-text-muted">
                                Belum ada riwayat penarikan dana.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
