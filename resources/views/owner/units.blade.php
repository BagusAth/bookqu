@extends('layouts.owner-layout')

@section('title', 'Unit')

@section('content')
{{-- BQ-162: Unit as in the Papan Jadwal prototype (#/unit): conflicts first, then one table; forms open in the side panel. --}}
<div class="mx-auto max-w-7xl space-y-5" x-data
    @if ($errors->any() && str_starts_with((string) old('_form'), 'unit-')) x-init="$nextTick(() => $dispatch('open-dialog', { id: @js(old('_form')) }))" @endif>
    <x-owner.page-header title="Unit" purpose="Ruang, lapangan atau alat yang dipakai layanan Anda. Dua booking tidak bisa memakai unit yang sama pada jam yang sama.">
        <x-slot:action>
            <x-ui.button variant="primary" icon="plus" x-on:click="$dispatch('open-dialog', { id: 'unit-create', trigger: $el })">Tambah unit</x-ui.button>
        </x-slot:action>
    </x-owner.page-header>

    <x-ui.panel title="Konflik mendatang" description="Dua booking yang sudah memakai unit yang sama pada jam yang sama. Pindahkan salah satunya." flush>
        @if ($upcomingConflicts)
            @include('owner.partials.unit-conflicts', ['conflicts' => $upcomingConflicts, 'upcoming' => true])
        @else
            <p class="px-5 py-3 text-bq-small text-bq-text-muted">Tidak ada konflik mendatang.</p>
        @endif
        @if ($pastConflicts)
            <details class="group border-t border-bq-border">
                <summary class="flex cursor-pointer list-none items-center gap-2 px-5 py-3 text-bq-small font-semibold text-bq-ink [&::-webkit-details-marker]:hidden">
                    <x-owner.icon name="chevron-right" class="h-4 w-4 transition-transform group-open:rotate-90" />{{ count($pastConflicts) }} konflik lampau
                </summary>
                <p class="px-5 pb-2 text-bq-small text-bq-text-muted">Sesi sudah lewat, tidak bisa ditindak lagi. Disimpan untuk catatan.</p>
                @include('owner.partials.unit-conflicts', ['conflicts' => $pastConflicts])
            </details>
        @endif
    </x-ui.panel>

    @if ($units === [])
        <section class="bq-panel">
            <x-ui.empty title="Belum ada unit" description="Tambahkan ruang, lapangan atau alat, lalu hubungkan dengan layanan yang memakainya." icon="units">
                <x-slot:action><x-ui.button variant="primary" icon="plus" x-on:click="$dispatch('open-dialog', { id: 'unit-create', trigger: $el })">Tambah unit</x-ui.button></x-slot:action>
            </x-ui.empty>
        </section>
    @else
        <x-ui.table :columns="['Unit', 'Kapasitas', 'Lokasi', 'Layanan', 'Terpakai hari ini']" widths="minmax(200px, 2fr) auto auto auto auto" label="Daftar unit">
            @foreach ($units as $unit)
                @php $usage = $unit['usage_today']; @endphp
                <x-ui.table.row :two-lines="true" :muted="! $unit['is_active']">
                    <x-ui.table.cell phone="main" :title="$unit['name']">
                        <button type="button" class="max-w-full truncate text-left font-semibold text-bq-ink hover:text-bq-primary" x-data
                            x-on:click="$dispatch('open-dialog', { id: 'unit-edit-{{ $unit['id'] }}', trigger: $el })">{{ $unit['name'] }}</button>
                        <x-slot:subtitle><span class="block truncate text-bq-small text-bq-text-muted">{{ $unit['type'] }}<span class="md:hidden">{{ $unit['lokasi'] ? ' · '.$unit['lokasi'] : '' }} · {{ count($unit['service_names']) }} layanan</span></span></x-slot:subtitle>
                    </x-ui.table.cell>
                    <x-ui.table.cell><span class="whitespace-nowrap">{{ $unit['kapasitas'] }} orang</span></x-ui.table.cell>
                    <x-ui.table.cell><span class="whitespace-nowrap">{{ $unit['lokasi'] ?: '—' }}</span></x-ui.table.cell>
                    <x-ui.table.cell :title="implode(', ', $unit['service_names'])"><span class="whitespace-nowrap">{{ count($unit['service_names']) }} layanan</span></x-ui.table.cell>
                    <x-ui.table.cell phone="end">
                        @if (! $unit['is_active'])
                            <span class="text-bq-text-muted">Nonaktif</span>
                        @elseif ($usage && $usage['offeredMinutes'] > 0)
                            <x-ui.mini-bar :value="rtrim(rtrim(number_format($usage['usedMinutes'] / 60, 1, ',', ''), '0'), ',')" :total="rtrim(rtrim(number_format($usage['offeredMinutes'] / 60, 1, ',', ''), '0'), ',')" unit="jam" />
                        @else
                            <span class="text-bq-text-muted">Tidak ada slot</span>
                        @endif
                    </x-ui.table.cell>
                </x-ui.table.row>
            @endforeach
        </x-ui.table>
    @endif
</div>

<x-owner.drawer id="unit-create" title="Tambah unit">
    @include('owner.partials.unit-form', ['unit' => null])
</x-owner.drawer>
@foreach ($units as $unit)
    <x-owner.drawer id="unit-edit-{{ $unit['id'] }}" title="Ubah unit">
        @include('owner.partials.unit-form', ['unit' => $unit])
        @if ($galleryUrl)
            <p class="mt-3 text-bq-small"><a class="bq-link" href="{{ $galleryUrl }}">Unggah gambar ke galeri</a></p>
        @endif
        <form method="POST" id="delete-unit-{{ $unit['id'] }}" x-data action="{{ route('owner.units.destroy', $unit['id']) }}" class="mt-4 border-t border-bq-border pt-4">
            @csrf
            @method('DELETE')
            <x-ui.button variant="danger" size="sm" icon="trash"
                @click="$dispatch('open-confirm', { title: 'Hapus unit?', message: {{ \Illuminate\Support\Js::from('Hapus unit '.$unit['name'].'?') }}, formId: $el.form.id })">Hapus unit</x-ui.button>
        </form>
    </x-owner.drawer>
@endforeach
@endsection
