@extends('layouts.owner-layout')

@section('title', 'Additional Items')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    search: '{{ addslashes($search ?? '') }}',
    addModalOpen: false,
    editModalOpen: false,
    activeItem: { id: null, name: '', description: '', price: 0, stock: null, is_active: 1, service_ids: [] },
    openEdit(item) {
        this.activeItem = { ...item };
        this.editModalOpen = true;
    }
}">

    {{-- ── Header ── --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-bq-text">Additional Items (Add-ons)</h1>
            <p class="text-sm text-bq-text-muted mt-1">Kelola produk atau perlengkapan tambahan yang dapat dibeli customer saat checkout reservasi.</p>
        </div>
        <button type="button" @click="addModalOpen = true" class="inline-flex items-center gap-2 rounded-xl bg-bq-primary px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-bq-primary-hover transition" id="btn-add-addon">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            + Tambah Add-on
        </button>
    </div>

    {{-- ── Search & Metrics ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('owner.additional-items') }}" class="relative w-full sm:max-w-xs">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari add-on..." class="w-full rounded-xl border border-bq-border bg-bq-surface py-2.5 pl-10 pr-4 text-xs text-bq-text placeholder-bq-text-subtle transition focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
        </form>
        <div class="text-xs text-bq-text-muted">
            Total <span class="font-bold text-bq-text">{{ $items->count() }}</span> add-on terdaftar
        </div>
    </div>

    {{-- ── Additional Items Table ── --}}
    <div class="rounded-2xl border border-bq-border bg-bq-surface shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-bq-border bg-bq-background/60 font-semibold uppercase tracking-wider text-bq-text-muted">
                        <th class="px-5 py-3.5">Item Name</th>
                        <th class="px-5 py-3.5">Price</th>
                        <th class="px-5 py-3.5">Applicable Services</th>
                        <th class="px-5 py-3.5">Stock</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bq-border">
                    @forelse($items as $item)
                        @php
                            $itemPayload = [
                                'id'          => $item->id,
                                'name'        => $item->name,
                                'description' => $item->description ?? '',
                                'price'       => (float) $item->price,
                                'stock'       => $item->stock,
                                'is_active'   => (int) $item->is_active,
                                'service_ids' => $item->services->pluck('id')->toArray(),
                            ];
                        @endphp
                        <tr class="hover:bg-bq-background/40 transition">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 font-bold text-xs">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-bq-text text-sm">{{ $item->name }}</span>
                                        @if($item->description)
                                            <p class="text-[11px] text-bq-text-muted line-clamp-1">{{ $item->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap font-bold text-bq-primary">
                                Rp {{ number_format($item->price, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($item->services as $svc)
                                        <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-700">
                                            {{ $svc->namalayanan }}
                                        </span>
                                    @empty
                                        <span class="text-bq-text-subtle text-[11px]">Semua Layanan</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-bq-text-muted font-mono">
                                {{ $item->stock !== null ? $item->stock . ' Unit' : 'Unlimited' }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                <form method="POST" action="{{ route('owner.additional-items.toggle', $item->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold transition {{ $item->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                        <span>{{ $item->is_active ? 'Active' : 'Inactive' }}</span>
                                    </button>
                                </form>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @click="openEdit(@json($itemPayload))" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-slate-100 hover:text-bq-text transition" title="Edit" id="btn-edit-addon-{{ $item->id }}">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <form method="POST" action="{{ route('owner.additional-items.destroy', $item->id) }}" id="form-delete-item-{{ $item->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                            @click="$dispatch('open-confirm', { title: 'Hapus Add-on?', message: 'Yakin ingin menghapus {{ $item->name }}?', formId: 'form-delete-item-{{ $item->id }}' })"
                                            class="rounded-lg p-1.5 text-bq-text-muted hover:bg-rose-50 hover:text-rose-600 transition" title="Hapus" id="btn-delete-addon-{{ $item->id }}">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-bq-text-muted">
                                Belum ada add-on tambahan. Klik "+ Tambah Add-on" untuk menambahkan perlengkapan atau layanan ekstra.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add Item Modal --}}
    <div x-show="addModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;" x-cloak>
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="addModalOpen = false">
            <h3 class="text-base font-bold text-bq-text">Tambah Add-on / Item Tambahan</h3>
            <p class="text-xs text-bq-text-muted mt-1">Produk atau layanan opsional yang dapat dipilih customer saat booking.</p>
            <form method="POST" action="{{ route('owner.additional-items.store') }}" class="mt-4 space-y-4" id="form-add-addon">
                @csrf
                <div>
                    <label class="text-xs font-semibold text-bq-text">Nama Item <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: Sewa Raket Tambahan" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Harga (Rp) <span class="text-rose-500">*</span></label>
                        <input type="number" name="price" required min="0" step="500" placeholder="25000" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Stok (Opsional)</label>
                        <input type="number" name="stock" min="0" placeholder="Kosongkan jika unlimited" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Deskripsi</label>
                    <textarea name="description" rows="2" placeholder="Keterangan singkat item..." class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"></textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Berlaku untuk Layanan</label>
                    <div class="mt-2 max-h-32 overflow-y-auto space-y-1.5 rounded-xl border border-bq-border p-2.5">
                        @foreach($services as $svc)
                            <label class="flex items-center gap-2 text-xs text-bq-text cursor-pointer">
                                <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}" class="rounded border-bq-border text-bq-primary">
                                <span>{{ $svc->namalayanan }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-bq-border">
                    <button type="button" @click="addModalOpen = false" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Simpan Add-on</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Item Modal --}}
    <div x-show="editModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;" x-cloak>
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="editModalOpen = false">
            <h3 class="text-base font-bold text-bq-text">Edit Add-on</h3>
            <p class="text-xs text-bq-text-muted mt-1">Perbarui harga, stok, atau layanan yang berlaku.</p>
            <form method="POST" :action="`/owner/additional-items/${activeItem.id}`" class="mt-4 space-y-4" id="form-edit-addon">
                @csrf
                @method('PUT')
                <div>
                    <label class="text-xs font-semibold text-bq-text">Nama Item <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="activeItem.name" required class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Harga (Rp) <span class="text-rose-500">*</span></label>
                        <input type="number" name="price" x-model="activeItem.price" required min="0" step="500" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Stok (Opsional)</label>
                        <input type="number" name="stock" x-model="activeItem.stock" min="0" placeholder="Unlimited" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Deskripsi</label>
                    <textarea name="description" x-model="activeItem.description" rows="2" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"></textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Status</label>
                    <select name="is_active" x-model="activeItem.is_active" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                        <option :value="1">Active</option>
                        <option :value="0">Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Berlaku untuk Layanan</label>
                    <div class="mt-2 max-h-32 overflow-y-auto space-y-1.5 rounded-xl border border-bq-border p-2.5">
                        @foreach($services as $svc)
                            <label class="flex items-center gap-2 text-xs text-bq-text cursor-pointer">
                                <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}" :checked="activeItem.service_ids && activeItem.service_ids.includes({{ $svc->id }})" class="rounded border-bq-border text-bq-primary">
                                <span>{{ $svc->namalayanan }}</span>
                            </label>
                        @endforeach
                    </div>
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
