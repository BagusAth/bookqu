@extends('layouts.owner-layout')

@section('title', 'Unit')

@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    <x-owner.page-header title="Unit" purpose="Kelola ruang, lapangan, atau peralatan yang dipakai bersama." />
    @if ($galleryUrl)
        <a class="inline-block text-sm bq-link" href="{{ $galleryUrl }}">Unggah gambar ke galeri</a>
    @endif
    <details id="unit-create" class="bq-panel p-4" @if ($errors->any() && old('_form') === 'unit-create') open @endif>
        <summary class="flex cursor-pointer list-none items-center gap-2 font-semibold text-bq-primary [&::-webkit-details-marker]:hidden"><x-owner.icon name="plus" class="h-5 w-5" />Tambah unit</summary>
        @include('owner.partials.unit-form', ['unit' => null])
    </details>

    @forelse ($units as $unit)
        <details id="unit-edit-{{ $unit['id'] }}" class="bq-panel p-4" @if ($errors->any() && old('_form') === 'unit-edit-'.$unit['id']) open @endif>
            <summary class="flex cursor-pointer list-none flex-wrap items-center gap-x-3 gap-y-2 [&::-webkit-details-marker]:hidden">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-bq-primary-light text-bq-primary"><x-owner.icon name="units" /></span>
                <span class="min-w-0 flex-1">
                    <span class="block font-semibold text-bq-ink">{{ $unit['name'] }}</span>
                    <span class="block text-sm text-bq-text-muted">{{ $unit['type'] }} · {{ $unit['kapasitas'] }} orang · {{ count($unit['service_names']) }} layanan</span>
                </span>
                @if ($unit['usage_today'])
                    <span class="hidden w-32 sm:block" title="Pemakaian hari ini">
                        <span class="block h-1.5 overflow-hidden rounded-full bg-bq-surface-2"><span class="block h-full rounded-full bg-bq-primary" style="width: {{ min(100, (float) $unit['usage_today']['rate']) }}%"></span></span>
                        <span class="bq-num mt-1 block text-xs text-bq-text-muted">{{ number_format($unit['usage_today']['rate'], 0, ',', '.') }}% hari ini</span>
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

    <section aria-labelledby="unit-conflicts" class="bq-panel p-4">
        <h2 id="unit-conflicts" class="flex items-center gap-2 font-semibold text-bq-ink"><x-owner.icon name="alert" class="h-5 w-5 text-bq-warning" />Konflik booking</h2>
        <ul class="mt-2 space-y-1 text-sm">
            @forelse ($conflicts as $conflict)
                <li>
                    {{ collect($units)->firstWhere('id', $conflict['unit_id'])['name'] }}
                    @foreach ($conflict['booking_ids'] as $bookingId)
                        @if ($detail = $bookingDetails[$bookingId] ?? null)
                            <p class="ms-3">
                                <button type="button" class="bq-link" x-data
                                    @click="$dispatch('open-booking-panel', { url: @js($detail['url']), trigger: $el })">{{ $detail['code'] }}</button>
                                · {{ $detail['customer'] }} · {{ $detail['session'] }}
                            </p>
                        @endif
                    @endforeach
                </li>
            @empty
                <li class="text-bq-text-muted">Tidak ada booking yang bertabrakan.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
