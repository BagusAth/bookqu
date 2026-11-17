@extends('layouts.owner-layout')

@section('title', 'Pembayaran')

{{--
    Pembayaran (BQ-141, owner-portal.md §8.12): tabs Ringkasan, Transaksi, Saldo & Pencairan,
    Rekening and Pengaturan. Pemilik only. Data from App\Actions\Owner\GetPaymentsPage; balance
    changes stay in the payout, payment lifecycle and ledger actions. No gateway settings (D-016).
--}}

@php
    $rupiah = fn (float|int $value): string => 'Rp '.number_format((float) $value, 0, ',', '.');
    $card = 'bq-panel';
    $buttonSecondary = 'bq-btn bq-btn-secondary bq-btn-sm';
    $buttonPrimary = 'bq-btn bq-btn-primary';
    $field = 'mt-1 block w-full bq-input';
    $label = 'block text-sm font-medium text-bq-text';
    $toneClass = fn (string $tone): string => match ($tone) {
        'success' => 'bg-bq-success-bg text-bq-success',
        'danger' => 'bg-bq-danger-bg text-bq-danger',
        'warning' => 'bg-bq-warning-bg text-bq-warning',
        'info' => 'bg-bq-info-bg text-bq-info',
        default => 'bg-bq-background text-bq-text-muted ring-1 ring-inset ring-bq-border',
    };
    $canAddAccount = $accounts->count() < $maxAccounts;
    $maxAmount = $payoutRules['cap'] !== null ? min((int) floor($summary['available']), $payoutRules['cap']) : (int) floor($summary['available']);
    $accountPayload = $accounts->map(fn ($account) => [
        'id' => $account->id,
        'bank_kode' => $account->bank_kode,
        'bank_nama' => $account->bank_nama,
        'nomor_rekening' => $account->nomor_rekening,
        'masked_number' => $account->masked_number,
        'nama_pemilik_rekening' => $account->nama_pemilik_rekening,
        'is_default' => (bool) $account->is_default,
    ])->values();
    $accountUpdateUrl = route('owner.payout-accounts.update', ['account' => '__ID__']);
    $accountDestroyUrl = route('owner.payout-accounts.destroy', ['account' => '__ID__']);
    $accountErrors = $errors->has('bank_kode') || $errors->has('nomor_rekening') || $errors->has('nama_pemilik_rekening') || ($errors->has('current_password') && old('_action') === 'save_account');
    $deleteErrors = $errors->has('current_password') && old('_action') === 'delete_account';
    $withdrawErrors = $errors->has('jumlah') || $errors->has('payout_account_id') || $errors->has('request_key');
@endphp

@section('content')
<div
    class="mx-auto max-w-7xl space-y-6"
    x-data="{
        ...ownerPayoutSubmission(),
        accounts: @js($accountPayload),
        account: { id: null, bank_kode: '', nomor_rekening: '', nama_pemilik_rekening: '' },
        deleteId: @js(old('delete_id')),
        step: 1,
        amount: @js(old('jumlah', '')),
        accountId: @js((string) old('payout_account_id', $accounts->firstWhere('is_default', true)?->id ?? $accounts->first()?->id ?? '')),
        min: @js($payoutRules['min']),
        max: @js($maxAmount),
        url(template, id) { return template.replace('__ID__', encodeURIComponent(id)); },
        openAccount(data, trigger) {
            this.account = Object.assign({ id: null, bank_kode: '', nomor_rekening: '', nama_pemilik_rekening: '' }, data || {});
            $dispatch('open-dialog', { id: 'payout-account-dialog', trigger });
        },
        openDelete(id, trigger) {
            this.deleteId = id;
            $dispatch('open-dialog', { id: 'payout-account-delete-dialog', trigger });
        },
        openWithdraw(trigger) {
            this.step = this.payoutUnconfirmed || this.payoutSubmitting ? 3 : 1;
            $dispatch('open-dialog', { id: 'withdraw-dialog', trigger });
        },
        amountValid() {
            const n = Number(this.amount);
            return Number.isInteger(n) && n >= this.min && n <= this.max;
        },
        selectedAccount() { return this.accounts.find(a => String(a.id) === String(this.accountId)) || null; },
        formatted(n) { return new Intl.NumberFormat('id-ID').format(Number(n) || 0); },
        init() {
            const reopen = @js($accountErrors ? 'account' : ($deleteErrors ? 'delete' : ($withdrawErrors ? 'withdraw' : null)));
            if (!reopen) return;
            this.$nextTick(() => {
                if (reopen === 'account') {
                    this.openAccount({ id: @js(old('account_id')), bank_kode: @js(old('bank_kode', '')), nomor_rekening: @js(old('nomor_rekening', '')), nama_pemilik_rekening: @js(old('nama_pemilik_rekening', '')) }, null);
                } else if (reopen === 'delete') {
                    this.openDelete(this.deleteId, null);
                } else {
                    this.openWithdraw(null);
                    this.step = @js($errors->has('jumlah') ? 1 : ($errors->has('payout_account_id') ? 2 : 3));
                }
            });
        },
    }"
>
    <x-owner.page-header title="Pembayaran" purpose="Cara Anda menerima uang, berapa saldo Anda, dan kapan bisa dicairkan." />

    <x-owner.tabs :tabs="\App\Actions\Owner\GetPaymentsPage::TABS" :active="$activeTab" label="Bagian halaman Pembayaran" />

    {{-- ── Ringkasan ── --}}
    @if ($activeTab === 'ringkasan')
        <section aria-label="Saldo" class="grid gap-4 sm:grid-cols-3">
            @foreach ([['Saldo tersedia', $summary['available'], 'Bisa dicairkan ke rekening Anda'], ['Sedang diproses', $summary['inProcess'], 'Pencairan menunggu review atau transfer'], ['Total dicairkan', $summary['withdrawn'], 'Sudah ditransfer ke rekening Anda']] as [$title, $value, $hint])
                {{-- BQ-161 (audit PY-01): the available balance is the page's hero figure --}}
                <div @class(['rounded-xl p-5', 'bg-bq-primary text-white' => $loop->first, $card => ! $loop->first])>
                    <p @class(['text-sm', 'text-white/80' => $loop->first, 'text-bq-text-muted' => ! $loop->first])>{{ $title }}</p>
                    <p @class(['bq-num mt-1 font-bold', 'text-3xl text-white' => $loop->first, 'text-2xl text-bq-ink' => ! $loop->first])>{{ $rupiah($value) }}</p>
                    <p @class(['mt-1 text-xs', 'text-white/80' => $loop->first, 'text-bq-text-muted' => ! $loop->first])>{{ $hint }}</p>
                </div>
            @endforeach
        </section>

        <section aria-label="Pembayaran pelanggan" class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([['Berhasil', 'sukses'], ['Menunggu', 'pending'], ['Gagal', 'gagal'], ['Direfund', 'refund']] as [$title, $key])
                <div class="{{ $card }} p-4">
                    <p class="text-sm text-bq-text-muted">Pembayaran {{ mb_strtolower($title) }}</p>
                    <p class="mt-1 text-xl font-semibold bq-num text-bq-text">{{ $summary['counts'][$key] }}</p>
                </div>
            @endforeach
        </section>

        <section aria-labelledby="latest-title" class="{{ $card }}">
            <div class="flex items-center justify-between border-b border-bq-border px-4 py-3">
                <h2 id="latest-title" class="text-base font-semibold text-bq-text">Transaksi terbaru</h2>
                <a href="{{ route('owner.payments', ['tab' => 'transaksi']) }}" class="text-sm bq-link">Semua transaksi</a>
            </div>
            @include('owner.partials.payments.transactions', ['rows' => $latest, 'toneClass' => $toneClass, 'emptyText' => 'Belum ada pembayaran pelanggan.'])
        </section>
    @endif

    {{-- ── Transaksi ── --}}
    @if ($activeTab === 'transaksi')
        <form method="GET" action="{{ route('owner.payments') }}" class="{{ $card }} flex flex-wrap items-end gap-3 p-4">
            <input type="hidden" name="tab" value="transaksi">
            <label class="grid gap-1 text-sm">Cari transaksi
                <input type="search" name="search" maxlength="100" value="{{ $transactionFilters['search'] }}" placeholder="Nomor pesanan, booking, pelanggan" class="{{ $field }}">
            </label>
            <label class="grid gap-1 text-sm">Status pembayaran
                <select name="status" class="{{ $field }}">
                    <option value="">Semua status</option>
                    @foreach ($transactionStatuses as $value => $label)
                        <option value="{{ $value }}" @selected($transactionFilters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            @foreach (['from' => 'Dari tanggal', 'to' => 'Sampai tanggal'] as $key => $label)
                <label class="grid gap-1 text-sm">{{ $label }}
                    <input type="date" name="{{ $key }}" value="{{ $transactionFilters[$key] }}" class="{{ $field }}">
                </label>
            @endforeach
            <button class="{{ $buttonSecondary }}">Cari</button>
            <a href="{{ route('owner.payments', ['tab' => 'transaksi']) }}" class="px-2 py-2 text-sm bq-link">Reset filter</a>
        </form>
        <section aria-label="Semua transaksi" class="{{ $card }}">
            @include('owner.partials.payments.transactions', ['rows' => $transactions, 'toneClass' => $toneClass, 'emptyText' => array_filter($transactionFilters) ? 'Tidak ada transaksi yang cocok.' : 'Belum ada pembayaran pelanggan.'])
        </section>
        <div>{{ $transactions->links() }}</div>
    @endif

    {{-- ── Saldo & Pencairan ── --}}
    @if ($activeTab === 'saldo')
        <section aria-labelledby="balance-title" class="{{ $card }} flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p id="balance-title" class="text-sm text-bq-text-muted">Saldo tersedia</p>
                <p class="bq-num mt-1 text-3xl font-bold text-bq-ink">{{ $rupiah($summary['available']) }}</p>
                <p class="mt-0.5 text-xs text-bq-text-muted">
                    Minimal {{ $rupiah($payoutRules['min']) }}@if ($payoutRules['cap'] !== null) · maksimal {{ $rupiah($payoutRules['cap']) }} per pengajuan @endif
                </p>
            </div>
            <div class="sm:text-right">
                <button type="button" class="{{ $buttonPrimary }}" @disabled(count($eligibilityReasons) > 0) x-on:click="openWithdraw($el)">Cairkan saldo</button>
            </div>
        </section>

        @if (count($eligibilityReasons) > 0)
            {{-- Eligibility reasons are shown before the dialog can open (§8.12). --}}
            <div role="status" class="rounded-xl border border-bq-warning/30 bg-bq-warning-bg px-4 py-3 text-sm text-bq-warning">
                <p class="font-medium">Belum dapat mengajukan pencairan</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($eligibilityReasons as $reason)
                        <li>{{ $reason }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section aria-labelledby="ledger-title" class="{{ $card }}">
            <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-bq-border px-4 py-3">
                <h2 id="ledger-title" class="text-base font-semibold text-bq-text">Mutasi saldo</h2>
                <p class="text-sm text-bq-text-muted">Saldo bersih dari riwayat mutasi <span class="font-medium bq-num text-bq-text">{{ $rupiah($ledger['total']) }}</span></p>
            </div>
            @if ($ledger['rows']->total() === 0)
                <p class="px-4 py-6 text-sm text-bq-text-muted">Belum ada mutasi. Saldo bertambah saat pembayaran booking online berhasil.</p>
            @else
                <ul class="divide-y divide-bq-border">
                    @foreach ($ledger['rows'] as $row)
                        @php
                            $kind = match ($row->kind) {
                                'payment' => 'Pembayaran booking',
                                'refund' => 'Refund',
                                'payout' => 'Pencairan',
                                'payout_returned' => 'Pencairan dikembalikan',
                                default => $row->kind,
                            };
                            $amount = (float) $row->amount;
                        @endphp
                        <li class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                            <div class="min-w-0">
                                <p class="text-bq-text">{{ $kind }}@if ($row->booking_code) · {{ $row->booking_code }}@endif</p>
                                <p class="text-xs bq-num text-bq-text-muted">{{ \Illuminate\Support\Carbon::parse($row->occurred_at)->timezone('Asia/Jakarta')->translatedFormat('j M Y, H:i') }}@if ($row->reference) · {{ $row->reference }}@endif</p>
                            </div>
                            <p class="shrink-0 font-medium bq-num {{ $amount >= 0 ? 'text-bq-success' : 'text-bq-danger' }}">{{ $amount >= 0 ? '+' : '−' }}{{ $rupiah(abs($amount)) }}</p>
                        </li>
                    @endforeach
                </ul>
                <div class="border-t border-bq-border px-4 py-3">{{ $ledger['rows']->links() }}</div>
            @endif
        </section>

        <section aria-labelledby="payouts-title" class="{{ $card }}">
            <h2 id="payouts-title" class="border-b border-bq-border px-4 py-3 text-base font-semibold text-bq-text">Riwayat pencairan</h2>
            @if ($payouts->total() === 0)
                <p class="px-4 py-6 text-sm text-bq-text-muted">Belum ada pencairan.</p>
            @else
                <ul class="divide-y divide-bq-border">
                    @foreach ($payouts as $payout)
                        <li class="space-y-2 px-4 py-3 text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-medium bq-num text-bq-text">{{ $payout['amount'] }} <span class="font-normal text-bq-text-muted">· {{ $payout['account'] }}</span></p>
                                <span class="bq-badge {{ $toneClass($payout['tone']) }}">{{ $payout['label'] }}</span>
                            </div>
                            <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs" aria-label="Tahap pencairan">
                                @foreach ($payout['stages'] as $index => $stage)
                                    @if ($index > 0)<li aria-hidden="true" class="text-bq-text-muted">→</li>@endif
                                    <li class="{{ $stage['done'] ? 'font-medium text-bq-text' : 'text-bq-text-muted' }}">{{ $stage['label'] }}<span class="sr-only">{{ $stage['done'] ? ' (selesai)' : ' (belum)' }}</span></li>
                                @endforeach
                            </ol>
                            <p class="text-xs text-bq-text-muted">Diajukan {{ $payout['requestedAt'] }}@if ($payout['note']) · {{ $payout['note'] }}@endif @if ($payout['reference']) · Ref {{ $payout['reference'] }}@endif</p>
                            @if ($payout['rejectionReason'])
                                <p class="text-xs text-bq-danger">{{ $payout['rejectionReason'] }}</p>
                            @endif
                            @if ($payout['proofUrl'])
                                <a href="{{ $payout['proofUrl'] }}" class="text-xs bq-link">Unduh bukti transfer</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <div class="border-t border-bq-border px-4 py-3">{{ $payouts->links() }}</div>
            @endif
        </section>

        {{-- Request dialog: amount, then account, then confirmation (§8.12) --}}
        <x-owner.dialog id="withdraw-dialog" title="Cairkan saldo" size="lg">
            <div x-show="step === 1" class="space-y-3">
                <p class="text-xs text-bq-text-muted">Langkah 1 dari 3: jumlah</p>
                <div>
                    <div class="flex items-center justify-between">
                        <label for="withdraw-amount" class="{{ $label }}">Jumlah (Rp, tanpa sen)</label>
                        <button type="button" class="text-sm bq-link" x-on:click="amount = max">Semua yang bisa</button>
                    </div>
                    <input id="withdraw-amount" type="number" inputmode="numeric" step="1" x-model="amount" min="{{ $payoutRules['min'] }}" max="{{ $maxAmount }}" class="{{ $field }}">
                    <p class="mt-1 text-xs text-bq-text-muted">Saldo tersedia {{ $rupiah($summary['available']) }} · Minimal {{ $rupiah($payoutRules['min']) }}@if ($payoutRules['cap'] !== null) · maksimal {{ $rupiah($payoutRules['cap']) }} per pengajuan @endif</p>
                    <p x-show="payoutErrors.jumlah" x-text="payoutErrors.jumlah?.[0]" class="mt-1 text-bq-small text-bq-danger" role="alert"></p>
                    @error('jumlah')<p class="mt-1 text-bq-small text-bq-danger">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end">
                    <button type="button" class="{{ $buttonPrimary }}" x-bind:disabled="!amountValid()" x-on:click="step = 2">Lanjut</button>
                </div>
            </div>

            <div x-show="step === 2" x-cloak class="space-y-3">
                <p class="text-xs text-bq-text-muted">Langkah 2 dari 3: rekening tujuan</p>
                @if ($accounts->isEmpty())
                    <p class="rounded-lg bg-bq-warning-bg px-3 py-2 text-sm text-bq-warning">Belum ada rekening tersimpan. <a href="{{ route('owner.payments', ['tab' => 'rekening']) }}" class="underline">Tambah rekening</a></p>
                @else
                    <fieldset class="space-y-2">
                        <legend class="sr-only">Rekening tujuan</legend>
                        @foreach ($accounts as $account)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-bq-border px-3 py-2 text-sm has-[:checked]:border-bq-primary has-[:checked]:bg-bq-primary-light">
                                <input type="radio" value="{{ $account->id }}" x-model="accountId" class="text-bq-primary">
                                <span class="min-w-0 flex-1">
                                    <span class="block text-bq-text">{{ $account->bank_nama }} · {{ $account->masked_number }}</span>
                                    <span class="block text-xs text-bq-text-muted">a.n. {{ $account->nama_pemilik_rekening }}</span>
                                </span>
                                @if ($account->is_default)<span class="text-xs text-bq-text-muted">Utama</span>@endif
                            </label>
                        @endforeach
                    </fieldset>
                @endif
                <p x-show="payoutErrors.payout_account_id" x-text="payoutErrors.payout_account_id?.[0]" class="text-bq-small text-bq-danger" role="alert"></p>
                @error('payout_account_id')<p class="text-xs text-bq-danger">{{ $message }}</p>@enderror
                <div class="flex justify-between">
                    <button type="button" class="{{ $buttonSecondary }}" x-on:click="step = 1">Kembali</button>
                    <button type="button" class="{{ $buttonPrimary }}" x-bind:disabled="!selectedAccount()" x-on:click="step = 3">Lanjut</button>
                </div>
            </div>

            <div x-show="step === 3" x-cloak class="space-y-3">
                <div x-show="!payoutUnconfirmed" class="space-y-3">
                    <p class="text-xs text-bq-text-muted">Langkah 3 dari 3: konfirmasi</p>
                    <dl class="space-y-1.5 rounded-lg bg-bq-background px-3 py-2 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-bq-text-muted">Jumlah</dt><dd class="font-medium bq-num text-bq-text">Rp <span x-text="formatted(amount)"></span></dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-bq-text-muted">Bank</dt><dd class="text-bq-text" x-text="selectedAccount()?.bank_nama"></dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-bq-text-muted">Nomor rekening</dt><dd class="bq-num text-bq-text" x-text="selectedAccount()?.masked_number"></dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-bq-text-muted">Atas nama</dt><dd class="text-bq-text" x-text="selectedAccount()?.nama_pemilik_rekening"></dd></div>
                    </dl>
                    <p class="text-xs text-bq-text-muted">Jumlah ini langsung ditahan dari saldo Anda. Admin BookQu meninjau lalu mentransfer secara manual pada jam kerja.</p>
                    <form id="withdraw-form" method="POST" action="{{ route('owner.payouts.request') }}" x-ref="payoutForm" x-on:submit.prevent="submitPayout($el)" class="flex justify-between">
                        @csrf
                        <input type="hidden" name="request_key" value="{{ old('request_key', $requestKey) }}">
                        <input type="hidden" name="jumlah" x-bind:value="amount">
                        <input type="hidden" name="payout_account_id" x-bind:value="accountId">
                        <button type="button" class="{{ $buttonSecondary }}" x-on:click="step = 2" :disabled="payoutSubmitting">Kembali</button>
                        <button type="submit" class="{{ $buttonPrimary }}" :disabled="payoutSubmitting" x-text="payoutSubmitting ? 'Memproses…' : 'Ajukan pencairan'">Ajukan pencairan</button>
                    </form>
                    <p x-show="payoutErrors.request_key" x-text="payoutErrors.request_key?.[0]" role="alert" class="text-bq-small text-bq-danger"></p>
                    @error('request_key')<p class="text-bq-small text-bq-danger">{{ $message }}</p>@enderror
                </div>
                <div x-show="payoutUnconfirmed" x-cloak class="space-y-3">
                    <x-ui.error-block title="Pengajuan pencairan belum terkonfirmasi" description="Koneksi terputus sebelum BookQu menjawab. Pengajuan mungkin sudah tercatat, mungkin belum. Tekan Coba lagi: permintaan yang sama tidak membuat pencairan ganda dan saldo hanya ditahan sekali. Transfer tetap diproses admin BookQu setelah pengajuan tercatat." />
                    <div class="flex flex-wrap justify-end gap-2">
                        <x-ui.button size="sm" x-on:click="hide()">Nanti saja</x-ui.button>
                        <x-ui.button size="sm" variant="primary" x-ref="payoutRetry" x-on:click="submitPayout($refs.payoutForm)" x-bind:disabled="payoutSubmitting">Coba lagi</x-ui.button>
                    </div>
                </div>
            </div>
        </x-owner.dialog>
    @endif

    {{-- ── Rekening (D-004) ── --}}
    @if ($activeTab === 'rekening')
        <section aria-labelledby="accounts-title" class="{{ $card }}">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bq-border px-4 py-3">
                <div>
                    <h2 id="accounts-title" class="text-base font-semibold text-bq-text">Rekening pencairan</h2>
                    <p class="text-sm text-bq-text-muted">{{ $accounts->count() }} dari {{ $maxAccounts }} rekening. Mengubah rekening memerlukan password dan mengirim email pemberitahuan.</p>
                </div>
                @if ($canAddAccount)
                    <button type="button" id="payout-account-create" class="{{ $buttonPrimary }}" x-on:click="openAccount(null, $el)">Tambah rekening</button>
                @endif
            </div>
            @if ($accounts->isEmpty())
                <x-owner.empty-state title="Belum ada rekening" description="Tambahkan rekening bank untuk mencairkan saldo." class="m-4" />
            @else
                <ul class="divide-y divide-bq-border">
                    @foreach ($accounts as $account)
                        <li class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0 text-sm">
                                <p class="text-bq-text">{{ $account->bank_nama }} · <span class="bq-num">{{ $account->masked_number }}</span>
                                    @if ($account->is_default)<span class="ml-1 inline-flex items-center rounded-full bg-bq-primary-light px-2 py-0.5 text-xs font-medium text-bq-primary">Utama</span>@endif
                                </p>
                                <p class="text-xs text-bq-text-muted">a.n. {{ $account->nama_pemilik_rekening }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @unless ($account->is_default)
                                    <form method="POST" action="{{ route('owner.payout-accounts.default', $account->id) }}">
                                        @csrf
                                        <button type="submit" class="{{ $buttonSecondary }}">Jadikan utama</button>
                                    </form>
                                @endunless
                                <button type="button" class="{{ $buttonSecondary }}" x-on:click="openAccount(accounts.find(a => a.id === @js($account->id)), $el)">Ubah</button>
                                <button type="button" class="{{ $buttonSecondary }} text-bq-danger" x-on:click="openDelete(@js($account->id), $el)">Hapus</button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <x-owner.dialog id="payout-account-dialog" title="Rekening pencairan">
            <form id="payout-account-form" method="POST" x-bind:action="account.id ? url(@js($accountUpdateUrl), account.id) : @js(route('owner.payout-accounts.store'))" class="space-y-3">
                @csrf
                <input type="hidden" name="_method" value="PUT" x-bind:disabled="!account.id">
                <input type="hidden" name="_action" value="save_account">
                <input type="hidden" name="account_id" x-bind:value="account.id || ''">
                <div>
                    <label for="account-bank" class="{{ $label }}">Bank</label>
                    <select id="account-bank" name="bank_kode" x-model="account.bank_kode" required class="{{ $field }}">
                        <option value="">Pilih bank</option>
                        @foreach (config('banks', []) as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('bank_kode')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="account-number" class="{{ $label }}">Nomor rekening</label>
                    <input id="account-number" type="text" name="nomor_rekening" inputmode="numeric" required x-model="account.nomor_rekening" class="{{ $field }}">
                    @error('nomor_rekening')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="account-holder" class="{{ $label }}">Nama pemilik rekening</label>
                    <input id="account-holder" type="text" name="nama_pemilik_rekening" maxlength="150" required x-model="account.nama_pemilik_rekening" class="{{ $field }}">
                    @error('nama_pemilik_rekening')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="account-password" class="{{ $label }}">Password akun Anda</label>
                    <input id="account-password" type="password" name="current_password" required autocomplete="current-password" class="{{ $field }}">
                    @if ($errors->has('current_password') && old('_action') === 'save_account')<p class="mt-1 text-xs text-bq-danger">{{ $errors->first('current_password') }}</p>@endif
                </div>
            </form>
            <x-slot:footer>
                <button type="button" class="{{ $buttonSecondary }} py-2" x-on:click="$dispatch('close-dialog', { id: 'payout-account-dialog' })">Batal</button>
                <button type="submit" form="payout-account-form" class="{{ $buttonPrimary }}">Simpan rekening</button>
            </x-slot:footer>
        </x-owner.dialog>

        <x-owner.dialog id="payout-account-delete-dialog" title="Hapus rekening?" size="sm" description="Rekening yang dipakai pencairan yang masih diproses tidak bisa dihapus.">
            <form id="payout-account-delete-form" method="POST" x-bind:action="deleteId ? url(@js($accountDestroyUrl), deleteId) : ''" class="space-y-3">
                @csrf
                <input type="hidden" name="_method" value="DELETE">
                <input type="hidden" name="_action" value="delete_account">
                <input type="hidden" name="delete_id" x-bind:value="deleteId || ''">
                <div>
                    <label for="delete-password" class="{{ $label }}">Password akun Anda</label>
                    <input id="delete-password" type="password" name="current_password" required autocomplete="current-password" class="{{ $field }}">
                    @if ($deleteErrors)<p class="mt-1 text-xs text-bq-danger">{{ $errors->first('current_password') }}</p>@endif
                </div>
            </form>
            <x-slot:footer>
                <button type="button" class="{{ $buttonSecondary }} py-2" x-on:click="$dispatch('close-dialog', { id: 'payout-account-delete-dialog' })">Batal</button>
                <button type="submit" form="payout-account-delete-form" class="bq-btn bq-btn-danger-solid">Hapus rekening</button>
            </x-slot:footer>
        </x-owner.dialog>
    @endif

    {{-- ── Pengaturan: explanation only, nothing to configure (D-016) ── --}}
    @if ($activeTab === 'pengaturan')
        <section aria-labelledby="how-title" class="{{ $card }} space-y-4 p-4 text-sm text-bq-text">
            <h2 id="how-title" class="text-base font-semibold">Cara pembayaran bekerja</h2>
            <ul class="list-disc space-y-2 pl-5">
                <li>Pembayaran pelanggan diproses oleh BookQu: QRIS, virtual account dan e-wallet. Anda tidak perlu mengatur payment gateway.</li>
                <li>Biaya platform 0%. Seluruh pembayaran booking yang berhasil masuk ke saldo Anda.</li>
                <li>Pelanggan punya waktu {{ $explanation['deadlineMinutes'] }} menit untuk membayar. Setelah itu booking batal otomatis dan slotnya kembali terbuka.</li>
                <li>
                    @if ($explanation['refundsEnabled'])
                        Refund aktif:
                        @if ($explanation['cancelBeforeHours'] > 0)
                            pelanggan bisa membatalkan dan meminta refund paling lambat {{ $explanation['cancelBeforeHours'] }} jam sebelum jadwal.
                        @else
                            pelanggan bisa meminta refund sesuai Kebijakan Booking Anda.
                        @endif
                        Refund yang Anda setujui mengurangi saldo.
                    @else
                        Refund tidak aktif: pelanggan tidak bisa meminta refund dari link akses mereka.
                    @endif
                    Atur di Pengaturan → Kebijakan Booking.
                </li>
                <li>Saldo dicairkan ke rekening Anda lewat tab Saldo &amp; Pencairan, ditinjau dan ditransfer oleh admin BookQu.</li>
            </ul>
        </section>
    @endif
</div>
@endsection
