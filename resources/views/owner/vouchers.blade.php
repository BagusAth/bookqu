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
            <button type="button" @click="addModalOpen = true" class="bq-btn bq-btn-primary" id="btn-add-voucher"><x-owner.icon name="plus" class="h-4 w-4" />Buat promo</button>
        </x-slot:action>
    </x-owner.page-header>

    {{-- BQ-162: Promo as in the Papan Jadwal prototype (#/promo): one figure panel, tabs with counts, short cards. --}}
    <x-ui.panel>
        <x-ui.figures :value="\App\Support\Rupiah::format($promoFigures['revenue'])" label="pendapatan dari booking berpromo"
            context="Sejak promo pertama dipakai. Batang di kartu = pemakaian sejak promo dimulai."
            :more="[
                ['value' => $promoFigures['active'], 'label' => 'promo aktif'],
                ['value' => $promoFigures['uses'].' kali', 'label' => 'dipakai'],
                ['value' => \App\Support\Rupiah::format($promoFigures['discount']), 'label' => 'potongan diberikan'],
            ]" />
    </x-ui.panel>
    <x-owner.tabs :tabs="$promoTabs" :active="$tab" :counts="$tabCounts" label="Status promo" />

    @if ($search)
        <a class="bq-btn bq-btn-secondary bq-btn-sm" href="{{ route($promoRoute, ['tab' => $tab]) }}">Cari: {{ $search }} <x-owner.icon name="x" class="h-3.5 w-3.5" /></a>
    @endif

    @if ($vouchers->isEmpty() && ! ($tab === 'active' && $quiet))
        <section class="bq-panel">
            <x-ui.empty icon="tag" :title="$tab === 'all' ? 'Belum ada promo' : 'Tidak ada promo di sini'"
                description="Buat kode potongan untuk pelanggan, dengan kuota dan masa berlaku.">
                <x-slot:action><x-ui.button variant="primary" icon="plus" @click="addModalOpen = true">Buat promo</x-ui.button></x-slot:action>
            </x-ui.empty>
        </section>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($vouchers as $v)
                @php
                    $card = $cards[$v->id];
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
                <article @class(['bq-panel flex min-w-0 flex-col gap-3 p-4', 'opacity-70' => $card['state'] === 'Berakhir' || $card['state'] === 'Nonaktif'])>
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            @if ($card['almostOut'] && $card['state'] === 'Aktif')
                                <x-ui.badge tone="warning" class="mb-1">Hampir habis</x-ui.badge>
                            @elseif ($card['state'] !== 'Aktif')
                                <x-ui.badge :tone="$card['state'] === 'Terjadwal' ? 'neutral' : 'danger'" class="mb-1">{{ $card['state'] }}</x-ui.badge>
                            @endif
                            <button type="button" class="block text-left" @click="openEdit({{ \Illuminate\Support\Js::from($vPayload) }})" id="btn-edit-voucher-{{ $v->id }}" title="Ubah promo {{ $v->code }}">
                                <span class="bq-num block font-bq-display text-bq-figure-small font-semibold text-bq-ink">{{ $card['value'] }}</span>
                            </button>
                            <p class="text-bq-small text-bq-text-muted">Berlaku {{ $card['period'] }}</p>
                            @if ($card['limits'])
                                <p class="text-bq-small text-bq-text-muted" data-promo-limits>Hanya {{ $card['limits'] }}</p>
                            @endif
                        </div>
                        <form method="POST" action="{{ route($promoRoute.'.destroy', $v->id) }}" id="form-delete-voucher-{{ $v->id }}">
                            @csrf
                            @method('DELETE')
                            <x-ui.menu :id="'promo-actions-'.$v->id" :label="'Tindakan '.$v->code">
                                <button type="button" role="menuitem" tabindex="-1" @click="openEdit({{ \Illuminate\Support\Js::from($vPayload) }})">Ubah</button>
                                <button type="submit" role="menuitem" tabindex="-1" form="toggle-voucher-{{ $v->id }}">{{ $v->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                <button type="button" role="menuitem" tabindex="-1" class="text-bq-danger" id="btn-delete-voucher-{{ $v->id }}"
                                    @click="$dispatch('open-confirm', { title: 'Hapus promo?', message: 'Yakin ingin menghapus promo ' + @js($v->code) + '?', formId: 'form-delete-voucher-{{ $v->id }}' })">Hapus</button>
                            </x-ui.menu>
                        </form>
                        <form method="POST" action="{{ route($promoRoute.'.toggle', $v->id) }}" id="toggle-voucher-{{ $v->id }}" class="hidden">@csrf @method('PATCH')</form>
                    </div>
                    <div class="flex min-w-0 items-center gap-2 rounded-bq-control bg-bq-background px-2.5 py-1.5">
                        <span class="min-w-0 flex-1 truncate font-bq-display font-semibold tracking-wide text-bq-ink" title="{{ $v->code }}">{{ $v->code }}</span>
                        <button type="button" @click="copyCode(@js($v->code))" class="text-bq-text-muted hover:text-bq-primary" aria-label="Salin kode {{ $v->code }}" title="Salin kode">
                            <x-owner.icon name="copy" class="h-4 w-4" />
                        </button>
                    </div>
                    @if ($v->usage_limit)
                        <x-ui.mini-bar :value="$v->used_count" :total="$v->usage_limit" unit="dipakai" />
                    @else
                        <p class="text-bq-small text-bq-text-muted">{{ $v->used_count }} kali dipakai · tanpa batas</p>
                    @endif
                </article>
            @endforeach
            @if ($tab === 'active' && $quiet)
                <article class="bq-panel flex min-w-0 flex-col gap-2 p-4">
                    <p class="font-semibold text-bq-ink">Promo untuk jam sepi</p>
                    <p class="text-bq-small text-bq-text-muted">30 hari terakhir paling sepi pada {{ $quiet['day'] }}, sekitar pukul {{ $quiet['hour'] }}. Batasi promo ke hari dan jam itu.</p>
                    <div class="mt-1"><x-ui.button size="sm" icon="tag" @click="addModalOpen = true">Buat promo jam sepi</x-ui.button></div>
                </article>
            @endif
        </div>
    @endif

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
                        <x-owner.field-error name="code" :form="$promoErrorForm" x-cloak x-show="{{ \Illuminate\Support\Js::from($promoErrorForm) }} === 'promo-edit-' + activeVoucher.id" />
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Tipe Diskon <span class="text-bq-danger">*</span></label>
                        <select name="discount_type" x-model="activeVoucher.discount_type" class="bq-input mt-1 w-full">
                            <option value="percentage">Persentase (%)</option>
                            <option value="fixed">Nominal Tetap (Rp)</option>
                        </select>
                        <x-owner.field-error name="discount_type" :form="$promoErrorForm" x-cloak x-show="{{ \Illuminate\Support\Js::from($promoErrorForm) }} === 'promo-edit-' + activeVoucher.id" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Nilai Diskon <span class="text-bq-danger">*</span></label>
                        <input type="number" name="discount_value" x-model="activeVoucher.discount_value" required min="1" step="0.01" :max="activeVoucher.discount_type === 'percentage' ? 100 : null" class="bq-input mt-1 w-full">
                        <x-owner.field-error name="discount_value" :form="$promoErrorForm" x-cloak x-show="{{ \Illuminate\Support\Js::from($promoErrorForm) }} === 'promo-edit-' + activeVoucher.id" />
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Min. Transaksi (Rp)</label>
                        <input type="number" name="min_order_amount" x-model="activeVoucher.min_order_amount" min="0" class="bq-input mt-1 w-full">
                        <x-owner.field-error name="min_order_amount" :form="$promoErrorForm" x-cloak x-show="{{ \Illuminate\Support\Js::from($promoErrorForm) }} === 'promo-edit-' + activeVoucher.id" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Batas Kuota Pemakaian</label>
                        <input type="number" name="usage_limit" x-model="activeVoucher.usage_limit" min="1" placeholder="Tanpa batas" class="bq-input mt-1 w-full">
                        <x-owner.field-error name="usage_limit" :form="$promoErrorForm" x-cloak x-show="{{ \Illuminate\Support\Js::from($promoErrorForm) }} === 'promo-edit-' + activeVoucher.id" />
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Mulai Berlaku</label>
                        <input type="date" name="start_date" x-model="activeVoucher.start_date" class="bq-input mt-1 w-full">
                        <x-owner.field-error name="start_date" :form="$promoErrorForm" x-cloak x-show="{{ \Illuminate\Support\Js::from($promoErrorForm) }} === 'promo-edit-' + activeVoucher.id" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Berakhir Pada</label>
                        <input type="date" name="end_date" x-model="activeVoucher.end_date" class="bq-input mt-1 w-full">
                        <x-owner.field-error name="end_date" :form="$promoErrorForm" x-cloak x-show="{{ \Illuminate\Support\Js::from($promoErrorForm) }} === 'promo-edit-' + activeVoucher.id" />
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Status</label>
                    <select name="is_active" x-model="activeVoucher.is_active" class="bq-input mt-1 w-full">
                        <option :value="1">Aktif</option>
                        <option :value="0">Nonaktif</option>
                    </select>
                        <x-owner.field-error name="is_active" :form="$promoErrorForm" x-cloak x-show="{{ \Illuminate\Support\Js::from($promoErrorForm) }} === 'promo-edit-' + activeVoucher.id" />
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
