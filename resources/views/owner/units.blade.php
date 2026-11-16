@extends('layouts.owner-layout')

@section('title', 'Unit')

@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    <x-owner.page-header title="Unit" purpose="Kelola ruang, lapangan, atau peralatan yang dipakai bersama." />
    <details class="rounded-xl border border-bq-border bg-bq-surface p-4" @if ($errors->any()) open @endif>
        <summary class="cursor-pointer font-semibold">Tambah unit</summary>
        @include('owner.partials.unit-form', ['unit' => null])
    </details>

    @forelse ($units as $unit)
        <details class="rounded-xl border border-bq-border bg-bq-surface p-4">
            <summary class="cursor-pointer">
                <span class="font-semibold">{{ $unit['name'] }}</span>
                <span class="ms-2 text-sm text-bq-text-muted">{{ $unit['type'] }} · {{ $unit['kapasitas'] }} orang</span>
                <x-owner.status-badge type="availability" :status="$unit['is_active'] ? 'active' : 'inactive'" />
            </summary>
            @include('owner.partials.unit-form', ['unit' => $unit])
            <form method="POST" action="{{ route('owner.units.destroy', $unit['id']) }}" class="mt-4">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm font-semibold text-bq-danger">Hapus unit</button>
            </form>
        </details>
    @empty
        <x-owner.empty-state title="Belum ada unit" description="Tambahkan unit untuk menghubungkannya dengan layanan." />
    @endforelse

    <section aria-labelledby="unit-conflicts" class="rounded-xl border border-bq-border bg-bq-surface p-4">
        <h2 id="unit-conflicts" class="font-semibold">Konflik booking</h2>
        <ul class="mt-2 space-y-1 text-sm">
            @forelse ($conflicts as $conflict)
                <li>
                    {{ collect($units)->firstWhere('id', $conflict['unit_id'])['name'] }}
                    @foreach ($conflict['booking_ids'] as $bookingId)
                        <button type="button" class="text-bq-primary" x-data
                            @click="$dispatch('open-booking-panel', { url: @js(route('owner.bookings.panel', $bookingId)), trigger: $el })">#{{ $bookingId }}</button>
                    @endforeach
                </li>
            @empty
                <li class="text-bq-text-muted">Tidak ada booking yang bertabrakan.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
