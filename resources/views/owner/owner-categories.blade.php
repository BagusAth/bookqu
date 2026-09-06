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
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#f3effe] text-[#382186] border border-[#e7e2f7] shadow-2xs">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </span>
                <h1 class="text-2xl font-extrabold tracking-tight text-[#231a3d] sm:text-3xl">Service Categories</h1>
            </div>
            <p class="text-sm text-[#6e6584] mt-1">Kelompokkan layanan bisnis agar katalog reservasi tertata rapi dan mudah ditemukan customer.</p>
        </div>
        <button
            type="button"
            @click="addModalOpen = true"
            class="craft-btn inline-flex items-center gap-2 rounded-xl bg-[#382186] px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-xs hover:bg-[#2d1a6d]"
            id="btn-add-category"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Kategori
        </button>
    </div>

    {{-- ── Search & Filter Bar ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between rounded-2xl border border-[#e7e2f7] bg-white p-3.5 shadow-[0_4px_20px_rgba(35,26,61,0.03)]">
        <div class="flex flex-wrap items-center gap-2.5">
            <form method="GET" action="{{ route('owner.categories') }}" class="relative w-full sm:w-64">
                <input type="hidden" name="status" value="{{ $status ?? 'all' }}">
                <input type="hidden" name="sort" value="{{ $sort ?? 'newest' }}">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#6e6584]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    name="search"
                    value="{{ $search ?? '' }}"
                    placeholder="Cari kategori..."
                    class="w-full rounded-xl border border-[#e7e2f7] bg-[#f7f7fa] py-2 pl-9 pr-3 text-xs text-[#231a3d] placeholder-[#6e6584] transition focus:border-[#382186] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs"
                    id="input-search-categories"
                >
            </form>

            {{-- Status Filter Pills --}}
            <div class="flex items-center gap-1 rounded-xl bg-[#f7f7fa] p-1 border border-[#e7e2f7] text-xs">
                <a
                    href="{{ route('owner.categories', ['status' => 'all', 'search' => $search ?? '', 'sort' => $sort ?? 'newest']) }}"
                    class="rounded-lg px-3 py-1 font-bold transition {{ ($status ?? 'all') === 'all' ? 'bg-[#382186] text-white shadow-2xs' : 'text-[#6e6584] hover:text-[#231a3d]' }}"
                >
                    Semua
                </a>
                <a
                    href="{{ route('owner.categories', ['status' => 'active', 'search' => $search ?? '', 'sort' => $sort ?? 'newest']) }}"
                    class="rounded-lg px-3 py-1 font-bold transition {{ ($status ?? 'all') === 'active' ? 'bg-[#382186] text-white shadow-2xs' : 'text-[#6e6584] hover:text-[#231a3d]' }}"
                >
                    Aktif
                </a>
                <a
                    href="{{ route('owner.categories', ['status' => 'inactive', 'search' => $search ?? '', 'sort' => $sort ?? 'newest']) }}"
                    class="rounded-lg px-3 py-1 font-bold transition {{ ($status ?? 'all') === 'inactive' ? 'bg-[#382186] text-white shadow-2xs' : 'text-[#6e6584] hover:text-[#231a3d]' }}"
                >
                    Nonaktif
                </a>
            </div>
        </div>

        {{-- Sort & Count --}}
        <div class="flex items-center gap-3">
            <select
                onchange="location.href = this.value"
                class="rounded-xl border border-[#e7e2f7] bg-[#f7f7fa] hover:bg-white px-3 py-1.5 text-xs font-semibold text-[#231a3d] focus:outline-none focus:border-[#382186] focus:ring-2 focus:ring-[#b499ff]/30 transition shadow-2xs"
            >
                <option value="{{ route('owner.categories', ['sort' => 'newest', 'status' => $status ?? 'all', 'search' => $search ?? '']) }}" {{ ($sort ?? 'newest') === 'newest' ? 'selected' : '' }}>Terbaru</option>
                <option value="{{ route('owner.categories', ['sort' => 'name_asc', 'status' => $status ?? 'all', 'search' => $search ?? '']) }}" {{ ($sort ?? '') === 'name_asc' ? 'selected' : '' }}>Nama (A - Z)</option>
                <option value="{{ route('owner.categories', ['sort' => 'name_desc', 'status' => $status ?? 'all', 'search' => $search ?? '']) }}" {{ ($sort ?? '') === 'name_desc' ? 'selected' : '' }}>Nama (Z - A)</option>
                <option value="{{ route('owner.categories', ['sort' => 'services', 'status' => $status ?? 'all', 'search' => $search ?? '']) }}" {{ ($sort ?? '') === 'services' ? 'selected' : '' }}>Layanan Terbanyak</option>
            </select>
            <div class="text-xs text-[#6e6584] whitespace-nowrap font-medium">
                Total <span class="font-extrabold text-[#231a3d]">{{ $categories->count() }}</span>
            </div>
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
            <div class="craft-card rounded-2xl border border-[#e7e2f7] bg-white p-5 shadow-[0_4px_20px_rgba(35,26,61,0.03)] hover:border-[#b499ff] flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#f3effe] text-[#382186] font-black text-sm border border-[#e7e2f7]">
                                <span>{{ strtoupper(substr($cat->name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-sm text-[#231a3d]">{{ $cat->name }}</h3>
                                <span class="text-[11px] font-semibold text-[#6e6584]">{{ $cat->services_count }} Layanan terhubung</span>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('owner.categories.toggle', $cat->id) }}">
                            @csrf
                            @method('PATCH')
                            <button
                                type="submit"
                                class="craft-btn inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-extrabold uppercase border {{ $cat->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-[#f7f7fa] text-[#6e6584] border-[#e7e2f7] hover:bg-[#e7e2f7]' }}"
                                title="Klik untuk ubah status"
                            >
                                <span>{{ $cat->is_active ? 'Active' : 'Inactive' }}</span>
                            </button>
                        </form>
                    </div>
                    <p class="mt-3 text-xs text-[#6e6584] line-clamp-2 leading-relaxed">{{ $cat->description ?: 'Tidak ada deskripsi.' }}</p>
                </div>

                <div class="mt-5 pt-3.5 border-t border-[#e7e2f7] flex items-center justify-between text-xs">
                    <a href="{{ route('owner.services', ['category_id' => $cat->id]) }}" class="text-[#382186] font-bold hover:underline text-[11px] inline-flex items-center gap-1">
                        Lihat Layanan &rarr;
                    </a>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="openEdit(@json($catPayload))"
                            class="craft-btn rounded-xl p-1.5 text-[#6e6584] hover:bg-[#f3effe] hover:text-[#382186] transition"
                            title="Edit"
                            id="btn-edit-category-{{ $cat->id }}"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <form method="POST" action="{{ route('owner.categories.destroy', $cat->id) }}" id="form-delete-cat-{{ $cat->id }}">
                            @csrf
                            @method('DELETE')
                            <button
                                type="button"
                                @click="$dispatch('open-confirm', { title: 'Hapus Kategori?', message: 'Kategori yang dihapus akan melepaskan relasi dari layanan yang terhubung. Yakin ingin menghapus?', formId: 'form-delete-cat-{{ $cat->id }}' })"
                                class="craft-btn rounded-xl p-1.5 text-[#6e6584] hover:bg-rose-50 hover:text-rose-600 transition"
                                title="Hapus"
                                id="btn-delete-category-{{ $cat->id }}"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-[#e7e2f7] bg-[#f7f7fa]/50 p-12 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f3effe] text-[#382186] border border-[#e7e2f7]">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </div>
                <h3 class="text-sm font-extrabold text-[#231a3d]">Belum ada kategori</h3>
                <p class="mt-1 text-xs text-[#6e6584] max-w-sm mx-auto">Kelompokkan layanan bisnis Anda untuk memudahkan navigasi pengunjung pada halaman booking.</p>
                <button
                    type="button"
                    @click="addModalOpen = true"
                    class="craft-btn mt-4 inline-flex items-center gap-2 rounded-xl bg-[#382186] px-4 py-2 text-xs font-bold text-white hover:bg-[#2d1a6d] shadow-xs"
                >
                    + Tambah Kategori Baru
                </button>
            </div>
        @endforelse
    </div>

    {{-- Add Category Modal --}}
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
                <h3 class="text-base font-extrabold text-[#231a3d]">Tambah Kategori Layanan</h3>
                <button @click="addModalOpen = false" class="rounded-xl p-1 text-[#6e6584] hover:text-[#231a3d] hover:bg-[#f7f7fa]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-xs text-[#6e6584] mt-2">Buat kategori baru untuk mengelompokkan layanan reservasi.</p>
            <form method="POST" action="{{ route('owner.categories.store') }}" class="mt-4 space-y-4" id="form-add-category">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-[#231a3d]">Nama Kategori <span class="text-rose-500">*</span></label>
                    <input
                        type="text"
                        name="name"
                        required
                        placeholder="Contoh: Photoshoot Outdoor"
                        class="mt-1 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                    <p class="mt-1 text-[11px] text-[#6e6584]">Nama kategori yang jelas memudahkan pelanggan memilih layanan yang tepat.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#231a3d]">Deskripsi Singkat</label>
                    <textarea
                        name="description"
                        rows="3"
                        placeholder="Jelaskan jenis layanan dalam kategori ini..."
                        class="mt-1 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    ></textarea>
                    <p class="mt-1 text-[11px] text-[#6e6584]">Deskripsi akan membantu pengunjung memahami cakupan layanan di kategori ini.</p>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_active" value="1" id="cat_add_active" checked class="h-4 w-4 rounded border-[#e7e2f7] text-[#382186] focus:ring-[#b499ff]">
                    <div>
                        <label for="cat_add_active" class="text-xs font-semibold text-[#231a3d]">Aktifkan kategori ini</label>
                        <p class="text-[10px] text-[#6e6584]">Kategori aktif dapat langsung digunakan untuk mengelompokkan layanan.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#e7e2f7]">
                    <button type="button" @click="addModalOpen = false" class="craft-btn rounded-xl px-4 py-2 text-xs font-bold text-[#6e6584] hover:bg-[#f7f7fa] hover:text-[#231a3d] transition">Batal</button>
                    <button type="submit" class="craft-btn rounded-xl bg-[#382186] px-4 py-2 text-xs font-bold text-white hover:bg-[#2d1a6d] shadow-xs transition">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Category Modal --}}
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
                <h3 class="text-base font-extrabold text-[#231a3d]">Edit Kategori Layanan</h3>
                <button @click="editModalOpen = false" class="rounded-xl p-1 text-[#6e6584] hover:text-[#231a3d] hover:bg-[#f7f7fa]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-xs text-[#6e6584] mt-2">Perbarui informasi kategori layanan reservasi.</p>
            <form method="POST" :action="`/owner/categories/${activeCategory.id}`" class="mt-4 space-y-4" id="form-edit-category">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-[#231a3d]">Nama Kategori <span class="text-rose-500">*</span></label>
                    <input
                        type="text"
                        name="name"
                        x-model="activeCategory.name"
                        required
                        class="mt-1 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                    <p class="mt-1 text-[11px] text-[#6e6584]">Nama kategori diperbarui pada katalog booking pelanggan.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#231a3d]">Deskripsi Singkat</label>
                    <textarea
                        name="description"
                        x-model="activeCategory.description"
                        rows="3"
                        class="mt-1 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    ></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#231a3d]">Status</label>
                    <select
                        name="is_active"
                        x-model="activeCategory.is_active"
                        class="mt-1 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                        <option :value="1">Active (Ditampilkan)</option>
                        <option :value="0">Inactive (Disembunyikan)</option>
                    </select>
                    <p class="mt-1 text-[10px] text-[#6e6584]">Kategori inactive tidak akan muncul di halaman pemesanan customer.</p>
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
