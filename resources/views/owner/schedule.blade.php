@extends('layouts.owner-layout')

@section('title', 'Jam Buka & Harga')

@push('head')
    <script defer src="{{ asset('js/owner-schedule.js') }}"></script>
@endpush

@section('content')
{{-- BQ-162: follows the Papan Jadwal prototype (#/jam-buka): banner for short runways, one summary panel, then tabs. --}}
<div class="mx-auto max-w-7xl space-y-5">
    <x-owner.page-header title="Jam Buka & Harga" purpose="Kapan Anda buka, berapa harganya, dan kapan tutup.">
        @if ($tab === 'slots' && $daftarlayanan->isNotEmpty())
            <x-slot:action>
                <x-ui.button variant="primary" icon="plus" x-data x-on:click="$dispatch('open-dialog', { id: 'schedule-bulk-dialog', trigger: $el })">Buka slot sekaligus</x-ui.button>
            </x-slot:action>
        @elseif ($tab === 'closed')
            <x-slot:action>
                <x-ui.button variant="primary" icon="plus" x-data x-on:click="$dispatch('open-dialog', { id: 'schedule-close-date-dialog', trigger: $el })">Tutup tanggal</x-ui.button>
            </x-slot:action>
        @endif
    </x-owner.page-header>

    @php $short = $overview['short']; @endphp
    @if (count($short) === 1)
        @php $only = $short[0]; @endphp
        <x-ui.banner tone="warning"
            :title="$only['service'].': '.($only['open_until_label'] ? 'periksa slot untuk 7 hari ke depan' : 'belum ada slot terbuka')"
            :description="$only['auto_open'] ? 'Cakupan slot belum memenuhi batas 7 hari.' : 'Cakupan slot belum memenuhi batas 7 hari. Layanan ini tidak ikut slot otomatis.'">
            <x-slot:action>
                <a class="bq-btn bq-btn-secondary bq-btn-sm" href="{{ route('owner.schedule', ['tab' => 'slots', 'buat' => 1, 'layanan' => $only['service_id']]) }}">Buka slot</a>
            </x-slot:action>
        </x-ui.banner>
    @elseif (count($short) > 1)
        <x-ui.banner tone="warning"
            :title="count($short).' layanan perlu dicek untuk 7 hari ke depan'"
            :description="collect($short)->pluck('service')->take(3)->join(', ').(count($short) > 3 ? ', dan '.(count($short) - 3).' lainnya' : '').'. Cakupan slot belum memenuhi batas 7 hari.'">
            <x-slot:action>
                <a class="bq-btn bq-btn-secondary bq-btn-sm" href="{{ route('owner.schedule', ['tab' => 'slots', 'buat' => 1]) }}">Buka slot</a>
            </x-slot:action>
        </x-ui.banner>
    @endif

    <x-ui.panel>
        <x-ui.figures
            :value="$overview['open_until_label'] ?? 'Belum ada'"
            label="slot terbuka sampai"
            :context="$overview['auto_open'] > 0
                ? $overview['auto_open'].' dari '.$overview['services'].' layanan, dibuka otomatis setiap hari'
                : ($overview['services'] - count($short)).' dari '.$overview['services'].' layanan punya slot 7 hari ke depan'"
            :more="[
                ['value' => $overview['days_ahead'] !== null ? $overview['days_ahead'].' hari' : '0 hari', 'label' => 'ke depan'],
                ['value' => count($short), 'label' => 'layanan perlu cek slot'],
            ]" />
    </x-ui.panel>

    <x-owner.tabs :tabs="$tabs" :active="$tab" />

    <section role="tabpanel" aria-label="{{ $tabs[$tab] }}">
        @include('owner.schedule.' . $tab)
    </section>
</div>
@endsection
