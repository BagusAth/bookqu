@extends('layouts.owner-layout')

@section('title', 'Additional Items')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    search: '',
    addModalOpen: false,
    editModalOpen: false,
    activeItem: null,
    notification: '',
    items: [
        { id: 1, name: 'Sewa Raket Carbon Pro', price: 25000, formatted_price: 'Rp 25.000', applicable: 'Sewa Lapangan Badminton', stock: '20 Unit', is_active: true },
        { id: 2, name: '1 Slop Shuttlecock Tournament (12 pcs)', price: 110000, formatted_price: 'Rp 110.000', applicable: 'Sewa Lapangan Badminton', stock: '35 Slop', is_active: true },
        { id: 3, name: 'Extra Organic Hair Serum', price: 35000, formatted_price: 'Rp 35.000', applicable: 'Hair Treatment, Hair Spa', stock: 'Unlimited', is_active: true },
        { id: 4, name: 'Minuman Isotonik & Handuk Bersih', price: 15000, formatted_price: 'Rp 15.000', applicable: 'Semua Layanan', stock: '48 Pcs', is_active: false }
    ],
    showToast(msg) {
        this.notification = msg;
        setTimeout(() => this.notification = '', 3500);
    },
    toggleStatus(it) {
        it.is_active = !it.is_active;
        this.showToast('Status item ' + it.name + ' ' + (it.is_active ? 'diaktifkan' : 'dinonaktifkan'));
    },
    openEdit(it) {
        this.activeItem = { ...it };
        this.editModalOpen = true;
    },
    saveEdit() {
        const idx = this.items.findIndex(i => i.id === this.activeItem.id);
        if (idx !== -1) {
            this.activeItem.formatted_price = 'Rp ' + Number(this.activeItem.price).toLocaleString('id-ID');
            this.items[idx] = { ...this.activeItem };
        }
        this.editModalOpen = false;
        this.showToast('Perubahan item berhasil disimpan.');
    },
    deleteItem(id) {
        this.items = this.items.filter(i => i.id !== id);
        this.showToast('Item berhasil dihapus.');
    },
    filteredItems() {
        if (!this.search.trim()) return this.items;
        const q = this.search.toLowerCase();
        return this.items.filter(i => i.name.toLowerCase().includes(q) || i.applicable.toLowerCase().includes(q));
    }
}">

    {{-- Toast Notification --}}
    <div x-show="notification"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-5 right-5 z-50 rounded-xl bg-slate-900 text-white px-4 py-3 shadow-xl text-xs font-medium flex items-center gap-2"
         style="display: none;">
        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
        <span x-text="notification"></span>
    </div>

    {{-- ── Header ── --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-bq-text">Additional Items (Add-ons)</h1>
            <p class="text-sm text-bq-text-muted mt-1">Kelola produk atau perlengkapan tambahan yang dapat dibeli customer saat checkout reservasi.</p>
        </div>
        <button type="button" @click="addModalOpen = true" class="inline-flex items-center gap-2 rounded-xl bg-bq-primary px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-bq-primary-hover transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            + Tambah Add-on
        </button>
    </div>

    {{-- ── Search & Metrics ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full sm:max-w-xs">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" x-model="search" placeholder="Cari item atau layanan..." class="w-full rounded-xl border border-bq-border bg-bq-surface py-2.5 pl-10 pr-4 text-xs text-bq-text placeholder-bq-text-subtle transition focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
        </div>
        <div class="text-xs text-bq-text-muted">
            Total <span class="font-bold text-bq-text" x-text="filteredItems().length"></span> add-on terdaftar
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
                        <th class="px-5 py-3.5">Stock / Availability</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bq-border">
                    <template x-for="item in filteredItems()" :key="item.id">
                        <tr class="hover:bg-bq-background/40 transition">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 font-bold text-xs">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                    </div>
                                    <span class="font-semibold text-bq-text text-sm" x-text="item.name"></span>
                                </div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap font-bold text-bq-primary" x-text="item.formatted_price"></td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700" x-text="item.applicable"></span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-bq-text-muted font-mono" x-text="item.stock"></td>
                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                <button type="button" @click="toggleStatus(item)"
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold transition"
                                    :class="item.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-slate-100 text-slate-500'">
                                    <span x-text="item.is_active ? 'Active' : 'Inactive'"></span>
                                </button>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @click="openEdit(item)" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-slate-100 hover:text-bq-text transition" title="Edit">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button type="button" @click="deleteItem(item.id)" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-rose-50 hover:text-rose-600 transition" title="Hapus">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Empty State --}}
    <div x-show="filteredItems().length === 0" class="rounded-2xl border border-dashed border-bq-border bg-bq-surface p-12 text-center" style="display: none;">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
        </div>
        <h3 class="text-sm font-bold text-bq-text">Belum ada additional item</h3>
        <p class="mt-1 text-xs text-bq-text-muted max-w-sm mx-auto">Tawarkan perlengkapan sewa, suplemen, atau produk pendukung untuk menambah nilai pesanan customer.</p>
        <button type="button" @click="addModalOpen = true" class="mt-4 rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">
            + Tambah Add-on Baru
        </button>
    </div>

    {{-- Add Item Modal --}}
    <div x-show="addModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="addModalOpen = false">
            <h3 class="text-base font-bold text-bq-text">Tambah Add-on / Item Baru</h3>
            <p class="text-xs text-bq-text-muted mt-1">Daftarkan item tambahan yang dapat dipilih saat reservasi.</p>
            <form @submit.prevent="
                const p = Number($refs.itPrice.value) || 0;
                items.push({
                    id: Date.now(),
                    name: $refs.itName.value,
                    price: p,
                    formatted_price: 'Rp ' + p.toLocaleString('id-ID'),
                    applicable: $refs.itApp.value || 'Semua Layanan',
                    stock: $refs.itStock.value || 'Unlimited',
                    is_active: true
                });
                addModalOpen = false;
                showToast('Item berhasil ditambahkan!')" class="mt-4 space-y-4">
                <div>
                    <label class="text-xs font-semibold text-bq-text">Nama Item / Produk</label>
                    <input type="text" x-ref="itName" required placeholder="Contoh: Sewa Sepatu, Handuk Gym" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Harga (Rp)</label>
                    <input type="number" x-ref="itPrice" required placeholder="25000" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Layanan Terkait</label>
                    <input type="text" x-ref="itApp" placeholder="Contoh: Semua Layanan, Lapangan Badminton" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Stok / Ketersediaan</label>
                    <input type="text" x-ref="itStock" placeholder="Contoh: 20 Pcs, atau Unlimited" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-bq-border">
                    <button type="button" @click="addModalOpen = false" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Simpan Item</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Item Modal --}}
    <div x-show="editModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="editModalOpen = false">
            <h3 class="text-base font-bold text-bq-text">Edit Add-on</h3>
            <template x-if="activeItem">
                <form @submit.prevent="saveEdit()" class="mt-4 space-y-4">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Nama Item</label>
                        <input type="text" x-model="activeItem.name" required class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Harga (Rp)</label>
                        <input type="number" x-model="activeItem.price" required class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Layanan Terkait</label>
                        <input type="text" x-model="activeItem.applicable" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Stok</label>
                        <input type="text" x-model="activeItem.stock" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-bq-border">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium text-bq-text">
                            <input type="checkbox" x-model="activeItem.is_active" class="rounded text-bq-primary focus:ring-bq-primary">
                            Status Aktif
                        </label>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="editModalOpen = false" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                            <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Simpan</button>
                        </div>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
