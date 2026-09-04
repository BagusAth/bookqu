@extends('layouts.owner-layout')

@section('title', 'Service Categories')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    search: '{{ addslashes($search ?? '') }}',
    addModalOpen: false,
    editModalOpen: false,
    activeCategory: { id: null, name: '', description: '', color: 'indigo', is_active: 1 },
    openEdit(cat) {
        this.activeCategory = { ...cat };
        this.editModalOpen = true;
    }
}">

    {{-- ── Header ── --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-bq-text">Service Categories</h1>
            <p class="text-sm text-bq-text-muted mt-1">Kelompokkan layanan bisnis agar katalog reservasi tertata rapi dan mudah ditemukan customer.</p>
        </div>
        <button type="button" @click="addModalOpen = true" class="inline-flex items-center gap-2 rounded-xl bg-bq-primary px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-bq-primary-hover transition" id="btn-add-category">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Kategori
        </button>
    </div>

    {{-- ── Search & Filter ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('owner.categories') }}" class="relative w-full sm:max-w-xs">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari kategori..."
                class="w-full rounded-xl border border-bq-border bg-bq-surface py-2.5 pl-10 pr-4 text-xs text-bq-text placeholder-bq-text-subtle transition focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                id="input-search-categories">
        </form>
        <div class="text-xs text-bq-text-muted">
            Total <span class="font-bold text-bq-text">{{ $categories->count() }}</span> kategori
        </div>
    </div>

    {{-- ── Categories List / Grid ── --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" id="categories-grid">
        @forelse ($categories as $cat)
            @php
                $catPayload = [
                    'id'          => $cat->id,
                    'name'        => $cat->name,
                    'description' => $cat->description ?? '',
                    'color'       => $cat->color ?? 'indigo',
                    'is_active'   => (int) $cat->is_active,
                ];
            @endphp
            <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs transition hover:border-bq-border-strong hover:shadow-md flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 font-bold text-sm">
                                <span>{{ strtoupper(substr($cat->name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-bq-text">{{ $cat->name }}</h3>
                                <span class="text-[11px] font-medium text-bq-text-muted">{{ $cat->services_count }} Layanan terhubung</span>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('owner.categories.toggle', $cat->id) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold transition {{ $cat->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}"
                                title="Klik untuk ubah status">
                                <span>{{ $cat->is_active ? 'Active' : 'Inactive' }}</span>
                            </button>
                        </form>
                    </div>
                    <p class="mt-3 text-xs text-bq-text-muted line-clamp-2">{{ $cat->description ?: 'Tidak ada deskripsi.' }}</p>
                </div>

                <div class="mt-5 pt-3 border-t border-bq-border flex items-center justify-between text-xs">
                    <a href="{{ route('owner.services') }}" class="text-indigo-600 font-medium hover:underline text-[11px]">
                        Lihat Layanan &rarr;
                    </a>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="openEdit(@json($catPayload))" class="rounded-lg p-1.5 text-bq-text-muted hover:bg-slate-100 hover:text-bq-text transition" title="Edit" id="btn-edit-category-{{ $cat->id }}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <form method="POST" action="{{ route('owner.categories.destroy', $cat->id) }}" id="form-delete-cat-{{ $cat->id }}">
                            @csrf
                            @method('DELETE')
                            <button type="button"
                                @click="$dispatch('open-confirm', { title: 'Hapus Kategori?', message: 'Kategori yang dihapus akan melepaskan relasi dari layanan yang terhubung. Yakin ingin menghapus?', formId: 'form-delete-cat-{{ $cat->id }}' })"
                                class="rounded-lg p-1.5 text-bq-text-muted hover:bg-rose-50 hover:text-rose-600 transition" title="Hapus" id="btn-delete-category-{{ $cat->id }}">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-bq-border bg-bq-surface p-12 text-center">
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
        @endforelse
    </div>

    {{-- Add Category Modal --}}
    <div x-show="addModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;" x-cloak>
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="addModalOpen = false">
            <h3 class="text-base font-bold text-bq-text">Tambah Kategori Layanan</h3>
            <p class="text-xs text-bq-text-muted mt-1">Buat kategori baru untuk mengelompokkan layanan reservasi.</p>
            <form method="POST" action="{{ route('owner.categories.store') }}" class="mt-4 space-y-4" id="form-add-category">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-bq-text">Nama Kategori <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: Photoshoot Outdoor"
                        class="mt-1 w-full rounded-xl border border-bq-border bg-bq-surface px-3 py-2 text-xs text-bq-text placeholder-bq-text-subtle focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div>
                    <label class="block text-xs font-bold text-bq-text">Deskripsi Singkat</label>
                    <textarea name="description" rows="3" placeholder="Jelaskan jenis layanan dalam kategori ini..."
                        class="mt-1 w-full rounded-xl border border-bq-border bg-bq-surface px-3 py-2 text-xs text-bq-text placeholder-bq-text-subtle focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"></textarea>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_active" value="1" id="cat_add_active" checked class="h-4 w-4 rounded border-bq-border text-bq-primary focus:ring-bq-primary/20">
                    <label for="cat_add_active" class="text-xs font-medium text-bq-text">Aktifkan kategori ini</label>
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-bq-border">
                    <button type="button" @click="addModalOpen = false" class="rounded-xl px-4 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Category Modal --}}
    <div x-show="editModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;" x-cloak>
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="editModalOpen = false">
            <h3 class="text-base font-bold text-bq-text">Edit Kategori Layanan</h3>
            <p class="text-xs text-bq-text-muted mt-1">Perbarui informasi kategori layanan reservasi.</p>
            <form method="POST" :action="`/owner/categories/${activeCategory.id}`" class="mt-4 space-y-4" id="form-edit-category">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-bq-text">Nama Kategori <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="activeCategory.name" required
                        class="mt-1 w-full rounded-xl border border-bq-border bg-bq-surface px-3 py-2 text-xs text-bq-text placeholder-bq-text-subtle focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                </div>
                <div>
                    <label class="block text-xs font-bold text-bq-text">Deskripsi Singkat</label>
                    <textarea name="description" x-model="activeCategory.description" rows="3"
                        class="mt-1 w-full rounded-xl border border-bq-border bg-bq-surface px-3 py-2 text-xs text-bq-text placeholder-bq-text-subtle focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-bq-text">Status</label>
                    <select name="is_active" x-model="activeCategory.is_active" class="mt-1 w-full rounded-xl border border-bq-border bg-bq-surface px-3 py-2 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
                        <option :value="1">Active</option>
                        <option :value="0">Inactive</option>
                    </select>
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-bq-border">
                    <button type="button" @click="editModalOpen = false" class="rounded-xl px-4 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
