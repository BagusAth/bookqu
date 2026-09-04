@extends('layouts.owner-layout')

@section('title', 'Vouchers & Discounts')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    search: '{{ addslashes($search ?? '') }}',
    addModalOpen: false,
    editModalOpen: false,
    activeVoucher: {
        id: null,
        code: '',
        discount_type: 'percentage',
        discount_value: 10,
        min_order_amount: 0,
        max_discount_amount: null,
        usage_limit: null,
        start_date: '',
        end_date: '',
        is_active: 1,
        applicable_services: []
    },
    openEdit(v) {
        this.activeVoucher = { ...v };
        this.editModalOpen = true;
    },
    copyCode(code) {
        navigator.clipboard.writeText(code);
        $dispatch('toast', { message: 'Kode ' + code + ' disalin!', type: 'success' });
    }
}">

    {{-- ── Header ── --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-bq-text">Promo Vouchers &amp; Discounts</h1>
            <p class="text-sm text-bq-text-muted mt-1">Buat kode promo, kupon diskon persentase atau nominal untuk mendongkrak konversi booking.</p>
        </div>
        <button type="button" @click="addModalOpen = true" class="inline-flex items-center gap-2 rounded-xl bg-bq-primary px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-bq-primary-hover transition" id="btn-add-voucher">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            + Buat Voucher Baru
        </button>
    </div>

    {{-- ── Search & Filter ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('owner.vouchers') }}" class="relative w-full sm:max-w-xs">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari kode kupon..." class="w-full rounded-xl border border-bq-border bg-bq-surface py-2.5 pl-10 pr-4 text-xs text-bq-text placeholder-bq-text-subtle transition focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
        </form>
        <p class="text-xs text-bq-text-muted">
            Total <span class="font-bold text-bq-text">{{ $vouchers->count() }}</span> voucher promo
        </p>
    </div>

    {{-- ── Voucher Cards Grid ── --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($vouchers as $v)
            @php
                $vPayload = [
                    'id'                  => $v->id,
                    'code'                => $v->code,
                    'discount_type'       => $v->discount_type,
                    'discount_value'      => (float) $v->discount_value,
                    'min_order_amount'    => (float) ($v->min_spending ?? $v->min_order_amount ?? 0),
                    'max_discount_amount' => ($v->max_discount ?? $v->max_discount_amount) ? (float) ($v->max_discount ?? $v->max_discount_amount) : null,
                    'usage_limit'         => $v->usage_limit,
                    'start_date'          => $v->start_date ? \Carbon\Carbon::parse($v->start_date)->format('Y-m-d') : '',
                    'end_date'            => $v->end_date ? \Carbon\Carbon::parse($v->end_date)->format('Y-m-d') : '',
                    'is_active'           => (int) $v->is_active,
                    'applicable_services' => is_string($v->applicable_services) ? ($v->applicable_services === 'all' ? [] : explode(',', $v->applicable_services)) : ($v->applicable_services ?? []),
                ];
            @endphp
            <div class="relative rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs transition hover:border-bq-border-strong hover:shadow-md flex flex-col justify-between overflow-hidden">
                <div class="absolute -right-6 -bottom-6 h-24 w-24 rounded-full bg-indigo-50/50 pointer-events-none"></div>

                <div>
                    {{-- Header Code & Discount --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="rounded-lg bg-indigo-50 px-2.5 py-1 font-mono text-xs font-bold text-indigo-700 tracking-wider">{{ $v->code }}</span>
                            <button type="button" @click="copyCode('{{ $v->code }}')" class="text-bq-text-muted hover:text-indigo-600 transition" title="Salin Kode">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                        </div>
                        <span class="font-bold text-sm text-emerald-600">
                            {{ $v->discount_type === 'percentage' ? $v->discount_value . '% OFF' : 'Rp ' . number_format($v->discount_value, 0, ',', '.') }}
                        </span>
                    </div>

                    {{-- Details --}}
                    <div class="mt-4 space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-bq-text-muted">Min. Order:</span>
                            <span class="font-medium text-bq-text">Rp {{ number_format($v->min_spending ?? $v->min_order_amount ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-bq-text-muted">Penggunaan:</span>
                            <span class="font-medium text-bq-text">
                                {{ $v->used_count }} / {{ $v->usage_limit ? $v->usage_limit . ' Kuota' : 'Unlimited' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-bq-text-muted">Masa Berlaku:</span>
                            <span class="font-medium text-bq-text-subtle font-mono text-[11px]">
                                @if($v->start_date && $v->end_date)
                                    {{ \Carbon\Carbon::parse($v->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($v->end_date)->format('d M Y') }}
                                @elseif($v->end_date)
                                    s/d {{ \Carbon\Carbon::parse($v->end_date)->format('d M Y') }}
                                @else
                                    Selamanya
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Footer Controls --}}
                <div class="mt-5 pt-3 border-t border-bq-border flex items-center justify-between">
                    <form method="POST" action="{{ route('owner.vouchers.toggle', $v->id) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold transition {{ $v->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                            <span>{{ $v->is_active ? 'Aktif' : 'Non-Aktif' }}</span>
                        </button>
                    </form>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="openEdit(@json($vPayload))" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-slate-100 hover:text-bq-text transition" title="Edit" id="btn-edit-voucher-{{ $v->id }}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <form method="POST" action="{{ route('owner.vouchers.destroy', $v->id) }}" id="form-delete-voucher-{{ $v->id }}">
                            @csrf
                            @method('DELETE')
                            <button type="button"
                                @click="$dispatch('open-confirm', { title: 'Hapus Voucher?', message: 'Yakin ingin menghapus voucher {{ $v->code }}?', formId: 'form-delete-voucher-{{ $v->id }}' })"
                                class="rounded-lg p-1.5 text-bq-text-muted hover:bg-rose-50 hover:text-rose-600 transition" title="Hapus" id="btn-delete-voucher-{{ $v->id }}">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-bq-border bg-bq-surface p-12 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-bq-text">Belum ada voucher promo</h3>
                <p class="mt-1 text-xs text-bq-text-muted max-w-sm mx-auto">Tingkatkan pemesanan dengan memberikan kupon diskon spesial untuk calon pelanggan Anda.</p>
                <button type="button" @click="addModalOpen = true" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">
                    + Buat Voucher Baru
                </button>
            </div>
        @endforelse
    </div>

    {{-- Add Voucher Modal --}}
    <div x-show="addModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;" x-cloak>
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="addModalOpen = false">
            <h3 class="text-base font-bold text-bq-text">Buat Kode Promo Baru</h3>
            <p class="text-xs text-bq-text-muted mt-1">Konfigurasi nilai diskon, kuota pemakaian, dan masa berlaku kupon.</p>
            <form method="POST" action="{{ route('owner.vouchers.store') }}" class="mt-4 space-y-3.5" id="form-add-voucher">
                @csrf
                <div>
                    <label class="text-xs font-semibold text-bq-text">Kode Promo <span class="text-rose-500">*</span></label>
                    <input type="text" name="code" required placeholder="Contoh: MERDEKA20" class="mt-1 w-full rounded-xl border border-bq-border px-3.5 py-2 font-mono uppercase text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Tipe Diskon <span class="text-rose-500">*</span></label>
                        <select name="discount_type" class="mt-1 w-full rounded-xl border border-bq-border px-3 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                            <option value="percentage">Persentase (%)</option>
                            <option value="fixed">Nominal Tetap (Rp)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Nilai Diskon <span class="text-rose-500">*</span></label>
                        <input type="number" name="discount_value" required min="1" placeholder="20 atau 25000" class="mt-1 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Min. Transaksi (Rp)</label>
                        <input type="number" name="min_order_amount" min="0" placeholder="0" class="mt-1 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Batas Kuota Pemakaian</label>
                        <input type="number" name="usage_limit" min="1" placeholder="Kosongkan jika unlimited" class="mt-1 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Mulai Berlaku</label>
                        <input type="date" name="start_date" class="mt-1 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Berakhir Pada</label>
                        <input type="date" name="end_date" class="mt-1 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-bq-border">
                    <button type="button" @click="addModalOpen = false" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Simpan Voucher</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Voucher Modal --}}
    <div x-show="editModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;" x-cloak>
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="editModalOpen = false">
            <h3 class="text-base font-bold text-bq-text">Edit Kode Promo</h3>
            <p class="text-xs text-bq-text-muted mt-1">Perbarui ketentuan diskon dan ketersediaan kupon.</p>
            <form method="POST" :action="`/owner/vouchers/${activeVoucher.id}`" class="mt-4 space-y-3.5" id="form-edit-voucher">
                @csrf
                @method('PUT')
                <div>
                    <label class="text-xs font-semibold text-bq-text">Kode Promo <span class="text-rose-500">*</span></label>
                    <input type="text" name="code" x-model="activeVoucher.code" required class="mt-1 w-full rounded-xl border border-bq-border px-3.5 py-2 font-mono uppercase text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Tipe Diskon <span class="text-rose-500">*</span></label>
                        <select name="discount_type" x-model="activeVoucher.discount_type" class="mt-1 w-full rounded-xl border border-bq-border px-3 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                            <option value="percentage">Persentase (%)</option>
                            <option value="fixed">Nominal Tetap (Rp)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Nilai Diskon <span class="text-rose-500">*</span></label>
                        <input type="number" name="discount_value" x-model="activeVoucher.discount_value" required min="1" class="mt-1 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Min. Transaksi (Rp)</label>
                        <input type="number" name="min_order_amount" x-model="activeVoucher.min_order_amount" min="0" class="mt-1 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Batas Kuota Pemakaian</label>
                        <input type="number" name="usage_limit" x-model="activeVoucher.usage_limit" min="1" placeholder="Unlimited" class="mt-1 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Mulai Berlaku</label>
                        <input type="date" name="start_date" x-model="activeVoucher.start_date" class="mt-1 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Berakhir Pada</label>
                        <input type="date" name="end_date" x-model="activeVoucher.end_date" class="mt-1 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Status</label>
                    <select name="is_active" x-model="activeVoucher.is_active" class="mt-1 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                        <option :value="1">Aktif</option>
                        <option :value="0">Non-Aktif</option>
                    </select>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-bq-border">
                    <button type="button" @click="editModalOpen = false" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
