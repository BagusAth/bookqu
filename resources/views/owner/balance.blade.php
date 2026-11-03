@extends('layouts.owner-layout')

@section('title', 'Saldo')

@section('content')
<div
    x-data="{
        accountModalOpen: @js($errors->has('bank_kode') || $errors->has('nomor_rekening') || $errors->has('nama_pemilik_rekening') || ($errors->has('current_password') && old('_action') === 'save_account')),
        deleteModalOpen: @js($errors->has('current_password') && old('_action') === 'delete_account'),
        withdrawModalOpen: @js($errors->has('jumlah') || $errors->has('payout_account_id') || $errors->has('request_key')),
        step: 1,
        amount: @js(old('jumlah', '')),
        accountId: @js(old('payout_account_id', (string) ($payoutAccounts->firstWhere('is_default', true)?->id ?? $payoutAccounts->first()?->id ?? ''))),
        isEdit: @js(old('is_edit') === '1'),
        formUrl: @js(old('form_url', route('owner.payout-accounts.store'))),
        deleteUrl: @js(old('delete_url', '')),
        bankKode: @js(old('bank_kode', '')),
        nomorRekening: @js(old('nomor_rekening', '')),
        namaPemilik: @js(old('nama_pemilik_rekening', '')),
        accounts: @js($payoutAccounts),
        availableBalance: {{ (int) $availableBalance }},
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
        },
        openWithdraw() {
            this.step = 1;
            this.withdrawModalOpen = true;
        },
        tarikSemua() {
            this.amount = this.availableBalance;
        },
        selectedAccount() {
            return this.accounts.find(a => String(a.id) === String(this.accountId)) || null;
        },
        formattedAmount() {
            const n = parseInt(this.amount, 10);
            return isNaN(n) ? '0' : new Intl.NumberFormat('id-ID').format(n);
        },
        canProceed() {
            const n = parseInt(this.amount, 10);
            return !isNaN(n) && n >= 10000 && n <= this.availableBalance && this.accountId;
        }
    }"
    class="mx-auto max-w-7xl space-y-6"
>
    @include('components.owner.page-header', [
        'judul' => 'Saldo',
        'subjudul' => 'Kelola saldo hasil booking dan tarik ke rekening Anda.',
    ])

    @if (session('sukses'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('sukses') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    @if (! $isEligible)
        <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4 sm:p-5 text-xs text-amber-900 flex items-start gap-3 shadow-2xs">
            <svg class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <p class="font-bold text-amber-900 text-sm">Belum dapat mengajukan withdraw</p>
                <p class="mt-0.5 text-amber-800">{{ $eligibilityReasons[0] }}</p>
                @if ($eligibilityReasons[0] === 'Lengkapi profil bisnis terlebih dahulu.')
                    <a href="{{ route('owner.settings.profile') }}" class="mt-1.5 inline-block font-bold text-indigo-600 hover:underline">Lengkapi profil bisnis &rarr;</a>
                @endif
            </div>
        </div>
    @endif

    {{-- Kartu Ringkasan Saldo --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs flex flex-col justify-between">
            <div>
                <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Saldo Tersedia</span>
                <p class="mt-2 text-2xl font-extrabold text-[#4F46E5]">Rp {{ number_format($availableBalance, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-emerald-600 font-medium">Siap ditarik ke rekening bank</p>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100">
                <button
                    type="button"
                    @click="openWithdraw()"
                    @disabled(! $isEligible)
                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-bq-primary px-4 py-2.5 text-xs font-bold text-white hover:bg-bq-primary-hover shadow-xs transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tarik Saldo</span>
                </button>
                @if (! $isEligible)
                    <p class="mt-1.5 text-[11px] text-amber-700 font-medium truncate" title="{{ $eligibilityReasons[0] }}">
                        {{ $eligibilityReasons[0] }}
                    </p>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
            <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Sedang Diproses</span>
            <p class="mt-2 text-2xl font-bold text-bq-text">Rp {{ number_format($inFlightPayouts, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-bq-text-muted">Pengajuan withdraw dalam proses review & transfer</p>
        </div>

        <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs">
            <span class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Total Pendapatan</span>
            <p class="mt-2 text-2xl font-bold text-bq-text">Rp {{ number_format($totalEarnings, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-bq-text-muted">Akumulasi pendapatan bersih reservasi</p>
        </div>
    </div>

    {{-- Rekening Penarikan (BQ-123) --}}
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

    {{-- Dialog Modal Tambah/Ubah Rekening --}}
    <div
        x-show="accountModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
    >
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="accountModalOpen = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-100 text-left" @click.stop>
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900" x-text="isEdit ? 'Ubah Rekening Penarikan' : 'Tambah Rekening Penarikan'"></h3>
                <button type="button" @click="accountModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form :action="formUrl" method="POST" class="mt-4 space-y-4">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="_action" value="save_account">
                <input type="hidden" name="is_edit" :value="isEdit ? '1' : '0'">
                <input type="hidden" name="form_url" :value="formUrl">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Bank</label>
                    <select name="bank_kode" x-model="bankKode" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">Pilih Bank</option>
                        @foreach (config('banks', []) as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('bank_kode')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Rekening</label>
                    <input type="text" name="nomor_rekening" x-model="nomorRekening" required inputmode="numeric" placeholder="Hanya angka, contoh: 1234567890" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
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
                    <label class="block text-xs font-semibold text-amber-950 mb-1">Password Akun Saat Ini</label>
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

    {{-- Dialog Konfirmasi Hapus Rekening --}}
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

    {{-- Dialog Modal Multi-Step Withdraw (BQ-125) --}}
    <div
        x-show="withdrawModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="withdrawModalOpen = false"
    >
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="withdrawModalOpen = false"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl border border-slate-100 text-left" @click.stop>
            {{-- Step 1: Nominal & Rekening --}}
            <div x-show="step === 1">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Tarik Saldo</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Langkah 1 dari 2: Nominal & Rekening Tujuan</p>
                    </div>
                    <button type="button" @click="withdrawModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700">Nominal Penarikan (Rp)</label>
                            <button type="button" @click="tarikSemua()" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">
                                Tarik Semua
                            </button>
                        </div>
                        <input
                            type="number"
                            x-model="amount"
                            min="10000"
                            step="1"
                            placeholder="Minimal Rp 10.000"
                            class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                        >
                        <p class="mt-1 text-[11px] text-slate-500">
                            Saldo tersedia: <span class="font-bold text-slate-700">Rp {{ number_format($availableBalance, 0, ',', '.') }}</span> · Minimal Rp 10.000
                        </p>
                        @error('jumlah')
                            <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-2">Pilih Rekening Tujuan</label>
                        @if ($payoutAccounts->isEmpty())
                            <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3 text-xs text-amber-900">
                                <p>Belum ada rekening penarikan tersimpan.</p>
                                <button type="button" @click="withdrawModalOpen = false; openCreate()" class="mt-1 font-bold text-indigo-600 hover:underline">
                                    + Tambah rekening sekarang
                                </button>
                            </div>
                        @else
                            <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                                @foreach ($payoutAccounts as $acc)
                                    <label
                                        class="flex items-center justify-between rounded-xl border p-3 cursor-pointer transition"
                                        :class="String(accountId) === '{{ $acc->id }}' ? 'border-indigo-500 bg-indigo-50/40' : 'border-slate-200 hover:border-slate-300'"
                                    >
                                        <div class="flex items-center gap-3">
                                            <input type="radio" name="_account_radio" value="{{ $acc->id }}" x-model="accountId" class="text-indigo-600 focus:ring-indigo-500">
                                            <div>
                                                <p class="text-xs font-bold text-slate-900">{{ $acc->bank_nama }} ({{ $acc->masked_number }})</p>
                                                <p class="text-[11px] text-slate-500">a.n. {{ $acc->nama_pemilik_rekening }}</p>
                                            </div>
                                        </div>
                                        @if ($acc->is_default)
                                            <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-bold text-indigo-700">Utama</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        @error('payout_account_id')
                            <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="withdrawModalOpen = false" class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="if (canProceed()) step = 2"
                            :disabled="! canProceed()"
                            class="rounded-xl bg-bq-primary px-5 py-2 text-xs font-bold text-white hover:bg-bq-primary-hover shadow-xs transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Lanjutkan &rarr;
                        </button>
                    </div>
                </div>
            </div>

            {{-- Step 2: Konfirmasi Penarikan --}}
            <div x-show="step === 2">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Konfirmasi Withdraw</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Langkah 2 dari 2: Tinjau rincian penarikan</p>
                    </div>
                    <button type="button" @click="withdrawModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4 space-y-3">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-slate-500">Nominal Penarikan</span>
                            <span class="text-base font-extrabold text-indigo-700">Rp <span x-text="formattedAmount()"></span></span>
                        </div>
                        <div class="border-t border-indigo-100/70 pt-2 flex justify-between items-start text-xs">
                            <span class="text-slate-500">Bank Tujuan</span>
                            <span class="font-bold text-slate-900 text-right" x-text="selectedAccount()?.bank_nama"></span>
                        </div>
                        <div class="border-t border-indigo-100/70 pt-2 flex justify-between items-start text-xs">
                            <span class="text-slate-500">Nomor Rekening</span>
                            <span class="font-mono font-bold text-slate-900 text-right" x-text="selectedAccount()?.masked_number"></span>
                        </div>
                        <div class="border-t border-indigo-100/70 pt-2 flex justify-between items-start text-xs">
                            <span class="text-slate-500">Nama Penerima</span>
                            <span class="font-semibold text-slate-800 text-right" x-text="selectedAccount()?.nama_pemilik_rekening"></span>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        Dana akan langsung dipotong dari saldo BookQu Anda dan diverifikasi oleh Admin. Transfer dilakukan secara manual dalam jam kerja.
                    </p>

                    <form
                        id="withdraw-form"
                        method="POST"
                        action="{{ route('owner.payouts.request') }}"
                        x-data="{ submitting: false }"
                        @submit="submitting = true"
                    >
                        @csrf
                        <input type="hidden" name="request_key" value="{{ old('request_key', $requestKey) }}">
                        <input type="hidden" name="jumlah" :value="amount">
                        <input type="hidden" name="payout_account_id" :value="accountId">

                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                            <button
                                type="button"
                                @click="step = 1"
                                :disabled="submitting"
                                class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Kembali
                            </button>
                            <button
                                type="submit"
                                :disabled="submitting"
                                class="rounded-xl bg-bq-primary px-5 py-2 text-xs font-bold text-white hover:bg-bq-primary-hover shadow-xs transition disabled:opacity-60 disabled:cursor-not-allowed"
                            >
                                <span x-text="submitting ? 'Memproses…' : 'Konfirmasi Withdraw'">Konfirmasi Withdraw</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Riwayat Withdraw (BQ-125) --}}
    <div class="rounded-2xl border border-bq-border bg-bq-surface shadow-xs overflow-hidden">
        <div class="p-5 border-b border-bq-border flex items-center justify-between">
            <div>
                <h3 class="font-bold text-sm text-bq-text">Riwayat Withdraw</h3>
                <p class="text-xs text-bq-text-muted mt-0.5">Catatan transfer saldo platform ke rekening Anda</p>
            </div>
        </div>

        {{-- Desktop Table View (>= 640px) --}}
        <div class="hidden sm:block overflow-x-auto">
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
                                {{ ($p->requested_at ?? $p->created_at)->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap font-bold text-emerald-700">
                                Rp {{ number_format((float) $p->jumlah, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-bq-text-muted">
                                {{ $p->bank_nama ?? '-' }} · {{ $p->payoutAccount?->masked_number ?? ('••••' . substr((string) $p->nomor_rekening, -4)) }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                @php
                                    $statusStr = (string) $p->status;
                                    $tone = \App\Domain\Payout\PayoutState::ownerTone($statusStr);
                                    $badgeClass = match ($tone) {
                                        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'danger' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        default => 'bg-amber-50 text-amber-700 border-amber-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $badgeClass }}">
                                    {{ \App\Domain\Payout\PayoutState::ownerLabel($statusStr) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-xs text-bq-text-muted space-y-0.5">
                                @if (\App\Domain\Payout\PayoutState::ownerNote((string) $p->status))
                                    <p class="text-amber-700 font-medium">{{ \App\Domain\Payout\PayoutState::ownerNote((string) $p->status) }}</p>
                                @endif
                                @if ($p->rejection_reason)
                                    <p class="text-rose-600 font-medium">{{ $p->rejection_reason }}</p>
                                @endif
                                @if ($p->transfer_reference)
                                    <p class="text-slate-500">Ref: {{ $p->transfer_reference }}</p>
                                @endif
                                @if ($p->proof_path)
                                    <a href="{{ route('owner.payouts.proof', $p->id) }}" class="inline-flex items-center gap-1 font-semibold text-indigo-600 hover:underline">
                                        Bukti transfer &rarr;
                                    </a>
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

        {{-- Mobile Stacked Cards View (< 640px) --}}
        <div class="sm:hidden divide-y divide-bq-border">
            @forelse ($payouts as $p)
                @php
                    $statusStr = (string) $p->status;
                    $tone = \App\Domain\Payout\PayoutState::ownerTone($statusStr);
                    $badgeClass = match ($tone) {
                        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'danger' => 'bg-rose-50 text-rose-700 border-rose-200',
                        default => 'bg-amber-50 text-amber-700 border-amber-200',
                    };
                @endphp
                <div class="p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-mono text-slate-500">
                            {{ ($p->requested_at ?? $p->created_at)->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
                        </span>
                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $badgeClass }}">
                            {{ \App\Domain\Payout\PayoutState::ownerLabel($statusStr) }}
                        </span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="text-lg font-extrabold text-emerald-700">Rp {{ number_format((float) $p->jumlah, 0, ',', '.') }}</span>
                        <span class="text-xs font-semibold text-slate-700">{{ $p->bank_nama }} {{ $p->payoutAccount?->masked_number ?? ('••••' . substr((string) $p->nomor_rekening, -4)) }}</span>
                    </div>
                    <div class="pt-1 text-xs text-slate-600 space-y-0.5">
                        @if (\App\Domain\Payout\PayoutState::ownerNote((string) $p->status))
                            <p class="text-amber-700 font-medium">{{ \App\Domain\Payout\PayoutState::ownerNote((string) $p->status) }}</p>
                        @endif
                        @if ($p->rejection_reason)
                            <p class="text-rose-600 font-medium">{{ $p->rejection_reason }}</p>
                        @endif
                        @if ($p->transfer_reference)
                            <p class="text-slate-500">Ref: {{ $p->transfer_reference }}</p>
                        @endif
                        @if ($p->proof_path)
                            <a href="{{ route('owner.payouts.proof', $p->id) }}" class="inline-flex items-center gap-1 font-semibold text-indigo-600 hover:underline">
                                Bukti transfer &rarr;
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-xs text-bq-text-muted">
                    Belum ada riwayat penarikan dana.
                </div>
            @endforelse
        </div>

        @if ($payouts->hasPages())
            <div class="p-4 border-t border-bq-border">
                {{ $payouts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
