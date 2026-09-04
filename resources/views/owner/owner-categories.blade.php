@extends('layouts.owner-layout')

@section('title', 'Service Categories')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    search: '',
    addModalOpen: false,
    editModalOpen: false,
    activeCategory: null,
    notification: '',
    categories: [
        { id: 1, name: 'General Treatment', description: 'Standard reservation & main consulting sessions.', services_count: 3, is_active: true, color: 'indigo' },
        { id: 2, name: 'VIP & Executive', description: 'Exclusive premium packages with dedicated resources.', services_count: 2, is_active: true, color: 'purple' },
        { id: 3, name: 'Express / Quick', description: 'Short duration slots for quick appointments.', services_count: 1, is_active: false, color: 'emerald' }
    ],
    showToast(msg) {
        this.notification = msg;
        setTimeout(() => this.notification = '', 3500);
    },
    toggleStatus(cat) {
        cat.is_active = !cat.is_active;
        this.showToast('Kategori ' + cat.name + ' ' + (cat.is_active ? 'diaktifkan' : 'dinonaktifkan'));
    },
    openEdit(cat) {
        this.activeCategory = { ...cat };
        this.editModalOpen = true;
    },
    saveEdit() {
        const idx = this.categories.findIndex(c => c.id === this.activeCategory.id);
        if (idx !== -1) {
            this.categories[idx] = { ...this.activeCategory };
        }
        this.editModalOpen = false;
        this.showToast('Perubahan kategori berhasil disimpan.');
    },
    deleteCategory(id) {
        this.categories = this.categories.filter(c => c.id !== id);
        this.showToast('Kategori berhasil dihapus.');
    },
    filteredCategories() {
        if (!this.search.trim()) return this.categories;
        const q = this.search.toLowerCase();
        return this.categories.filter(c => c.name.toLowerCase().includes(q) || c.description.toLowerCase().includes(q));
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
            <h1 class="text-2xl font-bold tracking-tight text-bq-text">Service Categories</h1>
            <p class="text-sm text-bq-text-muted mt-1">Kelompokkan layanan bisnis agar katalog reservasi tertata rapi dan mudah ditemukan customer.</p>
        </div>
        <button type="button" @click="addModalOpen = true" class="inline-flex items-center gap-2 rounded-xl bg-bq-primary px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-bq-primary-hover transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Kategori
        </button>
    </div>

    {{-- ── Search Bar ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full sm:max-w-xs">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" x-model="search" placeholder="Cari kategori..."
                class="w-full rounded-xl border border-bq-border bg-bq-surface py-2.5 pl-10 pr-4 text-xs text-bq-text placeholder-bq-text-subtle transition focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
        </div>
        <div class="text-xs text-bq-text-muted">
            Total <span class="font-bold text-bq-text" x-text="filteredCategories().length"></span> kategori
        </div>
    </div>

    {{-- ── Categories List / Grid ── --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <template x-for="cat in filteredCategories()" :key="cat.id">
            <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs transition hover:border-bq-border-strong hover:shadow-md flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 font-bold text-sm">
                                <span x-text="cat.name.charAt(0)"></span>
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-bq-text" x-text="cat.name"></h3>
                                <span class="text-[11px] font-medium text-bq-text-muted" x-text="cat.services_count + ' Layanan terhubung'"></span>
                            </div>
                        </div>
                        <button type="button" @click="toggleStatus(cat)"
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold transition"
                            :class="cat.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-slate-100 text-slate-500'">
                            <span x-text="cat.is_active ? 'Active' : 'Inactive'"></span>
                        </button>
                    </div>
                    <p class="mt-3 text-xs text-bq-text-muted line-clamp-2" x-text="cat.description"></p>
                </div>

                <div class="mt-5 pt-3 border-t border-bq-border flex items-center justify-between text-xs">
                    <a href="{{ route('owner.programs') }}" class="text-indigo-600 font-medium hover:underline text-[11px]">
                        Lihat Layanan &rarr;
                    </a>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="openEdit(cat)" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-slate-100 hover:text-bq-text transition" title="Edit">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button type="button" @click="deleteCategory(cat.id)" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-rose-50 hover:text-rose-600 transition" title="Hapus">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Empty State --}}
    <div x-show="filteredCategories().length === 0" class="rounded-2xl border border-dashed border-bq-border bg-bq-surface p-12 text-center" style="display: none;">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-bq-text">Belum ada kategori</h3>
        <p class="mt-1 text-xs text-bq-text-muted max-w-sm mx-auto">Kelompokkan layanan bisnis Anda untuk memudahkan navigasi pengunjung pada halaman booking.</p>
        <button type="button" @click="addModalOpen = true" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">
            + Tambah Kategori Baru
        </button>
    </div>

    {{-- Add Category Modal --}}
    <div x-show="addModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="addModalOpen = false">
            <h3 class="text-base font-bold text-bq-text">Tambah Kategori Layanan</h3>
            <p class="text-xs text-bq-text-muted mt-1">Buat kategori baru untuk mengelompokkan layanan reservasi.</p>
            <form @submit.prevent="categories.push({ id: Date.now(), name: $refs.newName.value, description: $refs.newDesc.value, services_count: 0, is_active: true }); addModalOpen = false; showToast('Kategori baru berhasil ditambahkan!')" class="mt-4 space-y-4">
                <div>
                    <label class="text-xs font-semibold text-bq-text">Nama Kategori</label>
                    <input type="text" x-ref="newName" required placeholder="Contoh: Perawatan Rambut, VIP Consult" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div>
                    <label class="text-xs font-semibold text-bq-text">Deskripsi</label>
                    <textarea x-ref="newDesc" rows="3" placeholder="Keterangan singkat kategori..." class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-bq-border">
                    <button type="button" @click="addModalOpen = false" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Category Modal --}}
    <div x-show="editModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="editModalOpen = false">
            <h3 class="text-base font-bold text-bq-text">Edit Kategori Layanan</h3>
            <template x-if="activeCategory">
                <form @submit.prevent="saveEdit()" class="mt-4 space-y-4">
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Nama Kategori</label>
                        <input type="text" x-model="activeCategory.name" required class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-bq-text">Deskripsi</label>
                        <textarea x-model="activeCategory.description" rows="3" class="mt-1.5 w-full rounded-xl border border-bq-border px-3.5 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"></textarea>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-bq-border">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium text-bq-text">
                            <input type="checkbox" x-model="activeCategory.is_active" class="rounded text-bq-primary focus:ring-bq-primary">
                            Status Aktif
                        </label>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="editModalOpen = false" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                            <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Simpan Perubahan</button>
                        </div>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
