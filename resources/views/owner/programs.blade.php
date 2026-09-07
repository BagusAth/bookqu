@extends('layouts.owner-layout')

@section('title', 'Services')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data>

    {{-- ── Header ── --}}
    @include('components.owner.page-header', [
        'judul' => 'Services Management',
        'subjudul' => 'Manage your business services, pricing, and programs offered to customers.',
    ])

    {{-- ── Stats ── --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @include('components.owner.stat-card', ['ikon' => 'program', 'label' => 'Total Programs', 'nilai' => $totallayanan, 'perubahan' => 0, 'tipeperubahan' => 'stabil'])
        @include('components.owner.stat-card', ['ikon' => 'revenue', 'label' => 'Avg. Price', 'nilai' => 'Rp ' . number_format($ratarataharga, 0, ',', '.'), 'perubahan' => 0, 'tipeperubahan' => 'stabil'])
        @include('components.owner.stat-card', ['ikon' => 'booking', 'label' => 'Bookings This Month', 'nilai' => number_format($totalbookinglayanan), 'perubahan' => 0, 'tipeperubahan' => 'stabil'])
        @include('components.owner.stat-card', ['ikon' => 'revenue', 'label' => 'Revenue This Month', 'nilai' => 'Rp ' . number_format($pendapatanlayanan, 0, ',', '.'), 'perubahan' => 0, 'tipeperubahan' => 'stabil'])
    </div>

    {{-- ── Search & Actions ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="/owner/programs" class="relative w-full sm:max-w-xs">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                type="text"
                name="katakunci"
                value="{{ $katakunci }}"
                placeholder="Search programs..."
                class="w-full rounded-lg border border-bq-border bg-bq-surface py-2.5 pl-10 pr-4 text-sm text-bq-text placeholder-bq-text-subtle transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                id="input-search-programs"
            >
        </form>
        <button @click="$dispatch('open-add-program')" class="inline-flex items-center gap-2 rounded-lg bg-bq-primary px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-bq-primary/25 transition-all hover:bg-bq-primary-hover hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0" id="btn-add-program">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Program
        </button>
    </div>

    {{-- ── Program Cards Grid ── --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" id="programs-grid">
        @forelse ($daftarlayanan as $layanan)
            <div class="group rounded-xl border border-bq-border bg-bq-surface transition-all duration-300 hover:border-bq-border-strong hover:shadow-md overflow-hidden">
                {{-- Cover Image / Placeholder --}}
                <div class="relative h-36 bg-gradient-to-br from-indigo-50 to-indigo-100 flex items-center justify-center">
                    @if ($layanan->image_url)
                        @php
                            $cardImageUrl = \Illuminate\Support\Str::startsWith($layanan->image_url, ['http://', 'https://', '/'])
                                ? $layanan->image_url
                                : \Illuminate\Support\Facades\Storage::url($layanan->image_url);
                        @endphp
                        <img src="{{ $cardImageUrl }}" alt="{{ $layanan->namalayanan }}" class="h-full w-full object-cover">
                    @else
                        <svg class="h-10 w-10 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    @endif
                    {{-- Action buttons overlay --}}
                    @php
                        $editPayload = [
                            'id'                  => $layanan->id,
                            'namalayanan'         => $layanan->namalayanan,
                            'idcategory'          => $layanan->idcategory ?? '',
                            'harga'               => $layanan->harga,
                            'durasi'              => $layanan->durasi,
                            'deskripsi'           => $layanan->deskripsi ?: '',
                            'is_active'           => (int) ($layanan->is_active ?? 1),
                            'staff_ids'           => $layanan->staff->pluck('id')->toArray(),
                            'resource_ids'        => $layanan->resources->pluck('id')->toArray(),
                            'additional_item_ids' => $layanan->additionalItems->pluck('id')->toArray(),
                            'image_url'           => $layanan->image_url
                                ? (\Illuminate\Support\Str::startsWith($layanan->image_url, ['http://', 'https://', '/'])
                                    ? $layanan->image_url
                                    : \Illuminate\Support\Facades\Storage::url($layanan->image_url))
                                : null,
                        ];
                    @endphp
                    <div class="absolute top-2 right-2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all">
                        <button
                            class="rounded-lg p-1.5 bg-white/90 shadow text-bq-text-subtle transition-all hover:bg-white hover:text-bq-primary"
                            @click='$dispatch("open-edit-program", @json($editPayload))'
                            aria-label="Edit program"
                            id="btn-edit-program-{{ $layanan->id }}"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <form method="POST" action="/owner/programs/{{ $layanan->id }}" id="form-delete-program-{{ $layanan->id }}">
                            @csrf
                            @method('DELETE')
                            <button
                                type="button"
                                class="rounded-lg p-1.5 bg-white/90 shadow text-bq-text-subtle transition-all hover:bg-rose-50 hover:text-rose-600"
                                @click="$dispatch('open-confirm', { title: 'Hapus Program?', message: 'Program yang sudah dihapus tidak dapat dikembalikan. Yakin ingin menghapus program ini?', formId: 'form-delete-program-{{ $layanan->id }}' })"
                                aria-label="Delete program"
                                id="btn-delete-program-{{ $layanan->id }}"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Body --}}
                <div class="p-5 flex flex-col justify-between flex-1">
                    <div>
                        <div class="flex flex-wrap items-center gap-1.5 mb-2">
                            @if($layanan->category)
                                <span class="inline-block rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-700">
                                    {{ $layanan->category->name }}
                                </span>
                            @endif
                            @if($layanan->additionalItems->isNotEmpty())
                                <span class="inline-block rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                                    +{{ $layanan->additionalItems->count() }} Add-ons
                                </span>
                            @endif
                        </div>
                        <h3 class="text-sm font-semibold text-bq-text">{{ $layanan->namalayanan }}</h3>
                        <p class="mt-1 text-xs text-bq-text-muted line-clamp-2">{{ $layanan->deskripsi ?? 'No description' }}</p>

                        {{-- Hierarchy: Staff & Resources breakdown --}}
                        <div class="mt-3 space-y-1.5">
                            @if($layanan->staff->isNotEmpty())
                                <div class="flex items-center gap-1 text-[11px] text-bq-text-muted">
                                    <svg class="h-3.5 w-3.5 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span class="truncate">Staff: {{ $layanan->staff->pluck('name')->join(', ') }}</span>
                                </div>
                            @endif
                            @if($layanan->resources->isNotEmpty())
                                <div class="flex items-center gap-1 text-[11px] text-bq-text-muted">
                                    <svg class="h-3.5 w-3.5 text-sky-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    <span class="truncate">Resource: {{ $layanan->resources->pluck('name')->join(', ') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-bq-border pt-3">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-bold text-bq-primary">Rp {{ number_format($layanan->harga, 0, ',', '.') }}</span>
                            <span class="text-xs text-bq-text-subtle">{{ $layanan->durasi }} min</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 rounded-full bg-bq-background px-2 py-0.5 text-xs font-medium text-bq-text-muted">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                {{ $layanan->bookings_count }} bookings
                            </span>
                            <form method="POST" action="{{ route('owner.services.toggle', $layanan->id) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold transition {{ ($layanan->is_active ?? true) ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-amber-100 text-amber-700 hover:bg-amber-200' }}" title="Klik untuk ubah status">
                                    {{ ($layanan->is_active ?? true) ? 'Active' : 'Non-Active' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        @empty
            <div class="col-span-full rounded-xl border border-dashed border-bq-border-strong bg-bq-surface p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <h3 class="mt-3 text-sm font-semibold text-bq-text">No programs found</h3>
                <p class="mt-1 text-xs text-bq-text-muted">Get started by adding your first program.</p>
            </div>
        @endforelse
    </div>

    {{-- ── Pagination ── --}}
    @if ($daftarlayanan->hasPages())
        <div class="flex justify-center">
            {{ $daftarlayanan->appends(['katakunci' => $katakunci])->links() }}
        </div>
    @endif

</div>

{{-- Add Program Modal --}}
@include('components.owner.modal-add-program')
{{-- Edit Program Modal --}}
@include('components.owner.modal-edit-program')
@endsection
