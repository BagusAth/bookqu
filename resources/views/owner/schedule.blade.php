@extends('layouts.owner-layout')

@section('title', 'Jam Buka & Harga')

@push('head')
    <script defer src="{{ asset('js/owner-schedule.js') }}"></script>
@endpush

@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    <x-owner.page-header title="Jam Buka & Harga" purpose="Atur jam operasional, harga, tanggal tutup, dan slot booking.">
        <x-slot:action>
            <a class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-semibold text-white" href="{{ route('owner.schedule', ['tab' => 'slots', 'buat' => 1]) }}">Buka slot</a>
        </x-slot:action>
    </x-owner.page-header>

    <ul class="flex flex-wrap gap-2" aria-label="Jangkauan slot">
        @foreach ($runway as $serviceRunway)
            <li class="rounded-lg border border-bq-border bg-bq-surface px-3 py-2 text-sm {{ $serviceRunway['warning'] ? 'font-semibold' : '' }}">
                {{ $serviceRunway['service'] }}:
                @if ($serviceRunway['open_until'])
                    slot terbuka sampai {{ \Carbon\Carbon::parse($serviceRunway['open_until'])->locale('id')->translatedFormat('j M') }}
                @else
                    belum ada slot terbuka
                @endif
                @if ($serviceRunway['warning'])
                    <span class="block text-bq-text-muted">Periksa ketersediaan slot 7 hari ke depan.</span>
                @endif
            </li>
        @endforeach
    </ul>

    <x-owner.tabs :tabs="$tabs" :active="$tab" />

    <section role="tabpanel" aria-label="{{ $tabs[$tab] }}">
        @include('owner.schedule.' . $tab)
    </section>
</div>
@endsection
