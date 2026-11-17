@extends('layouts.owner-layout')

@section('title', 'Unit')

@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    <x-owner.page-header title="Unit" purpose="Ruang, lapangan atau alat yang dipakai layanan Anda. Dua booking tidak bisa memakai unit yang sama pada jam yang sama." />
    @if ($galleryUrl)
        <a class="inline-block text-sm bq-link" href="{{ $galleryUrl }}">Unggah gambar ke galeri</a>
    @endif
    <details id="unit-create" class="bq-panel p-4" @if ($errors->any() && old('_form') === 'unit-create') open @endif>
        <summary class="flex cursor-pointer list-none items-center gap-2 font-semibold text-bq-primary [&::-webkit-details-marker]:hidden"><x-owner.icon name="plus" class="h-5 w-5" />Tambah unit</summary>
        @include('owner.partials.unit-form', ['unit' => null])
    </details>

    <section aria-labelledby="unit-conflicts" class="bq-panel space-y-3 p-4">
        <div>
            <h2 id="unit-conflicts" class="font-semibold">Konflik mendatang</h2>
            <p class="text-bq-small text-bq-text-muted">Dua booking yang sudah memakai unit yang sama pada jam yang sama. Pindahkan salah satunya.</p>
        </div>
        @if ($upcomingConflicts)
            @include('owner.partials.unit-conflicts', ['conflicts' => $upcomingConflicts])
        @else
            <p class="text-bq-small text-bq-text-muted">Tidak ada konflik mendatang.</p>
        @endif
        @if ($pastConflicts)
            <details class="border-t border-bq-border pt-3">
                <summary class="cursor-pointer font-semibold">{{ count($pastConflicts) }} konflik lampau</summary>
                <p class="my-2 text-bq-small text-bq-text-muted">Sesi sudah lewat, tidak bisa ditindak lagi. Disimpan untuk catatan.</p>
                @include('owner.partials.unit-conflicts', ['conflicts' => $pastConflicts])
            </details>
        @endif
    </section>

    @forelse ($units as $unit)
        <details id="unit-edit-{{ $unit['id'] }}" class="bq-panel p-4" @if ($errors->any() && old('_form') === 'unit-edit-'.$unit['id']) open @endif>
            <summary class="flex cursor-pointer list-none flex-wrap items-center gap-x-3 gap-y-2 [&::-webkit-details-marker]:hidden">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-bq-primary-light text-bq-primary"><x-owner.icon name="units" /></span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate font-semibold text-bq-ink" title="{{ $unit['name'] }}">{{ $unit['name'] }}</span>
                    <span class="block text-sm text-bq-text-muted">{{ $unit['type'] }}{{ $unit['lokasi'] ? ' · '.$unit['lokasi'] : '' }} · {{ $unit['kapasitas'] }} orang · {{ count($unit['service_names']) }} layanan</span>
                </span>
                @if ($unit['usage_today'])
                    <span class="hidden sm:inline-flex">
                        <x-ui.mini-bar :value="$unit['usage_today']['usedMinutes']" :total="$unit['usage_today']['offeredMinutes']" unit="menit" />
                    </span>
                @endif
                <x-owner.status-badge type="availability" :status="$unit['is_active'] ? 'active' : 'inactive'" />
                <x-owner.icon name="chevron-down" class="h-4 w-4 text-bq-text-muted" />
            </summary>
            @if ($unit['image_url'])
                <img src="{{ $unit['image_url'] }}" alt="" class="mt-3 h-24 w-24 rounded-lg object-cover" loading="lazy">
            @endif
            <p class="mt-3 text-sm text-bq-text-muted">Layanan yang memakai unit: {{ implode(', ', $unit['service_names']) ?: 'Belum ada' }}</p>
            @if ($unit['usage_today'])
                <p class="mt-1 text-sm text-bq-text-muted">
                    Pemakaian hari ini: {{ number_format($unit['usage_today']['rate'], 1, ',', '.') }}%
                    ({{ $unit['usage_today']['usedMinutes'] }} dari {{ $unit['usage_today']['offeredMinutes'] }} menit)
                </p>
            @endif
            @include('owner.partials.unit-form', ['unit' => $unit])
            <form method="POST" id="delete-unit-{{ $unit['id'] }}" x-data action="{{ route('owner.units.destroy', $unit['id']) }}" class="mt-4">
                @csrf
                @method('DELETE')
                <button type="button" class="bq-btn bq-btn-danger bq-btn-sm"
                    @click="$dispatch('open-confirm', { title: 'Hapus unit?', message: @js('Hapus unit '.$unit['name'].'?'), formId: $el.form.id })">Hapus unit</button>
            </form>
        </details>
    @empty
        <x-owner.empty-state title="Belum ada unit" description="Tambahkan unit untuk menghubungkannya dengan layanan." />
    @endforelse


</div>
@endsection
