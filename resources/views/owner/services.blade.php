@extends('layouts.owner-layout')

@section('title', 'Layanan')

{{--
    Layanan (BQ-132, owner-portal.md §8.6): tab Layanan (services, usage meter) and tab Kategori.
    Data comes from App\Actions\Service\GetLayananPage; this view only renders it.
--}}

@php
    $isServicesTab = $activeTab === 'layanan';
    $oldForm = $errors->any() ? (string) old('_form') : '';
    $oldContext = str_starts_with($oldForm, 'service-') ? 'service' : (str_starts_with($oldForm, 'category-') ? 'category' : null);
    $serviceUpdateUrl = route('owner.services.update', ['program' => '__ID__']);
    $serviceToggleUrl = route('owner.services.toggle', ['id' => '__ID__']);
    $serviceDestroyUrl = route('owner.services.destroy', ['program' => '__ID__']);
    $categoryUpdateUrl = route('owner.categories.update', ['id' => '__ID__']);
    $categoryDestroyUrl = route('owner.categories.destroy', ['id' => '__ID__']);
    $inputClass = 'mt-1 block w-full bq-input';
    $labelClass = 'block bq-label';
    $buttonSecondary = 'bq-btn bq-btn-secondary bq-btn-sm';
    $buttonPrimary = 'bq-btn bq-btn-primary';
@endphp

@section('content')
<div
    class="mx-auto max-w-7xl space-y-6"
    x-data="{
        service: {},
        category: {},
        blankService() {
            return { id: null, namalayanan: '', idcategory: '', harga: '', satuan_harga: 'sesi', durasi: 60, satuan_durasi: 'menit', deskripsi: '', is_popular: false, auto_open_slots: false, is_active: true, imageUrl: null, remove_image: false, kapasitas: 1, unit_ids: [], additional_item_ids: [], image_asset_id: '' };
        },
        blankCategory() {
            return { id: null, name: '', description: '', color: 'purple', sort_order: 0, is_active: true, imageUrl: null, remove_image: false };
        },
        openService(data, trigger) {
            this.service = Object.assign(this.blankService(), data || {});
            $dispatch('open-dialog', { id: 'service-form-dialog', trigger });
        },
        openCategory(data, trigger) {
            this.category = Object.assign(this.blankCategory(), data || {});
            $dispatch('open-dialog', { id: 'category-form-dialog', trigger });
        },
        url(template, id) {
            return template.replace('__ID__', encodeURIComponent(id));
        },
        init() {
            this.service = this.blankService();
            this.category = this.blankCategory();
            const old = @js($oldContext ? old() : null);
            if (!old) {
                if (@js($isServicesTab && $usage['canCreate'] && request()->boolean('buat'))) {
                    this.$nextTick(() => this.openService(null));
                }
                return;
            }
            const toBool = (value) => value === true || value === '1' || value === 1;
            this.$nextTick(() => {
                if (@js($oldContext) === 'service') {
                    this.openService({
                        id: old.editing_id || null, namalayanan: old.namalayanan || '', idcategory: old.idcategory || '',
                        harga: old.harga || '', satuan_harga: old.satuan_harga || 'sesi', durasi: old.durasi || 60,
                        satuan_durasi: old.satuan_durasi || 'menit', deskripsi: old.deskripsi || '',
                        is_popular: toBool(old.is_popular), auto_open_slots: toBool(old.auto_open_slots), is_active: toBool(old.is_active),
                        image_asset_id: String(old.image_asset_id ?? ''), remove_image: toBool(old.remove_image),
                        additional_item_ids: Array.isArray(old.additional_item_ids) ? old.additional_item_ids : [],
                        kapasitas: old.kapasitas ?? 1, unit_ids: Array.isArray(old.unit_ids) ? old.unit_ids : [],
                    }, null);
                } else if (@js($oldContext) === 'category') {
                    this.openCategory({
                        id: old.editing_id || null, name: old.name || '', description: old.description || '',
                        color: old.color || 'purple', sort_order: old.sort_order || 0, is_active: toBool(old.is_active),
                    }, null);
                }
            });
        },
    }"
>
    <x-owner.page-header title="Layanan" purpose="Yang Anda jual: harga, durasi, kapasitas dan tambahan.">
        <x-slot:action>
            @if ($isServicesTab && $usage['canCreate'])
                <button type="button" id="service-create-button" class="{{ $buttonPrimary }}" x-on:click="openService(null, $el)">
                    <x-owner.icon name="plus" class="h-4 w-4" /> Tambah layanan
                </button>
            @elseif (! $isServicesTab)
                <button type="button" id="category-create-button" class="{{ $buttonPrimary }}" x-on:click="openCategory(null, $el)">
                    <x-owner.icon name="plus" class="h-4 w-4" /> Tambah kategori
                </button>
            @endif
        </x-slot:action>
    </x-owner.page-header>

    <x-owner.tabs :tabs="['layanan' => 'Layanan', 'kategori' => 'Kategori'] + (($showAddOns ?? false) ? ['add-ons' => 'Tambahan'] : [])" :active="$activeTab" :counts="$tabCounts ?? []" label="Bagian halaman Layanan" />

    @include($isServicesTab ? 'owner.services.layanan' : 'owner.services.kategori')
</div>
@endsection
