@extends('layouts.owner-layout')

@section('title', 'Balance & Payout')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    @include('components.owner.page-header', [
        'judul' => 'Balance & Payouts',
        'subjudul' => 'Kelola saldo dana yang diterima melalui platform payment dan lakukan penarikan (withdrawal).',
    ])

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
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

    {{-- Withdrawal Request (BQ-015): reviewed by the platform admin, transferred manually --}}
    <div id="withdraw-form" class="rounded-2xl border border-bq-border bg-bq-surface p-6 sm:p-8 shadow-xs">
        <h3 class="text-lg font-bold text-bq-text">Ajukan Penarikan Dana</h3>
        <p class="mt-1 text-sm text-bq-text-muted">Minimal Rp 10.000. Nominal langsung ditahan dari saldo; jika ditolak atau gagal, dana kembali ke saldo. Admin BookQu mentransfer manual dan mengunggah bukti.</p>
        @if (session('sukses'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('sukses') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('owner.payouts.request') }}" class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
            @csrf
            <label class="text-xs font-semibold text-bq-text">Nominal (Rp)
                <input type="number" name="jumlah" min="10000" step="1" required value="{{ old('jumlah') }}" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-sm">
            </label>
            <label class="text-xs font-semibold text-bq-text">Nama Bank
                <input type="text" name="bank_nama" maxlength="100" required value="{{ old('bank_nama') }}" placeholder="BCA" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-sm">
            </label>
            <label class="text-xs font-semibold text-bq-text">Nomor Rekening
                <input type="text" name="nomor_rekening" inputmode="numeric" pattern="[0-9]{5,30}" required value="{{ old('nomor_rekening') }}" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-sm">
            </label>
            <label class="text-xs font-semibold text-bq-text">Nama Pemilik Rekening
                <input type="text" name="nama_pemilik_rekening" maxlength="150" required value="{{ old('nama_pemilik_rekening') }}" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-sm">
            </label>
            <div class="sm:col-span-2">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-bq-primary px-6 py-2.5 text-xs font-bold text-white hover:bg-bq-primary-hover shadow-sm transition">Ajukan Withdraw</button>
            </div>
        </form>
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
                        <th class="px-5 py-3.5">Rekening Tujuan</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5">Keterangan</th>
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
                            <td class="px-5 py-4 whitespace-nowrap text-bq-text-muted">
                                {{ $p->bank_nama ?? '-' }} · {{ $p->nomor_rekening ?? '-' }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $p->status === 'success' ? 'bg-emerald-50 text-emerald-700' : (in_array($p->status, ['rejected', 'failed'], true) ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                                    {{ \App\Domain\Payout\PayoutState::label((string) $p->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-bq-text-muted">
                                @if ($p->transfer_reference)
                                    Ref: {{ $p->transfer_reference }}
                                @endif
                                @if ($p->proof_path)
                                    · <a href="{{ route('owner.payouts.proof', $p->id) }}" class="font-semibold text-[#4F46E5] hover:underline">Bukti transfer</a>
                                @endif
                                @if ($p->rejection_reason)
                                    <span class="text-red-600">{{ $p->rejection_reason }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-xs text-bq-text-muted">
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
