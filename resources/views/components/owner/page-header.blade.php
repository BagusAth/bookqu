{{-- Page Header Component --}}
@props([
    'judul' => '',
    'subjudul' => '',
    'aksi' => null,
])

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-bq-text sm:text-3xl">{{ $judul }}</h1>
        @if ($subjudul)
            <p class="mt-1 text-sm text-bq-text-muted">{{ $subjudul }}</p>
        @endif
    </div>
    @if ($aksi)
        <div class="flex items-center gap-3">
            {{ $aksi }}
        </div>
    @endif
</div>
