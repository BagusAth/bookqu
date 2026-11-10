{{--
    Page header (BQ-129, owner-portal.md §9): title, one-line purpose, one primary action.
    `judul`, `subjudul` and the `aksi` slot are the former names, kept for pages not yet migrated.
--}}
@props([
    'title' => null,
    'purpose' => null,
    'judul' => '',
    'subjudul' => '',
    'aksi' => null,
    'icon' => null,
])

@php
    $heading = $title ?? $judul;
    $subheading = $purpose ?? $subjudul;
    $primaryAction = $action ?? $aksi;
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div class="min-w-0">
        <h1 class="text-xl font-semibold text-bq-text sm:text-2xl">{{ $heading }}</h1>
        @if ($subheading)
            <p class="mt-1 text-sm text-bq-text-muted">{{ $subheading }}</p>
        @endif
    </div>
    @if ($primaryAction)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $primaryAction }}
        </div>
    @endif
</div>
