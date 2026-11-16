@extends('layouts.owner-layout')

@section('title', 'Unit')

@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    <x-owner.page-header title="Unit" purpose="Kelola ruang, lapangan, atau peralatan yang dipakai bersama." />
    @if ($galleryUrl)
        <a class="inline-block text-sm bq-link" href="{{ $galleryUrl }}">Unggah gambar ke galeri</a>
    @endif
    <details id="unit-create" class="bq-panel p-4" @if ($errors->any() && old('_form') === 'unit-create') open @endif>
        <summary class="cursor-pointer font-semibold">Tambah unit</summary>
        @include('owner.partials.unit-form', ['unit' => null])
    </details>

    @forelse ($units as $unit)
        <details id="unit-edit-{{ $unit['id'] }}" class="bq-panel p-4" @if ($errors->any() && old('_form') === 'unit-edit-'.$unit['id']) open @endif>
            <summary class="cursor-pointer">
                <span class="font-semibold">{{ $unit['name'] }}</span>
                <span class="ms-2 text-sm text-bq-text-muted">{{ $unit['type'] }} · {{ $unit['kapasitas'] }} orang</span>
                <x-owner.status-badge type="availability" :status="$unit['is_active'] ? 'active' : 'inactive'" />
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
                <button type="button" class="text-sm font-semibold text-bq-danger"
                    @click="$dispatch('open-confirm', { title: 'Hapus unit?', message: @js('Hapus unit '.$unit['name'].'?'), formId: $el.form.id })">Hapus unit</button>
            </form>
        </details>
    @empty
        <x-owner.empty-state title="Belum ada unit" description="Tambahkan unit untuk menghubungkannya dengan layanan." />
    @endforelse

    <section aria-labelledby="unit-conflicts" class="bq-panel p-4">
        <h2 id="unit-conflicts" class="font-semibold">Konflik booking</h2>
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
