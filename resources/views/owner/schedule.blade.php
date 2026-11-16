@extends('layouts.owner-layout')

@section('title', 'Jam Buka & Harga')

@push('head')
    <script defer src="{{ asset('js/owner-schedule.js') }}"></script>
@endpush

@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    <x-owner.page-header title="Jam Buka & Harga" purpose="Atur jam operasional, harga, tanggal tutup, dan slot booking.">
        <x-slot:action>
            <a class="bq-btn bq-btn-primary" href="{{ route('owner.schedule', ['tab' => 'slots', 'buat' => 1]) }}">Buka slot</a>
        </x-slot:action>
    </x-owner.page-header>

    {{-- BQ-161 (audit L-03): one summary instead of one box per service; the per-service list stays one click away. --}}
    @php $runwayWarnings = collect($runway)->where('warning', true)->count(); @endphp
    <details @class(['group rounded-xl border', 'border-bq-warning/30 bg-bq-warning-bg/60' => $runwayWarnings > 0, 'border-bq-border bg-bq-surface' => $runwayWarnings === 0]) @if ($runwayWarnings > 0 && $runwayWarnings < count($runway)) open @endif>
        <summary class="flex cursor-pointer list-none items-center gap-3 px-4 py-3 text-sm [&::-webkit-details-marker]:hidden">
            <x-owner.icon :name="$runwayWarnings > 0 ? 'alert' : 'check'" @class(['h-5 w-5 shrink-0', 'text-bq-warning' => $runwayWarnings > 0, 'text-bq-success' => $runwayWarnings === 0]) />
            <span class="flex-1">
                @if ($runwayWarnings > 0)
                    <span class="font-semibold text-bq-ink">{{ $runwayWarnings }} dari {{ count($runway) }} layanan belum punya slot terbuka 7 hari ke depan.</span>
                    <span class="block text-bq-text-muted">Periksa ketersediaan slot 7 hari ke depan.</span>
                @else
                    <span class="font-semibold text-bq-ink">Semua layanan punya slot terbuka 7 hari ke depan.</span>
                @endif
            </span>
            <x-owner.icon name="chevron-down" class="h-4 w-4 shrink-0 text-bq-text-muted transition-transform group-open:rotate-180" />
        </summary>
        <ul class="grid gap-x-6 gap-y-1.5 border-t border-bq-border/70 px-4 py-3 text-sm sm:grid-cols-2" aria-label="Jangkauan slot">
            @foreach ($runway as $serviceRunway)
                <li class="flex items-center justify-between gap-3">
                    <span class="truncate text-bq-ink">{{ $serviceRunway['service'] }}</span>
                    @if ($serviceRunway['open_until'])
                        <span class="shrink-0 text-bq-success">slot terbuka sampai {{ \Carbon\Carbon::parse($serviceRunway['open_until'])->locale('id')->translatedFormat('j M') }}</span>
                    @else
                        <span class="shrink-0 font-semibold text-bq-warning">belum ada slot terbuka</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </details>

    <x-owner.tabs :tabs="$tabs" :active="$tab" />

    <section role="tabpanel" aria-label="{{ $tabs[$tab] }}">
        @include('owner.schedule.' . $tab)
    </section>
</div>
@endsection
