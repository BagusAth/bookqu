@extends('layouts.owner-layout')

@section('title', 'Additional Items')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    search: '{{ addslashes($search ?? '') }}',
    addModalOpen: false,
    editModalOpen: false,
    addUnlimited: true,
    editUnlimited: true,
    activeItem: { id: null, name: '', description: '', price: 0, stock: null, is_active: 1, service_ids: [] },
    openEdit(item) {
        this.activeItem = { ...item, service_ids: Array.isArray(item.service_ids) ? [...item.service_ids] : [] };
        this.editUnlimited = (item.stock === null || item.stock === undefined || item.stock === '');
        this.editModalOpen = true;
    },
    toggleItemService(id) {
        if (!this.activeItem.service_ids) this.activeItem.service_ids = [];
        const index = this.activeItem.service_ids.indexOf(id);
        if (index > -1) {
            this.activeItem.service_ids.splice(index, 1);
        } else {
            this.activeItem.service_ids.push(id);
        }
    }
}">

    {{-- ── Header ── --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#f3effe] text-[#382186] border border-[#e7e2f7] shadow-2xs">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </span>
                <h1 class="text-2xl font-extrabold tracking-tight text-[#231a3d] sm:text-3xl">Additional Items (Add-ons)</h1>
            </div>
            <p class="text-sm text-[#6e6584] mt-1">Kelola produk atau perlengkapan tambahan yang dapat dibeli customer saat checkout reservasi.</p>
        </div>
        <button
            type="button"
            @click="addModalOpen = true"
            class="craft-btn inline-flex items-center gap-2 rounded-xl bg-[#382186] px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-xs hover:bg-[#2d1a6d]"
            id="btn-add-addon"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            + Tambah Add-on
        </button>
    </div>

    {{-- ── Search & Metrics ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between rounded-2xl border border-[#e7e2f7] bg-white p-3.5 shadow-[0_4px_20px_rgba(35,26,61,0.03)]">
        <form method="GET" action="{{ route('owner.additional-items') }}" class="relative w-full sm:max-w-xs">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#6e6584]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                type="text"
                name="search"
                value="{{ $search ?? '' }}"
                placeholder="Cari add-on..."
                class="w-full rounded-xl border border-[#e7e2f7] bg-[#f7f7fa] py-2 pl-9 pr-4 text-xs text-[#231a3d] placeholder-[#6e6584] transition focus:border-[#382186] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs"
            >
        </form>
        <div class="text-xs text-[#6e6584] font-medium">
            Total <span class="font-extrabold text-[#231a3d]">{{ $items->count() }}</span> add-on terdaftar
        </div>
    </div>

    {{-- ── Additional Items Table ── --}}
    <div class="rounded-2xl border border-[#e7e2f7] bg-white shadow-[0_4px_24px_rgba(35,26,61,0.03)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-[#e7e2f7] bg-[#f7f7fa] font-extrabold uppercase tracking-wider text-[#6e6584]">
                        <th class="px-5 py-3.5">Item Name</th>
                        <th class="px-5 py-3.5">Price</th>
                        <th class="px-5 py-3.5">Applicable Services</th>
                        <th class="px-5 py-3.5">Stock</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#e7e2f7]">
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
                        <tr class="hover:bg-[#f7f7fa]/60 transition duration-150">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#f3effe] text-[#382186] font-bold text-xs border border-[#e7e2f7]">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                    </div>
                                    <div>
                                        <span class="font-extrabold text-[#231a3d] text-sm">{{ $item->name }}</span>
                                        @if($item->description)
                                            <p class="text-[11px] text-[#6e6584] line-clamp-1 leading-relaxed">{{ $item->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap font-black text-sm text-[#382186]">
                                Rp {{ number_format($item->price, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center gap-1">
                                    @forelse($item->services->take(2) as $svc)
                                        <span class="inline-flex rounded-md bg-[#f3effe] border border-[#b499ff]/40 px-2 py-0.5 text-[10px] font-bold text-[#382186]">
                                            {{ $svc->namalayanan }}
                                        </span>
                                    @empty
                                        <span class="text-[#6e6584] text-[11px] italic">Semua Layanan</span>
                                    @endforelse
                                    @if($item->services->count() > 2)
                                        <span
                                            class="rounded-md bg-[#f7f7fa] text-[#6e6584] border border-[#e7e2f7] px-1.5 py-0.5 text-[10px] font-bold cursor-help"
                                            title="{{ $item->services->pluck('namalayanan')->join(', ') }}"
                                        >
                                            +{{ $item->services->count() - 2 }} lainnya
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($item->stock === 0)
                                    <span class="inline-flex items-center rounded-md bg-rose-50 border border-rose-200 px-2.5 py-0.5 text-[11px] font-extrabold text-rose-700">
                                        Habis (0)
                                    </span>
                                @elseif($item->stock !== null && $item->stock <= 3)
                                    <span class="inline-flex items-center rounded-md bg-[#fff8eb] border border-[#ffb84d]/60 px-2.5 py-0.5 text-[11px] font-extrabold text-[#875000]">
                                        ⚠️ Sisa {{ $item->stock }} Unit
                                    </span>
                                @elseif($item->stock !== null)
                                    <span class="inline-flex items-center rounded-md bg-[#f7f7fa] border border-[#e7e2f7] px-2.5 py-0.5 text-[11px] font-bold text-[#231a3d]">
                                        {{ $item->stock }} Unit
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-[#f3effe] border border-[#b499ff]/40 px-2.5 py-0.5 text-[11px] font-bold text-[#382186]">
                                        Unlimited
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                <form method="POST" action="{{ route('owner.additional-items.toggle', $item->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button
                                        type="submit"
                                        class="craft-btn inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-extrabold uppercase border {{ $item->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-[#f7f7fa] text-[#6e6584] border-[#e7e2f7] hover:bg-[#e7e2f7]' }}"
                                    >
                                        <span>{{ $item->is_active ? 'Active' : 'Inactive' }}</span>
                                    </button>
                                </form>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        type="button"
                                        @click="openEdit(@json($itemPayload))"
                                        class="craft-btn rounded-xl p-1.5 text-[#6e6584] hover:bg-[#f3effe] hover:text-[#382186] transition"
                                        title="Edit"
                                        id="btn-edit-addon-{{ $item->id }}"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <form method="POST" action="{{ route('owner.additional-items.destroy', $item->id) }}" id="form-delete-item-{{ $item->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="button"
                                            @click="$dispatch('open-confirm', { title: 'Hapus Add-on?', message: 'Yakin ingin menghapus {{ $item->name }}?', formId: 'form-delete-item-{{ $item->id }}' })"
                                            class="craft-btn rounded-xl p-1.5 text-[#6e6584] hover:bg-rose-50 hover:text-rose-600 transition"
                                            title="Hapus"
                                            id="btn-delete-addon-{{ $item->id }}"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f3effe] text-[#382186] border border-[#e7e2f7] shadow-2xs mb-3">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                </div>
                                <p class="text-xs font-bold text-[#231a3d]">
                                    {{ !empty($search) ? 'Tidak ada add-on yang sesuai dengan pencarian' : 'Belum ada add-on tambahan terdaftar' }}
                                </p>
                                <p class="text-[11px] text-[#6e6584] mt-1 max-w-sm mx-auto">
                                    {{ !empty($search) ? 'Coba periksa kembali ejaan kata kunci atau reset filter pencarian.' : 'Tambahkan perlengkapan, minuman, atau sewa alat ekstra untuk meningkatkan pendapatan reservasi Anda.' }}
                                </p>
                                <div class="mt-4 flex items-center justify-center gap-2">
                                    @if(!empty($search))
                                        <a href="{{ route('owner.additional-items') }}" class="craft-btn inline-flex items-center gap-1.5 rounded-xl border border-[#e7e2f7] bg-white px-3 py-1.5 text-xs font-bold text-[#6e6584] hover:text-[#231a3d] hover:bg-[#f7f7fa]">
                                            Reset Pencarian
                                        </a>
                                    @endif
                                    <button type="button" @click="addModalOpen = true" class="craft-btn inline-flex items-center gap-1.5 rounded-xl bg-[#382186] px-3.5 py-1.5 text-xs font-bold text-white hover:bg-[#2d1a6d] shadow-2xs">
                                        + Tambah Add-on
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add Item Modal --}}
    <div
        x-show="addModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#231a3d]/50 backdrop-blur-xs"
        style="display: none;"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl border border-[#e7e2f7]"
            @click.outside="addModalOpen = false"
            x-show="addModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="flex items-center justify-between border-b border-[#e7e2f7] pb-3">
                <h3 class="text-base font-extrabold text-[#231a3d]">Tambah Add-on / Item Tambahan</h3>
                <button @click="addModalOpen = false" class="rounded-xl p-1 text-[#6e6584] hover:text-[#231a3d] hover:bg-[#f7f7fa]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-xs text-[#6e6584] mt-2">Produk atau layanan opsional yang dapat dipilih customer saat booking.</p>
            <form method="POST" action="{{ route('owner.additional-items.store') }}" class="mt-4 space-y-4" id="form-add-addon">
                @csrf
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Nama Item <span class="text-rose-500">*</span></label>
                    <input
                        type="text"
                        name="name"
                        required
                        placeholder="Contoh: Sewa Raket Tambahan"
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                    <p class="mt-1 text-[11px] text-[#6e6584]">Gunakan nama produk atau layanan tambahan yang menarik saat checkout.</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-bold text-[#231a3d]">Harga (Rp) <span class="text-rose-500">*</span></label>
                        <input
                            type="number"
                            name="price"
                            required
                            min="0"
                            step="500"
                            placeholder="25000"
                            class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                        >
                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-[#231a3d]">Stok Unit</label>
                            <label class="inline-flex items-center gap-1 cursor-pointer">
                                <input type="checkbox" x-model="addUnlimited" class="rounded border-[#e7e2f7] text-[#382186] focus:ring-[#b499ff]">
                                <span class="text-[10px] font-bold text-[#382186]">Unlimited</span>
                            </label>
                        </div>
                        <input
                            type="number"
                            name="stock"
                            min="0"
                            :disabled="addUnlimited"
                            :placeholder="addUnlimited ? 'Tak Terbatas' : 'Contoh: 20'"
                            class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition disabled:bg-[#f7f7fa] disabled:text-[#6e6584] disabled:cursor-not-allowed"
                        >
                        <p class="mt-1 text-[10px] text-[#6e6584]" x-text="addUnlimited ? 'Item ini dapat dipesan tanpa batasan kuantitas.' : 'Kuantitas akan berkurang setiap kali dipesan customer.'"></p>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Status Awal</label>
                    <select
                        name="is_active"
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                        <option value="1" selected>Active (Tersedia untuk Dipesan)</option>
                        <option value="0">Inactive (Disembunyikan)</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Deskripsi</label>
                    <textarea
                        name="description"
                        rows="2"
                        placeholder="Keterangan singkat item..."
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    ></textarea>
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-[#231a3d]">Berlaku untuk Layanan</label>
                        <span class="text-[10px] text-[#6e6584]">Opsional &bull; Default semua</span>
                    </div>
                    <div class="mt-2 max-h-32 overflow-y-auto space-y-1.5 rounded-2xl border border-[#e7e2f7] bg-[#f7f7fa]/60 p-3">
                        @foreach($services as $svc)
                            <label class="flex items-center gap-2 text-xs text-[#231a3d] font-semibold cursor-pointer">
                                <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}" class="rounded border-[#e7e2f7] text-[#382186] focus:ring-[#b499ff]">
                                <span>{{ $svc->namalayanan }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#e7e2f7]">
                    <button type="button" @click="addModalOpen = false" class="craft-btn rounded-xl px-4 py-2 text-xs font-bold text-[#6e6584] hover:bg-[#f7f7fa] hover:text-[#231a3d] transition">Batal</button>
                    <button type="submit" class="craft-btn rounded-xl bg-[#382186] px-4 py-2 text-xs font-bold text-white hover:bg-[#2d1a6d] shadow-xs transition">Simpan Add-on</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Item Modal --}}
    <div
        x-show="editModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#231a3d]/50 backdrop-blur-xs"
        style="display: none;"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl border border-[#e7e2f7]"
            @click.outside="editModalOpen = false"
            x-show="editModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="flex items-center justify-between border-b border-[#e7e2f7] pb-3">
                <h3 class="text-base font-extrabold text-[#231a3d]">Edit Add-on</h3>
                <button @click="editModalOpen = false" class="rounded-xl p-1 text-[#6e6584] hover:text-[#231a3d] hover:bg-[#f7f7fa]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-xs text-[#6e6584] mt-2">Perbarui harga, stok, atau layanan yang berlaku.</p>
            <form method="POST" :action="`/owner/additional-items/${activeItem.id}`" class="mt-4 space-y-4" id="form-edit-addon">
                @csrf
                @method('PUT')
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Nama Item <span class="text-rose-500">*</span></label>
                    <input
                        type="text"
                        name="name"
                        x-model="activeItem.name"
                        required
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                    <p class="mt-1 text-[11px] text-[#6e6584]">Nama add-on diperbarui pada formulir pemesanan tamu.</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-bold text-[#231a3d]">Harga (Rp) <span class="text-rose-500">*</span></label>
                        <input
                            type="number"
                            name="price"
                            x-model="activeItem.price"
                            required
                            min="0"
                            step="500"
                            class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                        >
                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-[#231a3d]">Stok Unit</label>
                            <label class="inline-flex items-center gap-1 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    x-model="editUnlimited" 
                                    @change="if (editUnlimited) activeItem.stock = ''; else if (!activeItem.stock) activeItem.stock = 10;" 
                                    class="rounded border-[#e7e2f7] text-[#382186] focus:ring-[#b499ff]"
                                >
                                <span class="text-[10px] font-bold text-[#382186]">Unlimited</span>
                            </label>
                        </div>
                        <input
                            type="number"
                            name="stock"
                            x-model="activeItem.stock"
                            :disabled="editUnlimited"
                            min="0"
                            :placeholder="editUnlimited ? 'Tak Terbatas' : 'Contoh: 20'"
                            class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition disabled:bg-[#f7f7fa] disabled:text-[#6e6584] disabled:cursor-not-allowed"
                        >
                        <p class="mt-1 text-[10px] text-[#6e6584]" x-text="editUnlimited ? 'Item ini dapat dipesan tanpa batasan kuantitas.' : 'Kuantitas akan berkurang setiap kali dipesan customer.'"></p>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Deskripsi</label>
                    <textarea
                        name="description"
                        x-model="activeItem.description"
                        rows="2"
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    ></textarea>
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Status</label>
                    <select
                        name="is_active"
                        x-model="activeItem.is_active"
                        class="mt-1.5 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                        <option :value="1">Active</option>
                        <option :value="0">Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-[#231a3d]">Berlaku untuk Layanan</label>
                    <div class="mt-2 max-h-32 overflow-y-auto space-y-1.5 rounded-2xl border border-[#e7e2f7] bg-[#f7f7fa]/60 p-3">
                        @foreach($services as $svc)
                            <label class="flex items-center gap-2 text-xs text-[#231a3d] font-semibold cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="service_ids[]"
                                    value="{{ $svc->id }}"
                                    :checked="activeItem.service_ids && activeItem.service_ids.includes({{ $svc->id }})"
                                    @change="toggleItemService({{ $svc->id }})"
                                    class="rounded border-[#e7e2f7] text-[#382186] focus:ring-[#b499ff]"
                                >
                                <span>{{ $svc->namalayanan }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#e7e2f7]">
                    <button type="button" @click="editModalOpen = false" class="craft-btn rounded-xl px-4 py-2 text-xs font-bold text-[#6e6584] hover:bg-[#f7f7fa] hover:text-[#231a3d] transition">Batal</button>
                    <button type="submit" class="craft-btn rounded-xl bg-[#382186] px-4 py-2 text-xs font-bold text-white hover:bg-[#2d1a6d] shadow-xs transition">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
