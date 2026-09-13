{{-- Page Header Component --}}
@props([
    'judul' => '',
    'subjudul' => '',
    'aksi' => null,
    'icon' => null,
])

@php
    $judulLower = strtolower($judul);
@endphp

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-black tracking-tight text-[#231a3d] sm:text-3xl">{{ $judul }}</h1>
        @if ($subjudul)
            <p class="mt-1 text-xs sm:text-sm text-[#6e6584] leading-relaxed">{{ $subjudul }}</p>
        @endif
    </div>
    @if ($aksi)
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            {{ $aksi }}
        </div>
    @endif
</div>
