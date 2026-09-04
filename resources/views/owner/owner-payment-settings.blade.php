@extends('layouts.owner-layout')

@section('title', 'Payment Settings')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    payoutModalOpen: false,
    paymentMode: '{{ $tenant->payment_mode ?? 'platform' }}',
    notification: '',
    showToast(msg) {
        this.notification = msg;
        setTimeout(() => this.notification = '', 3500);
    }
}">

    {{-- Toast Notification --}}
    <div x-show="notification"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-5 right-5 z-50 rounded-xl bg-slate-900 text-white px-4 py-3 shadow-xl text-xs font-medium flex items-center gap-2"
         style="display: none;">
        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
        <span x-text="notification"></span>
    </div>

    {{-- Flash messages --}}
    @if (session('sukses'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 flex items-center gap-2">
            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('sukses') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-disc pl-5 space-y-0.5 text-xs">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── Header ── --}}
    @include('components.owner.page-header', [
        'judul' => 'Payment Configuration & Balance',
        'subjudul' => 'Kelola metode pembayaran reservasi, saldo platform, penarikan dana, dan integrasi payment gateway.',
    ])

    {{-- ── Balance & Payout Banner ── --}}
    <div class="rounded-2xl border border-bq-border bg-gradient-to-br from-indigo-900 via-slate-900 to-indigo-950 p-6 text-white shadow-md">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="text-xs font-semibold text-indigo-300 uppercase tracking-widest">Saldo Platform Tersedia</span>
                <p class="text-3xl sm:text-4xl font-black mt-1 tracking-tight">
                    Rp {{ number_format($tenant->saldo_platform ?? 0, 0, ',', '.') }}
                </p>
                <div class="mt-2 flex items-center gap-3 text-xs text-slate-300">
                    <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-400"></span> Pembayaran Otomatis Aktif</span>
                    <span>•</span>
                    <span>Biaya Platform: Transparan &amp; Flat</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" @click="payoutModalOpen = true" class="rounded-xl bg-white px-5 py-2.5 text-xs font-bold text-slate-900 shadow-md hover:bg-slate-100 transition">
                    Tarik Saldo (Payout) &rarr;
                </button>
            </div>
        </div>
    </div>

    {{-- ── Gateway Configuration Mode ── --}}
    <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 shadow-xs space-y-6">
        <div>
            <h3 class="text-base font-bold text-bq-text">Metode Gateway Penerimaan Dana</h3>
            <p class="text-xs text-bq-text-muted mt-0.5">Tentukan akun gateway yang memproses transaksi pembayaran booking dari customer Anda.</p>
        </div>

        <form method="POST" action="/owner/settings/payment" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Mode 1: Platform Payment --}}
                <label class="relative flex cursor-pointer rounded-2xl border p-5 transition"
                       :class="paymentMode === 'platform' ? 'border-bq-primary bg-indigo-50/40 ring-2 ring-bq-primary/20' : 'border-bq-border hover:bg-slate-50'">
                    <input type="radio" name="payment_mode" value="platform" x-model="paymentMode" class="sr-only">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-sm text-bq-text">BookQu Platform Gateway</span>
                            <span class="rounded-full bg-indigo-100 text-indigo-800 text-[10px] font-bold px-2 py-0.5">Praktis (Default)</span>
                        </div>
                        <p class="text-xs text-bq-text-muted leading-relaxed">
                            Tidak perlu daftar akun Midtrans sendiri. Dana langsung tertampung di saldo BookQu dan dapat dicairkan kapan saja ke rekening bank Anda.
                        </p>
                    </div>
                </label>

                {{-- Mode 2: BYOPG --}}
                <label class="relative flex cursor-pointer rounded-2xl border p-5 transition"
                       :class="paymentMode === 'owner' ? 'border-bq-primary bg-indigo-50/40 ring-2 ring-bq-primary/20' : 'border-bq-border hover:bg-slate-50'">
                    <input type="radio" name="payment_mode" value="owner" x-model="paymentMode" class="sr-only">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-sm text-bq-text">Midtrans Gateway Sendiri (BYOPG)</span>
                            <span class="rounded-full bg-slate-100 text-slate-700 text-[10px] font-bold px-2 py-0.5">Custom API</span>
                        </div>
                        <p class="text-xs text-bq-text-muted leading-relaxed">
                            Hubungkan akun Midtrans Anda sendiri. Pembayaran dari customer langsung masuk ke merchant dashboard dan rekening Midtrans bisnis Anda.
                        </p>
                    </div>
                </label>
            </div>

            {{-- Custom Midtrans Credential Inputs (shown when owner mode) --}}
            <div x-show="paymentMode === 'owner'" class="rounded-xl border border-bq-border bg-slate-50/60 p-5 space-y-4" style="display: none;">
                <h4 class="text-xs font-bold uppercase tracking-wider text-bq-text-muted">Kredensial Midtrans Merchant</h4>
                
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Environment</label>
                        <select name="midtrans_environment" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text bg-white">
                            <option value="sandbox" {{ ($tenant->midtrans_environment ?? '') === 'sandbox' ? 'selected' : '' }}>Sandbox (Uji Coba)</option>
                            <option value="production" {{ ($tenant->midtrans_environment ?? '') === 'production' ? 'selected' : '' }}>Production (Live)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Merchant ID</label>
                        <input type="text" name="midtrans_sandbox_merchant_id" value="{{ $tenant->midtrans_sandbox_merchant_id ?? '' }}" placeholder="G123456789" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Client Key</label>
                        <input type="text" name="midtrans_sandbox_client_key" value="{{ $tenant->midtrans_sandbox_client_key ?? '' }}" placeholder="SB-Mid-client-..." class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Server Key</label>
                        <input type="password" name="midtrans_sandbox_server_key" value="{{ $tenant->midtrans_sandbox_server_key ?? '' }}" placeholder="SB-Mid-server-..." class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text bg-white">
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-2 border-t border-bq-border">
                <button type="submit" class="rounded-xl bg-bq-primary px-6 py-2.5 text-xs font-semibold text-white hover:bg-bq-primary-hover shadow-sm transition">
                    Simpan Pengaturan Pembayaran
                </button>
            </div>
        </form>
    </div>

    {{-- ── Payout History ── --}}
    <div class="rounded-2xl border border-bq-border bg-bq-surface shadow-xs overflow-hidden">
        <div class="p-5 border-b border-bq-border flex items-center justify-between">
            <div>
                <h3 class="font-bold text-sm text-bq-text">Riwayat Penarikan Dana (Payouts)</h3>
                <p class="text-xs text-bq-text-muted mt-0.5">Daftar permintaan transfer saldo ke rekening bank bisnis Anda</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-bq-border bg-bq-background/60 font-semibold uppercase tracking-wider text-bq-text-muted">
                        <th class="px-5 py-3.5">Tanggal Permintaan</th>
                        <th class="px-5 py-3.5">Jumlah Penarikan</th>
                        <th class="px-5 py-3.5">Rekening Tujuan</th>
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
                            <td class="px-5 py-4 whitespace-nowrap text-bq-text">
                                {{ $tenant->bank_nama ?? 'Bank BCA' }} • {{ $tenant->nomor_rekening ?? 'Rekening Terdaftar' }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $p->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ ucfirst($p->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-xs text-bq-text-muted">
                                Belum ada riwayat penarikan dana. Saldo yang terkumpul dapat ditarik sewaktu-waktu.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Payout Request Modal --}}
    <div x-show="payoutModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="payoutModalOpen = false">
            <h3 class="text-base font-bold text-bq-text">Tarik Saldo Platform</h3>
            <p class="text-xs text-bq-text-muted mt-1">Saldo saat ini: <strong class="text-emerald-700">Rp {{ number_format($tenant->saldo_platform ?? 0, 0, ',', '.') }}</strong></p>

            <form method="POST" action="/owner/payouts" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="text-xs font-semibold text-bq-text">Jumlah Penarikan (Rp)</label>
                    <input type="number" name="jumlah" required max="{{ $tenant->saldo_platform ?? 0 }}" placeholder="Minimal Rp 50.000" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Bank Tujuan</label>
                    <input type="text" name="bank_nama" required value="{{ $tenant->bank_nama ?? 'BCA' }}" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Nomor Rekening</label>
                    <input type="text" name="nomor_rekening" required value="{{ $tenant->nomor_rekening ?? '' }}" placeholder="Nomor rekening bank" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Nama Pemilik Rekening</label>
                    <input type="text" name="nama_pemilik_rekening" required value="{{ $tenant->nama_pemilik_rekening ?? '' }}" placeholder="Sesuai buku tabungan" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-bq-border">
                    <button type="button" @click="payoutModalOpen = false" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Ajukan Penarikan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
