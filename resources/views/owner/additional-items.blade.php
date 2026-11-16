@extends('layouts.owner-layout')

@section('title', 'Add-on')

@section('content')
@php
    $oldForm = $errors->any() && is_string(old('_form')) ? old('_form') : '';
    $editingId = preg_match('/^addon-edit-([1-9][0-9]*)$/', $oldForm, $match) ? (int) $match[1] : null;
    $restore = ($oldForm === 'addon-create' || $editingId !== null)
        ? \Illuminate\Support\Arr::only(old(), ['name', 'description', 'price', 'stock', 'is_active', 'service_ids']) + ['id' => $editingId]
        : null;
    $input = 'mt-1 w-full bq-input';
@endphp
<div class="mx-auto max-w-7xl space-y-5" x-data="{
    item: {},
    formKey: '',
    edit(data, trigger) {
        this.item = { name: '', price: '', description: '', stock: '', is_active: '1', service_ids: [], ...data };
        this.item.service_ids = Array.isArray(this.item.service_ids) ? this.item.service_ids.map(String) : [];
        this.formKey = this.item.id ? 'addon-edit-' + this.item.id : 'addon-create';
        $dispatch('open-dialog', { id: 'addon-form', trigger });
    },
    init() {
        const old = @js($restore);
        if (old) this.$nextTick(() => this.edit(old));
    }
}">
    <x-owner.page-header title="Add-on" purpose="Kelola produk tambahan yang dapat dipesan bersama layanan.">
        <x-slot:action>
            <button id="btn-add-addon" type="button" @click="edit(null, $el)" class="bq-btn bq-btn-primary">Tambah add-on</button>
        </x-slot:action>
    </x-owner.page-header>

    <x-owner.tabs :tabs="['layanan' => 'Layanan', 'kategori' => 'Kategori', 'add-ons' => 'Add-on']" active="add-ons" label="Bagian halaman Layanan" />

    <form method="GET" action="{{ route('owner.services') }}" role="search" class="flex flex-wrap items-end gap-2">
        <input type="hidden" name="tab" value="add-ons">
        <label class="text-sm">Cari add-on
            <input name="search" value="{{ $search }}" class="{{ $input }}" type="search">
        </label>
        <button class="bq-btn bq-btn-secondary">Cari</button>
    </form>

    <div class="grid gap-3 lg:grid-cols-2">
        @forelse ($items as $item)
            @php
                $payload = $item->only(['id', 'name', 'description', 'price', 'stock']) + [
                    'is_active' => $item->is_active ? '1' : '0',
                    'service_ids' => $item->services->modelKeys(),
                ];
            @endphp
            <article class="flex flex-col gap-3 bq-panel p-4">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <h2 class="font-semibold">{{ $item->name }}</h2>
                    <x-owner.status-badge type="availability" :status="$item->is_active ? 'active' : 'inactive'" />
                </div>
                @if ($item->description)
                    <p class="break-words text-sm text-bq-text-muted">{{ $item->description }}</p>
                @endif
                <dl class="grid grid-cols-3 gap-2 text-sm">
                    <div><dt class="text-bq-text-muted">Harga</dt><dd class="font-medium">Rp {{ number_format((float) $item->price, 0, ',', '.') }}</dd></div>
                    <div><dt class="text-bq-text-muted">Stok</dt><dd class="font-medium">{{ $item->stock ?? 'Tanpa batas' }}</dd></div>
                    <div><dt class="text-bq-text-muted">Terjual</dt> <dd class="font-medium">{{ $sales[$item->id] ?? 0 }}</dd></div>
                </dl>
                <p class="text-sm"><span class="text-bq-text-muted">Layanan:</span> {{ $item->services->pluck('namalayanan')->implode(', ') ?: 'Belum ditautkan' }}</p>
                <div class="mt-auto flex flex-wrap items-center gap-3 border-t border-bq-border pt-3 text-sm font-semibold">
                    <button type="button" @click="edit(@js($payload), $el)" class="text-bq-primary">Ubah</button>
                    <form method="POST" action="{{ route($itemRoute.'.toggle', $item->id) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="text-bq-primary">{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    </form>
                    <form id="delete-addon-{{ $item->id }}" method="POST" action="{{ route($itemRoute.'.destroy', $item->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="text-bq-danger"
                            @click="$dispatch('open-confirm', { title: 'Hapus add-on?', message: 'Yakin ingin menghapus ' + @js($item->name) + '?', formId: $el.form.id, tone: 'danger' })">Hapus</button>
                    </form>
                </div>
            </article>
        @empty
            <x-owner.empty-state :title="$search !== '' ? 'Add-on tidak ditemukan' : 'Belum ada add-on'"
                :description="$search !== '' ? 'Coba kata kunci lain.' : 'Tambahkan produk yang dapat dipilih saat pelanggan memesan layanan.'" />
        @endforelse
    </div>

    <x-owner.dialog id="addon-form" title="Add-on" size="lg">
        <form method="POST" class="space-y-4"
            :action="item.id ? @js(route($itemRoute.'.update', ['id' => '__ID__'])).replace('__ID__', encodeURIComponent(item.id)) : @js(route($itemRoute.'.store'))">
            @csrf
            <input type="hidden" name="_method" value="PUT" :disabled="!item.id">
            <input type="hidden" name="_form" :value="formKey">
            <div>
                <label for="addon-name" class="font-medium">Nama add-on</label>
                <input id="addon-name" name="name" x-model="item.name" required maxlength="150" class="{{ $input }}">
                <x-owner.field-error name="name" x-cloak x-show="formKey === @js($oldForm)" />
            </div>
            <div>
                <label for="addon-description" class="font-medium">Deskripsi</label>
                <textarea id="addon-description" name="description" x-model="item.description" maxlength="500" rows="2" class="{{ $input }}"></textarea>
                <x-owner.field-error name="description" x-cloak x-show="formKey === @js($oldForm)" />
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label for="addon-price" class="font-medium">Harga (Rp)</label>
                    <input id="addon-price" name="price" type="number" x-model="item.price" step="0.01" min="0" required class="{{ $input }}">
                    <x-owner.field-error name="price" x-cloak x-show="formKey === @js($oldForm)" />
                </div>
                <div>
                    <label for="addon-stock" class="font-medium">Stok</label>
                    <input id="addon-stock" name="stock" type="number" x-model="item.stock" min="0" placeholder="Tanpa batas" class="{{ $input }}">
                    <p class="mt-1 text-xs text-bq-text-muted">Kosongkan untuk stok tanpa batas. Stok berkurang setelah pembayaran booking berhasil.</p>
                    <x-owner.field-error name="stock" x-cloak x-show="formKey === @js($oldForm)" />
                </div>
            </div>
            <fieldset class="space-y-2">
                <legend class="font-medium">Layanan yang ditautkan</legend>
                @forelse ($services as $service)
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" x-model="item.service_ids">
                        {{ $service->namalayanan }}
                    </label>
                @empty
                    <p class="text-sm text-bq-text-muted">Tambahkan layanan untuk menautkannya ke add-on ini.</p>
                @endforelse
                <x-owner.field-error name="service_ids" x-cloak x-show="formKey === @js($oldForm)" />
                <x-owner.field-error name="service_ids.*" x-cloak x-show="formKey === @js($oldForm)" />
            </fieldset>
            <div>
                <label for="addon-active" class="font-medium">Status</label>
                <select id="addon-active" name="is_active" x-model="item.is_active" class="{{ $input }}">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
                <x-owner.field-error name="is_active" x-cloak x-show="formKey === @js($oldForm)" />
            </div>
            <button type="submit" class="bq-btn bq-btn-primary">Simpan add-on</button>
        </form>
    </x-owner.dialog>
</div>
@endsection
