@extends('layouts.owner-layout')

@section('title', 'Programs')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data>

    {{-- ── Flash Message ── --}}
    @if (session('sukses'))
        <div x-data="{ tampil: true }" x-show="tampil" x-init="setTimeout(() => tampil = false, 4000)"
            x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3" id="flash-success">
            <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm font-medium text-emerald-800">{{ session('sukses') }}</p>
        </div>
    @endif

    {{-- ── Header ── --}}
    @include('components.owner.page-header', [
        'judul' => 'Program Management',
        'subjudul' => 'Manage your services and programs offered to customers.',
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
            <div class="group rounded-xl border border-bq-border bg-bq-surface p-5 transition-all duration-300 hover:border-bq-border-strong hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    @php
                        $editPayload = [
                            'id' => $layanan->id,
                            'namalayanan' => $layanan->namalayanan,
                            'harga' => $layanan->harga,
                            'durasi' => $layanan->durasi,
                            'deskripsi' => $layanan->deskripsi ?: '',
                            'is_active' => (int) ($layanan->is_active ?? 1),
                        ];
                    @endphp
                    <div class="flex items-center gap-1">
                        <button
                            class="rounded-lg p-1.5 text-bq-text-subtle opacity-0 transition-all hover:bg-bq-background hover:text-bq-text group-hover:opacity-100"
                            @click="$dispatch('open-edit-program', @json($editPayload))"
                            aria-label="Edit program"
                            id="btn-edit-program-{{ $layanan->id }}"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4h6a2 2 0 012 2v6m-10 6H6a2 2 0 01-2-2v-6m12-4L8 18l-4 1 1-4 10-10z"/>
                            </svg>
                        </button>
                        <form method="POST" action="/owner/programs/{{ $layanan->id }}" class="opacity-0 transition-all group-hover:opacity-100">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="rounded-lg p-1.5 text-bq-text-subtle transition-all hover:bg-rose-50 hover:text-rose-600"
                                onclick="return confirm('Hapus program ini?');"
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
                <div class="mt-3">
                    <h3 class="text-sm font-semibold text-bq-text">{{ $layanan->namalayanan }}</h3>
                    <p class="mt-1 text-xs text-bq-text-muted line-clamp-2">{{ $layanan->deskripsi ?? 'No description' }}</p>
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
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ ($layanan->is_active ?? true) ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ ($layanan->is_active ?? true) ? 'Active' : 'Non-Active' }}
                        </span>
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
