@extends('layouts.owner-layout')

@section('title', 'Promo')

@section('content')
@php
    $promoErrorForm = $errors->any() ? (string) old('_form') : '';
    $promoCreating = $promoErrorForm === 'promo-create';
    $promoEditId = str_starts_with($promoErrorForm, 'promo-edit-') ? (int) substr($promoErrorForm, 11) : null;
    $promoInput = \Illuminate\Support\Arr::only(old(), ['code', 'discount_type', 'discount_value', 'min_order_amount', 'max_discount_amount', 'usage_limit', 'start_date', 'end_date', 'is_active', 'applicable_services', 'valid_days', 'valid_from_time', 'valid_until_time']);
    $promoInput['applicable_services'] = array_map('strval', array_filter((array) ($promoInput['applicable_services'] ?? []), 'is_scalar'));
    $promoInput['valid_days'] = array_map('strval', array_filter((array) ($promoInput['valid_days'] ?? []), 'is_scalar'));
@endphp
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    addModalOpen: @js($promoCreating || ($promoEditId === null && request()->boolean('buat'))),
    editModalOpen: @js($promoEditId > 0),
    newVoucher: { discount_type: 'percentage', max_discount_amount: null, applicable_services: [], valid_days: [], valid_from_time: '', valid_until_time: '', ...@js($promoCreating ? $promoInput : []) },
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
        applicable_services: [],
        valid_days: [],
        valid_from_time: '',
        valid_until_time: '',
        ...@js($promoEditId ? $promoInput + ['id' => $promoEditId] : []),
    },
    openEdit(v) {
        this.activeVoucher = { ...v };
        this.editModalOpen = true;
    },
    async copyCode(code) {
        try {
            await navigator.clipboard.writeText(code);
            $dispatch('toast', { message: 'Kode ' + code + ' disalin.', type: 'success' });
        } catch {
            $dispatch('toast', { message: 'Kode tidak dapat disalin. Salin secara manual.', type: 'error' });
        }
    }
}">

    <x-owner.page-header title="Promo" purpose="Beri potongan harga, dan lihat apakah berhasil.">
        <x-slot:action>
            <button type="button" @click="addModalOpen = true" class="bq-btn bq-btn-primary" id="btn-add-voucher">Buat promo</button>
        </x-slot:action>
    </x-owner.page-header>

    <x-ui.stat hero label="Pendapatan dengan promo" :value="'Rp '.number_format($promoFigures['revenue'], 0, ',', '.')"
        :supporting="['Promo aktif' => $promoFigures['active'], 'Pemakaian' => $promoFigures['uses'], 'Total diskon' => 'Rp '.number_format($promoFigures['discount'], 0, ',', '.')]" />
    <x-owner.tabs :tabs="$promoTabs" :active="$tab" label="Status promo" />

    {{-- ── Search & Filter ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route($promoRoute) }}" class="relative w-full sm:max-w-xs">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari kode promo" class="bq-input w-full pl-10">
        </form>
        <p class="text-xs text-bq-text-muted">
            Total <span class="font-bold text-bq-text">{{ $vouchers->count() }}</span> promo
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
                    'max_discount_amount' => $v->max_discount !== null ? (float) $v->max_discount : null,
                    'usage_limit'         => $v->usage_limit,
                    'start_date'          => $v->start_date ? \Carbon\Carbon::parse($v->start_date)->format('Y-m-d') : '',
                    'end_date'            => $v->end_date ? \Carbon\Carbon::parse($v->end_date)->format('Y-m-d') : '',
                    'is_active'           => (int) $v->is_active,
                    'applicable_services' => is_string($v->applicable_services) ? ($v->applicable_services === 'all' ? [] : explode(',', $v->applicable_services)) : ($v->applicable_services ?? []),
                    'valid_days'          => $v->valid_days ? explode(',', $v->valid_days) : [],
                    'valid_from_time'     => $v->valid_from_time ? substr((string) $v->valid_from_time, 0, 5) : '',
                    'valid_until_time'    => $v->valid_until_time ? substr((string) $v->valid_until_time, 0, 5) : '',
                ];
            @endphp
            <div class="bq-panel flex min-w-0 flex-col justify-between p-4">

                <div>
                    {{-- Header Code & Discount --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="min-w-0 truncate rounded-bq-control bg-bq-surface-2 px-2.5 py-1 font-semibold text-bq-primary" title="{{ $v->code }}">{{ $v->code }}</span>
                            <button type="button" @click="copyCode(@js($v->code))" class="-m-2 inline-flex items-center justify-center rounded-lg p-2 text-bq-text-muted hover:bg-bq-primary-light hover:text-bq-primary transition" title="Salin Kode" aria-label="Salin Kode">
                                <x-owner.icon name="copy" class="h-4 w-4" />
                            </button>
                        </div>
                        <span class="bq-num text-2xl font-bold leading-none text-bq-primary">
                            {{ $v->discount_type === 'percentage' ? rtrim(rtrim(number_format((float) $v->discount_value, 2, ',', '.'), '0'), ',') . '%' : 'Rp ' . number_format($v->discount_value, 0, ',', '.') }}
                        </span>
                    </div>

                    {{-- Details --}}
                    <div class="mt-4 space-y-2 text-bq-small">
                        <p><span class="text-bq-text-muted">Layanan:</span> {{ $cards[$v->id]['services'] }}</p>
                        @if ($v->max_discount !== null)
                            <p><span class="text-bq-text-muted">Diskon maksimum:</span> Rp {{ number_format((float) $v->max_discount, 0, ',', '.') }}</p>
                        @endif
                        <p><span class="text-bq-text-muted">Diskon diberikan:</span> Rp {{ number_format($promoFigures['discountByPromo'][$v->id] ?? 0, 0, ',', '.') }}</p>
                        <div class="flex justify-between">
                            <span class="text-bq-text-muted">Minimum transaksi</span>
                            <span class="font-medium text-bq-text">Rp {{ number_format($v->min_spending ?? $v->min_order_amount ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-bq-text-muted">Penggunaan:</span>
                            <span class="font-medium text-bq-text">
                                @if ($v->usage_limit)
                                    <x-ui.mini-bar :value="$v->used_count" :total="$v->usage_limit" unit="dipakai" />
                                @else
                                    {{ $v->used_count }} dipakai · Tanpa batas
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-bq-text-muted">Masa Berlaku:</span>
                            <span class="font-medium text-bq-text-subtle bq-num text-bq-small">
                                @if($v->start_date && $v->end_date)
                                    {{ \Carbon\Carbon::parse($v->start_date)->translatedFormat('j M') }} - {{ \Carbon\Carbon::parse($v->end_date)->translatedFormat('j M Y') }}
                                @elseif($v->end_date)
                                    s/d {{ \Carbon\Carbon::parse($v->end_date)->translatedFormat('j M Y') }}
                                @else
                                    Selamanya
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Footer Controls --}}
                <div class="mt-5 pt-3 border-t border-bq-border flex items-center justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-bq-small text-bq-text-muted">{{ $cards[$v->id]['state'] }}</span>
                        <form method="POST" action="{{ route($promoRoute.'.toggle', $v->id) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs bq-link">{{ $v->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        </form>
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="openEdit(@js($vPayload))" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-bq-surface-2 hover:text-bq-text transition" title="Edit" id="btn-edit-voucher-{{ $v->id }}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <form method="POST" action="{{ route($promoRoute.'.destroy', $v->id) }}" id="form-delete-voucher-{{ $v->id }}">
                            @csrf
                            @method('DELETE')
                            <button type="button"
                                @click="$dispatch('open-confirm', { title: 'Hapus promo?', message: 'Yakin ingin menghapus promo ' + @js($v->code) + '?', formId: $el.form.id })"
                                class="rounded-lg p-1.5 text-bq-text-muted hover:bg-bq-danger-bg hover:text-bq-danger transition" title="Hapus" id="btn-delete-voucher-{{ $v->id }}">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-bq-border bg-bq-surface p-12 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-bq-primary-light text-bq-primary">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-bq-text">Belum ada promo</h3>
                <p class="mt-1 text-xs text-bq-text-muted max-w-sm mx-auto">Tingkatkan pemesanan dengan memberikan promo diskon spesial untuk calon pelanggan Anda.</p>
                <button type="button" @click="addModalOpen = true" class="bq-btn bq-btn-primary bq-btn-sm mt-4">
                    + Buat promo
                </button>
            </div>
        @endforelse
    </div>

    {{-- Add Voucher Modal --}}
    <div x-show="addModalOpen" class="fixed inset-0 z-50 flex justify-center overflow-y-auto p-4 bg-bq-ink/40 backdrop-blur-xs" style="display: none;" x-cloak>
        <div class="my-auto w-full max-w-md rounded-2xl bg-bq-surface p-6 shadow-2xl border border-bq-border" @click.outside="addModalOpen = false">
            <h3 class="text-base font-bold text-bq-text">Buat Kode Promo Baru</h3>
            <p class="text-xs text-bq-text-muted mt-1">Konfigurasi nilai diskon, kuota pemakaian, dan masa berlaku promo.</p>
            <form method="POST" action="{{ route($promoRoute.'.store') }}" class="mt-4 space-y-3.5" id="form-add-voucher">
                @csrf
                <input type="hidden" name="_form" value="promo-create">
                <div>
                    <label class="text-xs font-semibold text-bq-text">Kode Promo <span class="text-bq-danger">*</span></label>
                    <input type="text" name="code" value="{{ $promoCreating ? old('code') : '' }}" required placeholder="Contoh: MERDEKA20" class="bq-input mt-1 w-full font-mono uppercase">
                        <x-owner.field-error name="code" form="promo-create" />
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Tipe Diskon <span class="text-bq-danger">*</span></label>
                        <select name="discount_type" x-model="newVoucher.discount_type" class="bq-input mt-1 w-full">
                            <option value="percentage">Persentase (%)</option>
                            <option value="fixed">Nominal Tetap (Rp)</option>
                        </select>
                        <x-owner.field-error name="discount_type" form="promo-create" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Nilai Diskon <span class="text-bq-danger">*</span></label>
                        <input type="number" name="discount_value" value="{{ $promoCreating ? old('discount_value') : '' }}" required min="1" step="0.01" :max="newVoucher.discount_type === 'percentage' ? 100 : null" placeholder="20 atau 25000" class="bq-input mt-1 w-full">
                        <x-owner.field-error name="discount_value" form="promo-create" />
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Min. Transaksi (Rp)</label>
                        <input type="number" name="min_order_amount" value="{{ $promoCreating ? old('min_order_amount') : '' }}" min="0" placeholder="0" class="bq-input mt-1 w-full">
                        <x-owner.field-error name="min_order_amount" form="promo-create" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Batas Kuota Pemakaian</label>
                        <input type="number" name="usage_limit" value="{{ $promoCreating ? old('usage_limit') : '' }}" min="1" placeholder="Kosongkan jika tanpa batas" class="bq-input mt-1 w-full">
                        <x-owner.field-error name="usage_limit" form="promo-create" />
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Mulai Berlaku</label>
                        <input type="date" name="start_date" value="{{ $promoCreating ? old('start_date') : '' }}" class="bq-input mt-1 w-full">
                        <x-owner.field-error name="start_date" form="promo-create" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Berakhir Pada</label>
                        <input type="date" name="end_date" value="{{ $promoCreating ? old('end_date') : '' }}" class="bq-input mt-1 w-full">
                        <x-owner.field-error name="end_date" form="promo-create" />
                    </div>
                </div>
                @include('owner.partials.promo-limits', ['model' => 'newVoucher', 'errorForm' => 'promo-create'])
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-bq-border">
                    <button type="button" @click="addModalOpen = false" class="bq-btn bq-btn-secondary">Batal</button>
                    <button type="submit" class="bq-btn bq-btn-primary bq-btn-sm">Simpan promo</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Voucher Modal --}}
    <div x-show="editModalOpen" class="fixed inset-0 z-50 flex justify-center overflow-y-auto p-4 bg-bq-ink/40 backdrop-blur-xs" style="display: none;" x-cloak>
        <div class="my-auto w-full max-w-md rounded-2xl bg-bq-surface p-6 shadow-2xl border border-bq-border" @click.outside="editModalOpen = false">
            <h3 class="text-base font-bold text-bq-text">Edit Kode Promo</h3>
            <p class="text-xs text-bq-text-muted mt-1">Perbarui ketentuan diskon dan ketersediaan promo.</p>
            <form method="POST" :action="@js(route($promoRoute.'.update', ['id' => '__ID__'])).replace('__ID__', encodeURIComponent(activeVoucher.id))" class="mt-4 space-y-3.5" id="form-edit-voucher">
                @csrf
                <input type="hidden" name="_form" :value="'promo-edit-' + activeVoucher.id">
                @method('PUT')
                <div>
                    <label class="text-xs font-semibold text-bq-text">Kode Promo <span class="text-bq-danger">*</span></label>
                    <input type="text" name="code" x-model="activeVoucher.code" required class="bq-input mt-1 w-full font-mono uppercase">
                        <x-owner.field-error name="code" :form="$promoErrorForm" x-cloak x-show="@js($promoErrorForm) === 'promo-edit-' + activeVoucher.id" />
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Tipe Diskon <span class="text-bq-danger">*</span></label>
                        <select name="discount_type" x-model="activeVoucher.discount_type" class="bq-input mt-1 w-full">
                            <option value="percentage">Persentase (%)</option>
                            <option value="fixed">Nominal Tetap (Rp)</option>
                        </select>
                        <x-owner.field-error name="discount_type" :form="$promoErrorForm" x-cloak x-show="@js($promoErrorForm) === 'promo-edit-' + activeVoucher.id" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Nilai Diskon <span class="text-bq-danger">*</span></label>
                        <input type="number" name="discount_value" x-model="activeVoucher.discount_value" required min="1" step="0.01" :max="activeVoucher.discount_type === 'percentage' ? 100 : null" class="bq-input mt-1 w-full">
                        <x-owner.field-error name="discount_value" :form="$promoErrorForm" x-cloak x-show="@js($promoErrorForm) === 'promo-edit-' + activeVoucher.id" />
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Min. Transaksi (Rp)</label>
                        <input type="number" name="min_order_amount" x-model="activeVoucher.min_order_amount" min="0" class="bq-input mt-1 w-full">
                        <x-owner.field-error name="min_order_amount" :form="$promoErrorForm" x-cloak x-show="@js($promoErrorForm) === 'promo-edit-' + activeVoucher.id" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Batas Kuota Pemakaian</label>
                        <input type="number" name="usage_limit" x-model="activeVoucher.usage_limit" min="1" placeholder="Tanpa batas" class="bq-input mt-1 w-full">
                        <x-owner.field-error name="usage_limit" :form="$promoErrorForm" x-cloak x-show="@js($promoErrorForm) === 'promo-edit-' + activeVoucher.id" />
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Mulai Berlaku</label>
                        <input type="date" name="start_date" x-model="activeVoucher.start_date" class="bq-input mt-1 w-full">
                        <x-owner.field-error name="start_date" :form="$promoErrorForm" x-cloak x-show="@js($promoErrorForm) === 'promo-edit-' + activeVoucher.id" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Berakhir Pada</label>
                        <input type="date" name="end_date" x-model="activeVoucher.end_date" class="bq-input mt-1 w-full">
                        <x-owner.field-error name="end_date" :form="$promoErrorForm" x-cloak x-show="@js($promoErrorForm) === 'promo-edit-' + activeVoucher.id" />
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Status</label>
                    <select name="is_active" x-model="activeVoucher.is_active" class="bq-input mt-1 w-full">
                        <option :value="1">Aktif</option>
                        <option :value="0">Nonaktif</option>
                    </select>
                        <x-owner.field-error name="is_active" :form="$promoErrorForm" x-cloak x-show="@js($promoErrorForm) === 'promo-edit-' + activeVoucher.id" />
                </div>
                @include('owner.partials.promo-limits', ['model' => 'activeVoucher', 'errorForm' => $promoErrorForm])
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-bq-border">
                    <button type="button" @click="editModalOpen = false" class="bq-btn bq-btn-secondary">Batal</button>
                    <button type="submit" class="bq-btn bq-btn-primary bq-btn-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
