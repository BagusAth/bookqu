@extends('layouts.owner-layout')

@section('title', 'Assets Management')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    activeFilter: 'all',
    search: '',
    previewModal: false,
    selectedAsset: null,
    notification: '',
    assets: [
        { id: 1, title: 'Primary Brand Logo', category: 'logo', size: '142 KB', dimensions: '512 x 512 px', url: 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=600&auto=format&fit=crop&q=80', updated: 'Kemarin' },
        { id: 2, title: 'Main Cover Banner', category: 'cover', size: '820 KB', dimensions: '1920 x 720 px', url: 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=600&auto=format&fit=crop&q=80', updated: '3 hari lalu' },
        { id: 3, title: 'Service Promo - Hair Spa', category: 'service', size: '320 KB', dimensions: '800 x 600 px', url: 'https://images.unsplash.com/photo-1562322140-8baeececf3df?w=600&auto=format&fit=crop&q=80', updated: '1 minggu lalu' },
        { id: 4, title: 'Treatment Room Gallery', category: 'gallery', size: '490 KB', dimensions: '1200 x 800 px', url: 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=600&auto=format&fit=crop&q=80', updated: '2 minggu lalu' }
    ],
    showToast(msg) {
        this.notification = msg;
        setTimeout(() => this.notification = '', 3500);
    },
    openPreview(a) {
        this.selectedAsset = a;
        this.previewModal = true;
    },
    deleteAsset(id) {
        this.assets = this.assets.filter(a => a.id !== id);
        this.showToast('File aset berhasil dihapus.');
    },
    filteredAssets() {
        return this.assets.filter(a => {
            const matchesFilter = this.activeFilter === 'all' || a.category === this.activeFilter;
            const matchesSearch = !this.search.trim() || a.title.toLowerCase().includes(this.search.toLowerCase());
            return matchesFilter && matchesSearch;
        });
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
            <h1 class="text-2xl font-bold tracking-tight text-bq-text">Assets &amp; Media Manager</h1>
            <p class="text-sm text-bq-text-muted mt-1">Pusat pengelolaan file media, logo bisnis, foto cover, dan gambar katalog layanan.</p>
        </div>
        <button type="button" @click="showToast('Upload dialog dipicu. Silakan pilih file gambar.')" class="inline-flex items-center gap-2 rounded-xl bg-bq-primary px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-bq-primary-hover transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            + Upload Asset Baru
        </button>
    </div>

    {{-- ── Drag & Drop Upload Zone ── --}}
    <div class="rounded-2xl border-2 border-dashed border-bq-border bg-bq-surface p-8 text-center hover:border-bq-primary transition group cursor-pointer"
         @click="showToast('Dropzone siap. Anda dapat mengunggah file JPG, PNG, atau WEBP.')">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 group-hover:scale-110 transition">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
        </div>
        <p class="mt-3 text-xs font-bold text-bq-text">Tarik &amp; letakkan file gambar di sini, atau klik untuk memilih file</p>
        <p class="mt-1 text-[11px] text-bq-text-muted">Mendukung format JPG, PNG, WEBP hingga 5MB. Resolusi optimal 1920x1080 untuk Cover.</p>
    </div>

    {{-- ── Filter Tabs & Search ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-bq-border pb-3">
        <div class="flex items-center gap-2 overflow-x-auto">
            <template x-for="f in [
                { id: 'all', label: 'Semua Asset' },
                { id: 'logo', label: 'Logo & Identitas' },
                { id: 'cover', label: 'Cover Banner' },
                { id: 'service', label: 'Layanan' },
                { id: 'gallery', label: 'Galeri Foto' }
            ]" :key="f.id">
                <button type="button" @click="activeFilter = f.id"
                    class="rounded-xl px-3.5 py-1.5 text-xs font-semibold whitespace-nowrap transition"
                    :class="activeFilter === f.id ? 'bg-bq-primary text-white shadow-xs' : 'border border-bq-border bg-bq-surface text-bq-text-muted hover:text-bq-text'">
                    <span x-text="f.label"></span>
                </button>
            </template>
        </div>

        <div class="relative w-full sm:max-w-xs">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" x-model="search" placeholder="Cari nama asset..." class="w-full rounded-xl border border-bq-border bg-bq-surface py-2 pl-9 pr-3 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
        </div>
    </div>

    {{-- ── Assets Grid ── --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <template x-for="asset in filteredAssets()" :key="asset.id">
            <div class="group rounded-2xl border border-bq-border bg-bq-surface overflow-hidden shadow-xs hover:border-bq-border-strong hover:shadow-md transition flex flex-col justify-between">
                <div>
                    {{-- Image Thumbnail --}}
                    <div class="relative h-40 bg-slate-100 overflow-hidden cursor-pointer" @click="openPreview(asset)">
                        <img :src="asset.url" :alt="asset.title" class="h-full w-full object-cover group-hover:scale-105 transition duration-300">
                        <span class="absolute top-2 left-2 rounded-lg bg-slate-900/70 backdrop-blur-xs px-2 py-0.5 text-[10px] font-bold text-white uppercase tracking-wider" x-text="asset.category"></span>
                    </div>

                    {{-- Meta details --}}
                    <div class="p-4 space-y-1">
                        <h4 class="font-bold text-xs text-bq-text truncate" x-text="asset.title"></h4>
                        <div class="flex items-center justify-between text-[11px] text-bq-text-muted font-mono">
                            <span x-text="asset.dimensions"></span>
                            <span x-text="asset.size"></span>
                        </div>
                    </div>
                </div>

                {{-- Card Actions --}}
                <div class="p-4 pt-0 border-t border-bq-border flex items-center justify-between text-xs mt-2">
                    <span class="text-[10px] text-bq-text-subtle" x-text="'Diperbarui ' + asset.updated"></span>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="openPreview(asset)" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-slate-100 hover:text-bq-text transition" title="Lihat">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                        <button type="button" @click="deleteAsset(asset.id)" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-rose-50 hover:text-rose-600 transition" title="Hapus">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Empty State --}}
    <div x-show="filteredAssets().length === 0" class="rounded-2xl border border-dashed border-bq-border bg-bq-surface p-12 text-center" style="display: none;">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
        </div>
        <h3 class="text-sm font-bold text-bq-text">Tidak ada aset media ditemukan</h3>
        <p class="mt-1 text-xs text-bq-text-muted max-w-sm mx-auto">Upload gambar logo, banner promosi, atau ruangan bisnis Anda agar halaman booking semakin menarik.</p>
    </div>

    {{-- Asset Preview Modal --}}
    <div x-show="previewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" style="display: none;">
        <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="previewModal = false">
            <template x-if="selectedAsset">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-sm text-bq-text" x-text="selectedAsset.title"></h3>
                            <p class="text-xs text-bq-text-muted font-mono mt-0.5" x-text="selectedAsset.dimensions + ' • ' + selectedAsset.size"></p>
                        </div>
                        <button type="button" @click="previewModal = false" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-slate-100 transition">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="rounded-xl overflow-hidden bg-slate-100 max-h-80 flex items-center justify-center">
                        <img :src="selectedAsset.url" :alt="selectedAsset.title" class="max-h-80 w-auto object-contain">
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-bq-border">
                        <button type="button" @click="deleteAsset(selectedAsset.id); previewModal = false;" class="rounded-xl border border-rose-200 bg-rose-50 text-rose-700 px-3.5 py-2 text-xs font-semibold hover:bg-rose-100 transition">
                            Hapus File
                        </button>
                        <button type="button" @click="showToast('URL gambar disalin!'); previewModal = false;" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">
                            Salin URL Asset
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

</div>
@endsection
