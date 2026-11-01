<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Review Withdraw - BookQu</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_icon.png') }}">
    @vite('resources/css/app.css')
</head>
<body class="bg-slate-100 text-slate-900">
    <div class="min-h-screen">
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.dashboard') }}" class="text-lg font-bold text-[#4F46E5]">BookQu</a>
                <span class="text-slate-400">/</span>
                <span class="text-sm font-medium text-slate-600">Review Withdraw</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-rose-600 hover:underline">Logout</button>
            </form>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-10 space-y-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Review Withdraw Owner</h1>
                    <p class="mt-1 text-sm text-slate-500">Setujui, catat transfer manual beserta bukti, atau tolak dengan alasan. Dana yang ditolak/gagal otomatis kembali ke saldo owner.</p>
                </div>
                <form method="GET" class="flex items-center gap-2">
                    <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Semua status</option>
                        @foreach (['pending', 'approved', 'success', 'failed', 'rejected'] as $option)
                            <option value="{{ $option }}" @selected($status === $option)>{{ \App\Domain\Payout\PayoutState::label($option) }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-lg bg-[#4F46E5] px-4 py-2 text-sm font-semibold text-white">Filter</button>
                </form>
            </div>

            @if (session('sukses'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('sukses') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
            @endif

            <div class="space-y-4">
                @forelse ($payouts as $payout)
                    <div class="rounded-xl bg-white border border-slate-200 p-5 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="space-y-1 text-sm">
                                <p class="font-bold text-slate-900">{{ $payout->tenant?->namabisnis ?? 'Tenant #'.$payout->idtenant }}</p>
                                <p class="text-xl font-extrabold text-[#4F46E5]">Rp {{ number_format((float) $payout->jumlah, 0, ',', '.') }}</p>
                                <p class="text-slate-600">{{ $payout->bank_nama ?? '-' }} · {{ $payout->nomor_rekening ?? '-' }} · a.n. {{ $payout->nama_pemilik_rekening ?? '-' }}</p>
                                <p class="text-xs text-slate-400">Diajukan {{ optional($payout->requested_at)->format('d M Y, H:i') }}</p>
                                @if ($payout->transfer_reference)
                                    <p class="text-xs text-slate-500">Ref transfer: {{ $payout->transfer_reference }}</p>
                                @endif
                                @if ($payout->rejection_reason)
                                    <p class="text-xs text-rose-600">Alasan: {{ $payout->rejection_reason }}</p>
                                @endif
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ \App\Domain\Payout\PayoutState::label((string) $payout->status) }}</span>
                        </div>

                        @if ($payout->status === 'pending')
                            <div class="mt-4 flex flex-wrap gap-3 border-t border-slate-100 pt-4">
                                <form method="POST" action="{{ route('admin.payouts.approve', $payout->id) }}">
                                    @csrf
                                    <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Setujui</button>
                                </form>
                                <form method="POST" action="{{ route('admin.payouts.reject', $payout->id) }}" class="flex flex-wrap gap-2">
                                    @csrf
                                    <input name="rejection_reason" required maxlength="500" placeholder="Alasan penolakan" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <button class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white">Tolak</button>
                                </form>
                            </div>
                        @elseif ($payout->status === 'approved')
                            <div class="mt-4 flex flex-wrap gap-3 border-t border-slate-100 pt-4">
                                <form method="POST" action="{{ route('admin.payouts.complete', $payout->id) }}" enctype="multipart/form-data" class="flex flex-wrap gap-2">
                                    @csrf
                                    <input name="transfer_reference" required maxlength="150" placeholder="No. referensi transfer" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" class="text-sm">
                                    <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Tandai Berhasil</button>
                                </form>
                                <form method="POST" action="{{ route('admin.payouts.fail', $payout->id) }}" class="flex flex-wrap gap-2">
                                    @csrf
                                    <input name="rejection_reason" required maxlength="500" placeholder="Alasan gagal" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <button class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white">Transfer Gagal</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500">Belum ada pengajuan withdraw.</div>
                @endforelse
            </div>

            {{ $payouts->links() }}
        </main>
    </div>
</body>
</html>
