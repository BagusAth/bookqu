@extends('customer.layouts.booking-shell')

@section('title', 'Invoice')
@section('immersive', true)

@php
    // Success mode right after paying (storefront route); invoice mode when opened from Kelola (design A §7.5).
    $isSuccess = request()->routeIs('customer.booking.*');
    [$weekday, $dateRest] = array_pad(explode(', ', $invoice['sessionDate'], 2), 2, '');
    [$day, $month] = array_pad(explode(' ', $dateRest), 2, '');
    $sessions = collect($invoice['sessions']);
    $firstTime = (string) $sessions->first()['time'];
    $lastTime = (string) $sessions->last()['time'];
    $ticketTime = \Illuminate\Support\Str::before($firstTime, ' – ').' – '.\Illuminate\Support\Str::afterLast($lastTime, ' – ').' WIB';
    $isPaid = $invoice['status'] === 'Lunas';
    $actions = array_filter([
        ['label' => 'Tambahkan ke Google Calendar', 'icon' => 'calendar-add', 'href' => $calendarUrl],
        ['label' => 'Bagikan ke WhatsApp', 'icon' => 'whatsapp', 'href' => $shareUrl],
        $mapsUrl ? ['label' => 'Petunjuk arah', 'icon' => 'map-pin', 'href' => $mapsUrl] : null,
    ]);
@endphp

@section('content')
    <header class="sf-hero no-print">
        <x-customer.hero-ornament class="left-1/2 -top-10 size-[420px] -translate-x-1/2 md:-top-[120px] md:size-[640px]" />
        <div class="mx-auto max-w-[1180px] px-4 pt-3 md:px-8 md:pt-[18px] lg:px-14 lg:pt-5">
            <div class="flex items-center gap-3">
                <a href="{{ $invoiceBackUrl }}" aria-label="Kembali" title="{{ $isSuccess ? 'Kembali ke halaman bisnis' : 'Kembali ke kelola reservasi' }}" class="sf-btn-on-primary sf-btn-icon">
                    <x-customer.icon name="chevron-left" :stroke="2" />
                </a>
                @if ($invoice['logo'])
                    <img src="{{ $invoice['logo'] }}" alt="" class="size-10 shrink-0 rounded-sf-card-small bg-sf-surface object-cover">
                @else
                    <span aria-hidden="true" class="grid size-10 shrink-0 place-items-center rounded-sf-card-small bg-sf-surface text-[15px] font-bold text-sf-primary-text">{{ $businessInitials ?? 'BQ' }}</span>
                @endif
                <span class="min-w-0 grow truncate text-[15px] font-bold">{{ $tenant->namabisnis }}</span>
                @unless ($isSuccess)
                    <button type="button" class="sf-btn-on-primary px-3.5 text-sm" onclick="window.print()">
                        <x-customer.icon name="printer" :size="18" />Cetak
                    </button>
                @endunless
            </div>
            @if ($isSuccess)
                <div class="pt-[18px] text-center md:pt-3">
                    <span class="mx-auto grid size-[72px] place-items-center rounded-full bg-sf-surface text-sf-success shadow-[0_0_0_10px_var(--sf-on-primary-faint)]">
                        <x-customer.icon name="check" :size="34" :stroke="2.6" />
                    </span>
                    <h1 class="mt-[22px] text-[26px] font-bold leading-[1.15] tracking-[-0.6px] md:text-[38px]">Pembayaran berhasil</h1>
                    <p class="mx-auto mt-2 max-w-[480px] text-[15px] leading-[23px] text-sf-hero-soft">
                        Reservasi Anda sudah terkonfirmasi.@if ($invoice['email']) Bukti dan tautan kelola dikirim ke {{ $invoice['email'] }}.@endif
                    </p>
                </div>
            @else
                <h1 class="mt-5 text-[26px] font-bold tracking-[-0.4px] md:text-[38px]">Invoice pembayaran</h1>
                <p class="sf-num mt-1 text-sm text-sf-hero-soft">No. {{ $invoice['number'] }} · {{ $invoice['date'] }}</p>
            @endif
        </div>
        <div @class(['h-14' => ! $isSuccess, 'h-16 md:h-[88px]' => $isSuccess])></div>
    </header>

    <div class="mx-auto max-w-[1180px] px-4 pb-10 md:px-8 lg:grid lg:grid-cols-[420px_minmax(0,1fr)] lg:items-start lg:gap-6 lg:px-14">
        <div class="no-print relative -mt-10 grid gap-3 md:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)] md:items-start lg:grid-cols-1">
            <x-customer.ticket :month="$month" :day="$day" :weekday="$weekday" :service="$sessions->first()['service']"
                :time="$ticketTime" :location="$tenant->alamat" :code="$sessions->first()['code']" />

            <div class="grid gap-3">
                <div class="grid grid-cols-2 gap-2 md:grid-cols-1 lg:grid-cols-2">
                    @foreach ($actions as $action)
                        <a href="{{ $action['href'] }}" target="_blank" rel="noopener noreferrer"
                            class="sf-card-small flex min-h-14 items-center gap-2.5 px-3 py-2.5 text-sm font-semibold leading-[18px]">
                            <span class="grid size-9 shrink-0 place-items-center rounded-sf-control bg-sf-tint text-sf-primary-text"><x-customer.icon :name="$action['icon']" :size="18" /></span>
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                    <button type="button" onclick="window.print()"
                        class="sf-card-small flex min-h-14 items-center gap-2.5 px-3 py-2.5 text-left text-sm font-semibold leading-[18px]">
                        <span class="grid size-9 shrink-0 place-items-center rounded-sf-control bg-sf-tint text-sf-primary-text"><x-customer.icon name="download" :size="18" /></span>
                        <span class="md:hidden">Unduh invoice</span><span class="hidden md:inline">Cetak invoice</span>
                    </button>
                </div>

                @if ($isSuccess)
                    <div class="flex min-h-16 items-center gap-3 rounded-sf-card-small bg-sf-tint px-[18px] py-3">
                        <span class="min-w-0 grow">
                            <span class="block text-[15px] font-bold">Kelola reservasi</span>
                            @if ($manageUrl ?? null)
                                <span class="block text-[13px] text-sf-muted">Lihat status, ubah jadwal, atau minta bantuan.</span>
                            @else
                                <span class="block text-[13px] text-sf-muted">Ubah jadwal atau hubungi bisnis lewat tautan di email konfirmasi.</span>
                            @endif
                        </span>
                        @if ($manageUrl ?? null)
                            <a href="{{ $manageUrl }}" class="sf-btn sf-btn-sm shrink-0">Kelola</a>
                        @endif
                        @if ($merchantWaUrl ?? null)
                            <a href="{{ $merchantWaUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Hubungi bisnis lewat WhatsApp" class="sf-btn sf-btn-icon shrink-0">
                                <x-customer.icon name="whatsapp" :size="18" :stroke="2" />
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <section aria-labelledby="judul-invoice" class="sf-card relative mt-4 p-[18px] md:p-[26px] lg:-mt-10 print:mt-0 print:shadow-none">
            <div class="mb-[18px] flex flex-wrap items-start justify-between gap-2.5">
                <div>
                    <h2 id="judul-invoice" class="text-xl font-bold">{{ $isSuccess ? 'Invoice pembayaran' : 'Rincian pembayaran' }}</h2>
                    <p class="sf-num mt-1 text-[13px] text-sf-muted">Bukti pembayaran reservasi · No. {{ $invoice['number'] }} · {{ $invoice['date'] }}</p>
                </div>
                <span class="sf-pill" data-tone="{{ $isPaid ? 'success' : 'neutral' }}">{{ $invoice['status'] }}</span>
            </div>
            <div class="grid gap-2.5 md:grid-cols-2">
                <div class="rounded-sf-card-small bg-sf-page px-4 py-3.5">
                    <p class="text-xs font-bold text-sf-muted">Pemesan</p>
                    <p class="mt-1.5 text-[15px] font-bold">{{ $invoice['name'] }}</p>
                    <p class="mt-0.5 break-words text-[13px] text-sf-muted">{{ collect([$invoice['email'], $invoice['phone']])->filter()->implode(' · ') }}</p>
                </div>
                <div class="rounded-sf-card-small bg-sf-page px-4 py-3.5">
                    <p class="text-xs font-bold text-sf-muted">Bisnis</p>
                    <p class="mt-1.5 text-[15px] font-bold">{{ $tenant->namabisnis }}</p>
                    @if ($tenant->alamat)
                        <p class="mt-0.5 text-[13px] text-sf-muted">{{ $tenant->alamat }}</p>
                    @endif
                </div>
            </div>

            <p class="mt-[18px] text-xs font-bold text-sf-muted">Jadwal dan rincian · {{ $invoice['sessionDate'] }}</p>
            <ul>
                @foreach ($invoice['sessions'] as $session)
                    <li class="flex justify-between gap-4 border-b border-sf-disabled py-3 print:break-inside-avoid">
                        <div class="min-w-0">
                            <p class="text-[15px] font-semibold">{{ $session['service'] }}</p>
                            <p class="sf-num mt-0.5 text-[13px] text-sf-muted">{{ $session['time'] }} WIB @if ($session['minutes'] !== null) · {{ $session['minutes'] }} menit @endif · {{ $session['code'] }}</p>
                        </div>
                        <span class="sf-num shrink-0 text-[15px] font-semibold">{{ $session['amount'] }}</span>
                    </li>
                @endforeach
                @foreach ($invoice['extras'] as $item)
                    <li class="flex justify-between gap-4 border-b border-sf-disabled py-3">
                        <span class="text-[15px]">{{ $item['label'] }}</span>
                        <span @class(['sf-num shrink-0 text-[15px] font-semibold', 'text-sf-success' => str_starts_with($item['amount'], '-') || str_starts_with($item['amount'], '−')])>{{ $item['amount'] }}</span>
                    </li>
                @endforeach
            </ul>
            @if ($invoice['note'])
                <p class="py-3 text-[13px] text-sf-muted">{{ $invoice['note'] }}</p>
            @endif
            <div class="mt-3.5 flex items-baseline justify-between gap-3">
                <span class="text-[15px] font-bold">Total pembayaran</span>
                <span class="sf-num text-[26px] font-bold">{{ $invoice['amount'] }}</span>
            </div>
            <p class="mt-0.5 text-right text-[13px] text-sf-muted">{{ $invoice['method'] }}</p>
        </section>
    </div>
@endsection
