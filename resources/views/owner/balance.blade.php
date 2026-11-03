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

    {{-- Rekening Penarikan (BQ-123) --}}
    <div x-data="{
        accountModalOpen: @js($errors->has('bank_kode') || $errors->has('nomor_rekening') || $errors->has('nama_pemilik_rekening') || ($errors->has('current_password') && old('_action') === 'save_account')),
        deleteModalOpen: @js($errors->has('current_password') && old('_action') === 'delete_account'),
        isEdit: @js(old('is_edit') === '1'),
        formUrl: @js(old('form_url', route('owner.payout-accounts.store'))),
        deleteUrl: @js(old('delete_url', '')),
        bankKode: @js(old('bank_kode', '')),
        nomorRekening: @js(old('nomor_rekening', '')),
        namaPemilik: @js(old('nama_pemilik_rekening', '')),
        openCreate() {
            this.isEdit = false;
            this.formUrl = '{{ route('owner.payout-accounts.store') }}';
            this.bankKode = '';
            this.nomorRekening = '';
            this.namaPemilik = '';
            this.accountModalOpen = true;
        },
        openEdit(account) {
            this.isEdit = true;
            this.formUrl = '/owner/payout-accounts/' + account.id;
            this.bankKode = account.bank_kode;
            this.nomorRekening = account.nomor_rekening;
            this.namaPemilik = account.nama_pemilik_rekening;
            this.accountModalOpen = true;
        },
        openDelete(account) {
            this.deleteUrl = '/owner/payout-accounts/' + account.id;
            this.deleteModalOpen = true;
        }
    }" class="space-y-4">
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 sm:p-8 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-bq-border pb-5">
                <div>
                    <h3 class="text-base font-bold text-bq-text">Rekening Penarikan</h3>
                    <p class="mt-0.5 text-xs text-bq-text-muted">Kelola rekening bank tujuan untuk mencairkan saldo Anda (maksimal 3 rekening tersimpan).</p>
                </div>
                @if ($payoutAccounts->count() < (int) config('payouts.max_accounts', 3))
                    <button
                        type="button"
                        @click="openCreate()"
                        class="inline-flex items-center gap-1.5 self-start sm:self-auto rounded-xl bg-bq-primary px-4 py-2 text-xs font-bold text-white hover:bg-bq-primary-hover shadow-xs transition"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Rekening</span>
                    </button>
                @endif
            </div>

            @if ($payoutAccounts->isEmpty())
                <div class="py-10 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 mb-3">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-bq-text">Belum ada rekening penarikan</p>
                    <p class="mt-1 text-xs text-bq-text-muted max-w-sm mx-auto">Tambahkan rekening bank untuk mulai mengajukan penarikan dana (withdraw) saldo Anda.</p>
                    <button
                        type="button"
                        @click="openCreate()"
                        class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-bq-primary px-4 py-2 text-xs font-bold text-white hover:bg-bq-primary-hover shadow-xs transition"
                    >
                        <span>Tambah Rekening Pertama</span>
                    </button>
                </div>
            @else
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($payoutAccounts as $acc)
                        <div class="relative rounded-2xl border {{ $acc->is_default ? 'border-indigo-300 bg-indigo-50/30' : 'border-bq-border bg-white' }} p-4 shadow-2xs flex flex-col justify-between">
                            <div>
                                <div class="flex items-start justify-between gap-2">
                                    <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-extrabold text-slate-800 tracking-wide">
                                        {{ $acc->bank_nama }}
                                    </span>
                                    @if ($acc->is_default)
                                        <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-[11px] font-bold text-indigo-700">
                                            Utama
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-3 font-mono text-base font-bold text-slate-900 tracking-wider">
                                    {{ $acc->masked_number }}
                                </p>
                                <p class="mt-1 text-xs font-medium text-slate-600 truncate">
                                    a.n. {{ $acc->nama_pemilik_rekening }}
                                </p>
                            </div>

                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                                <div>
                                    @if (! $acc->is_default)
                                        <form method="POST" action="{{ route('owner.payout-accounts.default', $acc->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="font-semibold text-indigo-600 hover:text-indigo-800">
                                                Jadikan utama
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        @click="openEdit(@js($acc))"
                                        class="font-semibold text-slate-600 hover:text-slate-900"
                                    >
                                        Ubah
                                    </button>
                                    <button
                                        type="button"
                                        @click="openDelete(@js($acc))"
                                        class="font-semibold text-rose-600 hover:text-rose-800"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Dialog Form Rekening (Tambah / Ubah) --}}
        <div
            x-show="accountModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="accountModalOpen = false"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-100" @click.stop>
                <h3 class="text-base font-bold text-slate-900" x-text="isEdit ? 'Ubah Rekening Penarikan' : 'Tambah Rekening Penarikan'"></h3>
                <p class="mt-1 text-xs text-slate-500">Pastikan rekening atas nama Anda atau pemilik bisnis agar pencairan berjalan lancar.</p>

                <form :action="formUrl" method="POST" class="mt-4 space-y-3.5">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <input type="hidden" name="_action" value="save_account">
                    <input type="hidden" name="is_edit" :value="isEdit ? '1' : '0'">
                    <input type="hidden" name="form_url" :value="formUrl">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Bank</label>
                        <select name="bank_kode" x-model="bankKode" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            <option value="">-- Pilih Bank --</option>
                            @foreach (config('banks', []) as $code => $name)
                                <option value="{{ $code }}">{{ $name }} ({{ $code }})</option>
                            @endforeach
                        </select>
                        @error('bank_kode')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Rekening</label>
                        <input type="text" name="nomor_rekening" x-model="nomorRekening" inputmode="numeric" required placeholder="Contoh: 1234567890" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        @error('nomor_rekening')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Pemilik Rekening</label>
                        <input type="text" name="nama_pemilik_rekening" x-model="namaPemilik" required maxlength="150" placeholder="Nama lengkap sesuai buku tabungan" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <p class="mt-1 text-[11px] text-slate-500">Sebaiknya sama dengan nama pemilik bisnis agar mudah diverifikasi admin.</p>
                        @error('nama_pemilik_rekening')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3 text-left">
                        <label class="block text-xs font-semibold text-amber-950 mb-1">Password Saat Ini</label>
                        <input type="password" name="current_password" required placeholder="Masukkan password akun Anda" class="w-full rounded-xl border border-amber-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        @if ($errors->has('current_password') && old('_action') === 'save_account')
                            <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $errors->first('current_password') }}</p>
                        @endif
                    </div>

                    <div class="mt-5 flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="accountModalOpen = false" class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-bold text-white hover:bg-bq-primary-hover shadow-xs">
                            Simpan Rekening
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Dialog Konfirmasi Hapus Rekening (dengan konfirmasi password) --}}
        <div
            x-show="deleteModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="deleteModalOpen = false"></div>
            <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl border border-slate-100 text-left" @click.stop>
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 mb-3">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-center text-slate-900">Hapus Rekening Penarikan?</h3>
                <p class="mt-1 text-xs text-center text-slate-600">Rekening ini tidak dapat digunakan lagi untuk withdraw setelah dihapus.</p>

                <form :action="deleteUrl" method="POST" class="mt-4 space-y-3">
                    @csrf
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="_action" value="delete_account">
                    <input type="hidden" name="delete_url" :value="deleteUrl">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Password Saat Ini</label>
                        <input type="password" name="current_password" required placeholder="Masukkan password Anda" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20">
                        @if ($errors->has('current_password') && old('_action') === 'delete_account')
                            <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $errors->first('current_password') }}</p>
                        @endif
                    </div>

                    <div class="mt-4 flex items-center justify-end gap-2">
                        <button type="button" @click="deleteModalOpen = false" class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-700 shadow-xs">
                            Hapus Rekening
                        </button>
                    </div>
                </form>
            </div>
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
        <form method="POST" action="{{ route('owner.payouts.request') }}" x-data="{ submitting: false }" @submit="submitting = true" class="mt-5 space-y-4">
            @csrf
            <input type="hidden" name="request_key" value="{{ old('request_key', $requestKey) }}">

            <div>
                <label class="text-xs font-semibold text-bq-text">Nominal Penarikan (Rp)
                    <input type="number" name="jumlah" min="10000" step="1" required value="{{ old('jumlah') }}" placeholder="Minimal 10000" class="mt-1.5 w-full sm:max-w-md rounded-xl border border-bq-border px-3.5 py-2 text-sm">
                </label>
            </div>

            <div>
                <span class="block text-xs font-semibold text-bq-text mb-2">Pilih Rekening Tujuan</span>
                @if ($payoutAccounts->isEmpty())
                    <p class="text-xs text-rose-600 font-medium">Belum ada rekening penarikan tersimpan. Silakan tambahkan rekening di atas terlebih dahulu.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach ($payoutAccounts as $acc)
                            <label class="relative flex cursor-pointer rounded-xl border border-slate-200 bg-white p-3 hover:border-indigo-400 focus:outline-none">
                                <input type="radio" name="payout_account_id" value="{{ $acc->id }}" class="mt-0.5 text-indigo-600 focus:ring-indigo-500" {{ (string) old('payout_account_id', $payoutAccounts->firstWhere('is_default', true)?->id ?? $payoutAccounts->first()?->id) === (string) $acc->id ? 'checked' : '' }} required>
                                <span class="ml-2.5 flex flex-col">
                                    <span class="text-xs font-bold text-slate-900">{{ $acc->bank_nama }} {{ $acc->masked_number }}</span>
                                    <span class="text-[11px] text-slate-500">a.n. {{ $acc->nama_pemilik_rekening }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('payout_account_id')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit" :disabled="submitting" @disabled($payoutAccounts->isEmpty()) class="inline-flex items-center gap-2 rounded-xl bg-bq-primary px-6 py-2.5 text-xs font-bold text-white hover:bg-bq-primary-hover shadow-sm transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <span x-text="submitting ? 'Memproses…' : 'Ajukan Withdraw'">Ajukan Withdraw</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Payouts Table --}}
    <div class="rounded-2xl border border-bq-border bg-bq-surface shadow-xs overflow-hidden">
        <div class="p-5 border-b border-bq-border flex items-center justify-between">
            <div>
                <h3 class="font-bold text-sm text-bq-text">Riwayat Withdraw</h3>
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

        @if ($payouts->hasPages())
            <div class="p-4 border-t border-bq-border">
                {{ $payouts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
