@extends('customer.layouts.booking-shell')

@section('title', 'Invoice')
@section('back_url', $invoiceBackUrl)
@section('back_label', 'Kembali')

@section('content')
    <article class="mx-auto max-w-3xl space-y-4">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <x-customer.page-header title="Invoice pembayaran" description="Bukti pembayaran reservasi" />
            <button type="button" class="no-print bq-btn bq-btn-primary min-h-11" onclick="window.print()">Cetak invoice</button>
        </header>

        <x-ui.panel>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    @if ($invoice['logo'])
                        <img src="{{ $invoice['logo'] }}" alt="" class="mb-2 size-10 rounded-bq-control object-cover">
                    @endif
                    <h2 class="text-bq-section">{{ $tenant->namabisnis }}</h2>
                    @if ($tenant->alamat)
                        <p class="mt-1 text-bq-small text-bq-text-muted">{{ $tenant->alamat }}</p>
                    @endif
                </div>
                <x-customer.status :label="$invoice['status']" :tone="$invoice['status'] === 'Lunas' ? 'success' : 'neutral'" />
            </div>
            <x-ui.kv class="mt-4" :items="[
                'Nomor pesanan' => $invoice['number'],
                'Tanggal invoice' => $invoice['date'],
                'Metode pembayaran' => $invoice['method'],
            ]" />
        </x-ui.panel>

        <x-ui.panel title="Pemesan">
            <x-ui.kv :items="array_filter(['Nama' => $invoice['name'], 'Email' => $invoice['email'], 'Nomor WhatsApp' => $invoice['phone']])" />
        </x-ui.panel>

        <x-ui.panel title="Jadwal dan rincian" :description="$invoice['sessionDate']">
            <ul class="divide-y divide-bq-border">
                @foreach ($invoice['sessions'] as $session)
                    <li class="flex flex-wrap justify-between gap-x-4 gap-y-1 py-3 print:break-inside-avoid">
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $session['service'] }}</p>
                            <p class="bq-num text-bq-small">{{ $session['time'] }} WIB @if($session['minutes'] !== null) · {{ $session['minutes'] }} menit @endif</p>
                            <p class="break-all text-bq-small text-bq-text-muted">{{ $session['code'] }}</p>
                        </div>
                        <span class="bq-num text-bq-body">{{ $session['amount'] }}</span>
                    </li>
                @endforeach
            </ul>
            @if ($invoice['extras'])
                <x-ui.kv class="border-t border-bq-border pt-3">
                    @foreach ($invoice['extras'] as $item)
                        <div class="flex flex-wrap justify-between gap-2">
                            <dt>{{ $item['label'] }}</dt>
                            <dd class="bq-num">{{ $item['amount'] }}</dd>
                        </div>
                    @endforeach
                </x-ui.kv>
            @endif
            @if ($invoice['note'])
                <p class="border-t border-bq-border py-3 text-bq-small text-bq-text-muted">{{ $invoice['note'] }}</p>
            @endif
            <div class="flex flex-wrap items-baseline justify-between gap-2 border-t border-bq-border pt-3">
                <p class="font-semibold">Total pembayaran</p>
                <p class="bq-num text-bq-section">{{ $invoice['amount'] }}</p>
            </div>
        </x-ui.panel>

        <div class="no-print flex flex-wrap gap-2">
            <x-ui.button :href="$calendarUrl" target="_blank" rel="noopener noreferrer" class="min-h-11">Tambahkan ke Google Calendar</x-ui.button>
            <x-ui.button :href="$shareUrl" target="_blank" rel="noopener noreferrer" class="min-h-11">Bagikan ke WhatsApp</x-ui.button>
            @if ($mapsUrl)
                <x-ui.button :href="$mapsUrl" target="_blank" rel="noopener noreferrer" class="min-h-11">Petunjuk arah</x-ui.button>
            @endif
        </div>
    </article>
@endsection
