@extends('layouts.owner-layout')

@section('title', 'Service Categories')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    search: '{{ addslashes($search ?? '') }}',
    addModalOpen: false,
    editModalOpen: false,
    newCategoryColor: 'purple',
    activeCategory: { id: null, name: '', description: '', color: 'purple', is_active: 1 },
    openEdit(cat) {
        this.activeCategory = { ...cat };
        if (!this.activeCategory.color) {
            this.activeCategory.color = 'purple';
        }
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
            class="craft-btn inline-flex items-center gap-2 rounded-xl bg-[#382186] px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-xs hover:bg-[#2d1a6d] cursor-pointer"
            id="btn-add-category"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            + Tambah Kategori
        </button>
    </div>

    {{-- Alerts Container --}}
    @if (session('sukses'))
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-xs font-semibold text-emerald-800 shadow-2xs">
            <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('sukses') }}</span>
        </div>
    @endif

    @if (session('error') || $errors->any())
        <div class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50/90 px-4 py-3 text-xs font-semibold text-rose-800 shadow-2xs">
            <svg class="h-4 w-4 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <p>{{ session('error') ?? 'Terjadi kesalahan saat memproses kategori:' }}</p>
                @if ($errors->any())
                    <ul class="list-disc pl-4 mt-1 space-y-0.5 font-normal">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif

    {{-- ── Search & Filter Toolbar ── --}}
    <div class="flex flex-col gap-3 rounded-2xl border border-[#e7e2f7] bg-white p-4 shadow-[0_4px_20px_rgba(35,26,61,0.03)] sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('owner.categories') }}" class="relative w-full sm:max-w-xs">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#6e6584]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                type="text"
                name="search"
                value="{{ $search ?? '' }}"
                placeholder="Cari kategori..."
                class="w-full rounded-xl border border-[#e7e2f7] bg-white pl-9 pr-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
            >
        </form>
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex items-center rounded-xl border border-[#e7e2f7] p-1 bg-[#f7f7fa] text-xs">
                <a href="{{ route('owner.categories', array_merge(request()->query(), ['status' => 'all'])) }}" class="rounded-lg px-2.5 py-1 font-bold transition {{ ($status ?? 'all') === 'all' ? 'bg-white text-[#382186] shadow-2xs' : 'text-[#6e6584] hover:text-[#231a3d]' }}">Semua</a>
                <a href="{{ route('owner.categories', array_merge(request()->query(), ['status' => 'active'])) }}" class="rounded-lg px-2.5 py-1 font-bold transition {{ ($status ?? '') === 'active' ? 'bg-white text-[#382186] shadow-2xs' : 'text-[#6e6584] hover:text-[#231a3d]' }}">Aktif</a>
                <a href="{{ route('owner.categories', array_merge(request()->query(), ['status' => 'inactive'])) }}" class="rounded-lg px-2.5 py-1 font-bold transition {{ ($status ?? '') === 'inactive' ? 'bg-white text-[#382186] shadow-2xs' : 'text-[#6e6584] hover:text-[#231a3d]' }}">Nonaktif</a>
            </div>
            <div class="relative">
                <form method="GET" action="{{ route('owner.categories') }}" id="sort-form">
                    @if($search ?? '') <input type="hidden" name="search" value="{{ $search }}"> @endif
                    @if(($status ?? 'all') !== 'all') <input type="hidden" name="status" value="{{ $status }}"> @endif
                    <select
                        name="sort"
                        onchange="document.getElementById('sort-form').submit()"
                        class="rounded-xl border border-[#e7e2f7] bg-white px-3 py-2 text-xs font-bold text-[#231a3d] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                        <option value="newest" {{ ($sort ?? '') === 'newest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="name_asc" {{ ($sort ?? '') === 'name_asc' ? 'selected' : '' }}>Nama (A - Z)</option>
                        <option value="name_desc" {{ ($sort ?? '') === 'name_desc' ? 'selected' : '' }}>Nama (Z - A)</option>
                        <option value="services" {{ ($sort ?? '') === 'services' ? 'selected' : '' }}>Layanan Terbanyak</option>
                    </select>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Categories List / Grid ── --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" id="categories-grid">
        @php
            $colorBadgeClasses = [
                'purple'   => 'bg-[#f3effe] text-[#382186] border-[#e7e2f7]',
                'indigo'   => 'bg-[#f3effe] text-[#382186] border-[#e7e2f7]',
                'lavender' => 'bg-[#f8f6ff] text-[#7a5af8] border-[#e7e2f7]',
                'emerald'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'amber'    => 'bg-amber-50 text-amber-700 border-amber-200',
                'rose'     => 'bg-rose-50 text-rose-700 border-rose-200',
                'sky'      => 'bg-sky-50 text-sky-700 border-sky-200',
            ];
        @endphp

        @forelse ($categories as $cat)
            @php
                $catPayload = [
                    'id'          => $cat->id,
                    'name'        => $cat->name,
                    'description' => $cat->description ?? '',
                    'color'       => $cat->color ?? 'purple',
                    'is_active'   => (int) $cat->is_active,
                ];
                $badgeColor = $colorBadgeClasses[$cat->color ?? 'purple'] ?? $colorBadgeClasses['purple'];
            @endphp
            <div class="craft-card rounded-2xl border border-[#e7e2f7] bg-white p-5 shadow-[0_4px_20px_rgba(35,26,61,0.03)] hover:border-[#b499ff] flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl font-black text-sm border {{ $badgeColor }}">
                                <span>{{ strtoupper(substr($cat->name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-sm text-[#231a3d]">{{ $cat->name }}</h3>
                                <span class="text-[11px] font-semibold text-[#6e6584]">{{ $cat->services_count ?? 0 }} Layanan terhubung</span>
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
                            @click='openEdit(@json($catPayload))'
                            class="craft-btn rounded-xl p-1.5 text-[#6e6584] hover:bg-[#f3effe] hover:text-[#382186] transition cursor-pointer"
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
                                @click="$dispatch('open-confirm', { title: 'Hapus Kategori?', message: 'Kategori {{ addslashes($cat->name) }} akan dihapus dan melepaskan relasi dari layanannya. Yakin ingin menghapus?', formId: 'form-delete-cat-{{ $cat->id }}', confirmText: 'Ya, Hapus Kategori' })"
                                class="craft-btn rounded-xl p-1.5 text-[#6e6584] hover:bg-rose-50 hover:text-rose-600 transition cursor-pointer"
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
                <h3 class="text-sm font-extrabold text-[#231a3d]">{{ ($search ?? '') ? 'Tidak ada kategori yang cocok' : 'Belum ada kategori' }}</h3>
                <p class="mt-1 text-xs text-[#6e6584] max-w-sm mx-auto">
                    {{ ($search ?? '') ? 'Coba periksa kata kunci pencarian Anda atau reset filter untuk melihat semua kategori.' : 'Kelompokkan layanan bisnis Anda untuk memudahkan navigasi pengunjung pada halaman booking.' }}
                </p>
                <div class="mt-4 flex items-center justify-center gap-2">
                    @if($search ?? '')
                        <a href="{{ route('owner.categories') }}" class="craft-btn inline-flex items-center gap-2 rounded-xl border border-[#e7e2f7] bg-white px-4 py-2 text-xs font-bold text-[#6e6584] hover:bg-[#e7e2f7] hover:text-[#231a3d] shadow-xs">
                            Reset Pencarian
                        </a>
                    @endif
                    <button
                        type="button"
                        @click="addModalOpen = true"
                        class="craft-btn inline-flex items-center gap-2 rounded-xl bg-[#382186] px-4 py-2 text-xs font-bold text-white hover:bg-[#2d1a6d] shadow-xs"
                    >
                        + Tambah Kategori Baru
                    </button>
                </div>
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
        @keydown.escape.window="addModalOpen = false"
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
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#231a3d]">Deskripsi Singkat</label>
                    <textarea
                        name="description"
                        rows="2"
                        placeholder="Jelaskan jenis layanan dalam kategori ini..."
                        class="mt-1 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    ></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#231a3d]">Warna Aksen Kategori</label>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <label class="flex items-center gap-1.5 cursor-pointer rounded-xl border border-[#e7e2f7] px-2.5 py-1 hover:bg-[#f7f7fa] text-xs font-semibold">
                            <input type="radio" name="color" value="purple" x-model="newCategoryColor" class="text-[#382186] focus:ring-[#b499ff]">
                            <span class="h-3 w-3 rounded-full bg-[#382186]"></span>
                            <span>Purple</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer rounded-xl border border-[#e7e2f7] px-2.5 py-1 hover:bg-[#f7f7fa] text-xs font-semibold">
                            <input type="radio" name="color" value="lavender" x-model="newCategoryColor" class="text-[#7a5af8] focus:ring-[#b499ff]">
                            <span class="h-3 w-3 rounded-full bg-[#b499ff]"></span>
                            <span>Lavender</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer rounded-xl border border-[#e7e2f7] px-2.5 py-1 hover:bg-[#f7f7fa] text-xs font-semibold">
                            <input type="radio" name="color" value="emerald" x-model="newCategoryColor" class="text-emerald-600 focus:ring-emerald-400">
                            <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                            <span>Emerald</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer rounded-xl border border-[#e7e2f7] px-2.5 py-1 hover:bg-[#f7f7fa] text-xs font-semibold">
                            <input type="radio" name="color" value="amber" x-model="newCategoryColor" class="text-amber-600 focus:ring-amber-400">
                            <span class="h-3 w-3 rounded-full bg-amber-500"></span>
                            <span>Amber</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer rounded-xl border border-[#e7e2f7] px-2.5 py-1 hover:bg-[#f7f7fa] text-xs font-semibold">
                            <input type="radio" name="color" value="rose" x-model="newCategoryColor" class="text-rose-600 focus:ring-rose-400">
                            <span class="h-3 w-3 rounded-full bg-rose-500"></span>
                            <span>Rose</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer rounded-xl border border-[#e7e2f7] px-2.5 py-1 hover:bg-[#f7f7fa] text-xs font-semibold">
                            <input type="radio" name="color" value="sky" x-model="newCategoryColor" class="text-sky-600 focus:ring-sky-400">
                            <span class="h-3 w-3 rounded-full bg-sky-500"></span>
                            <span>Sky</span>
                        </label>
                    </div>
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
        @keydown.escape.window="editModalOpen = false"
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
            <form method="POST" :action="'{{ url('owner/categories') }}/' + activeCategory.id" class="mt-4 space-y-4" id="form-edit-category">
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
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#231a3d]">Deskripsi Singkat</label>
                    <textarea
                        name="description"
                        x-model="activeCategory.description"
                        rows="2"
                        class="mt-1 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] placeholder-[#6e6584] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    ></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#231a3d]">Warna Aksen Kategori</label>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <label class="flex items-center gap-1.5 cursor-pointer rounded-xl border border-[#e7e2f7] px-2.5 py-1 hover:bg-[#f7f7fa] text-xs font-semibold">
                            <input type="radio" name="color" value="purple" x-model="activeCategory.color" class="text-[#382186] focus:ring-[#b499ff]">
                            <span class="h-3 w-3 rounded-full bg-[#382186]"></span>
                            <span>Purple</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer rounded-xl border border-[#e7e2f7] px-2.5 py-1 hover:bg-[#f7f7fa] text-xs font-semibold">
                            <input type="radio" name="color" value="lavender" x-model="activeCategory.color" class="text-[#7a5af8] focus:ring-[#b499ff]">
                            <span class="h-3 w-3 rounded-full bg-[#b499ff]"></span>
                            <span>Lavender</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer rounded-xl border border-[#e7e2f7] px-2.5 py-1 hover:bg-[#f7f7fa] text-xs font-semibold">
                            <input type="radio" name="color" value="emerald" x-model="activeCategory.color" class="text-emerald-600 focus:ring-emerald-400">
                            <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                            <span>Emerald</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer rounded-xl border border-[#e7e2f7] px-2.5 py-1 hover:bg-[#f7f7fa] text-xs font-semibold">
                            <input type="radio" name="color" value="amber" x-model="activeCategory.color" class="text-amber-600 focus:ring-amber-400">
                            <span class="h-3 w-3 rounded-full bg-amber-500"></span>
                            <span>Amber</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer rounded-xl border border-[#e7e2f7] px-2.5 py-1 hover:bg-[#f7f7fa] text-xs font-semibold">
                            <input type="radio" name="color" value="rose" x-model="activeCategory.color" class="text-rose-600 focus:ring-rose-400">
                            <span class="h-3 w-3 rounded-full bg-rose-500"></span>
                            <span>Rose</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer rounded-xl border border-[#e7e2f7] px-2.5 py-1 hover:bg-[#f7f7fa] text-xs font-semibold">
                            <input type="radio" name="color" value="sky" x-model="activeCategory.color" class="text-sky-600 focus:ring-sky-400">
                            <span class="h-3 w-3 rounded-full bg-sky-500"></span>
                            <span>Sky</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#231a3d]">Status</label>
                    <select
                        name="is_active"
                        x-model="activeCategory.is_active"
                        class="mt-1 w-full rounded-xl border border-[#e7e2f7] bg-white px-3.5 py-2 text-xs text-[#231a3d] focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30 shadow-2xs transition"
                    >
                        <option value="1">Active (Ditampilkan)</option>
                        <option value="0">Inactive (Disembunyikan)</option>
                    </select>
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
