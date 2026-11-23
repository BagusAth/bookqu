@extends('customer.layouts.booking-shell')

@section('title', 'Kelola reservasi')
@section('immersive', true)

@php
    // Display state of the schedule card (design A §7.6); the data and permissions come from the server.
    $state = match (true) {
        $booking->status === 'cancelled' || $booking->status === 'no_show' => 'cancelled',
        $booking->status === 'completed' => 'completed',
        $booking->status === 'pending' || ($isGroupPage && $paymentLabel && $paymentLabel !== 'Lunas') => 'pending',
        default => 'confirmed',
    };
    $deadlinePassed = $state === 'confirmed' && $rescheduleDeadline && ! $rescheduleUrl;
    [$weekday, $dateRest] = array_pad(explode(', ', $sessionDate, 2), 2, '');
    [$day, $month] = array_pad(explode(' ', $dateRest), 2, '');
    $firstTime = (string) ($sessions[0]['time'] ?? '');
    $lastTime = (string) (end($sessions)['time'] ?? '');
    $timeLabel = \Illuminate\Support\Str::before($firstTime, ' – ').' – '.\Illuminate\Support\Str::afterLast($lastTime, ' – ');
    $pillTone = match ($statusTone) {
        'success' => 'success',
        'danger' => 'danger',
        default => 'warning',
    };
@endphp

@section('content')
    @include('customer.partials.payment.brand-header', ['title' => 'Kelola reservasi', 'backUrl' => $storefront, 'backLabel' => 'Kembali ke layanan'])

    <div class="mx-auto max-w-[1120px] px-4 pb-6 md:px-8 lg:px-14">
        @if ($errors->any())
            <x-customer.status-note tone="danger" role="alert" class="relative -mt-6 mb-4">{{ $errors->first() }}</x-customer.status-note>
        @endif

        <div class="sf-manage-grid relative -mt-10">
            <div class="sf-manage-col">
            {{-- Schedule card --}}
            <section aria-labelledby="judul-jadwal" class="sf-card-raised p-[18px] md:p-6 [grid-area:jadwal]">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="sf-pill" data-tone="{{ $pillTone }}">{{ $statusLabel }}</span>
                    @if ($reference)
                        <span class="sf-num text-[13px] text-sf-muted">{{ $referenceLabel }} {{ $reference }}</span>
                    @endif
                </div>
                <div class="mt-4 flex items-center gap-4">
                    <div class="w-[76px] shrink-0 overflow-hidden rounded-sf-card-small bg-sf-tint text-center">
                        <p class="bg-sf-primary py-1 text-xs font-bold text-sf-on-primary">{{ $month }}</p>
                        <p @class(['sf-num text-[30px] font-bold leading-[38px] text-sf-primary-text', 'line-through' => $state === 'cancelled'])>{{ $day }}</p>
                        <p class="pb-1.5 text-xs font-semibold text-sf-muted">{{ $weekday }}</p>
                    </div>
                    <div class="min-w-0">
                        <h2 id="judul-jadwal" @class(['text-[17px] font-bold leading-[23px]', 'line-through decoration-sf-muted' => $state === 'cancelled'])>{{ $sessions[0]['service'] ?? 'Reservasi' }}</h2>
                        <p @class(['sf-num mt-1 text-[15px] font-bold text-sf-primary-text', 'line-through' => $state === 'cancelled'])>{{ $timeLabel }} WIB</p>
                        <p class="mt-0.5 text-[13px] text-sf-muted">{{ $sessionDate }} · {{ count($sessions) }} sesi</p>
                    </div>
                </div>

                @if (count($sessions) > 1)
                    <ul class="mt-4 divide-y divide-sf-divider rounded-sf-card-small bg-sf-page px-4">
                        @foreach ($sessions as $session)
                            <li class="flex flex-wrap justify-between gap-2 py-2.5 text-sm">
                                <span class="font-semibold">{{ $session['service'] }}</span>
                                <span class="sf-num">{{ $session['time'] }} WIB @if ($session['minutes'] !== null) · {{ $session['minutes'] }} menit @endif</span>
                            </li>
                        @endforeach
                    </ul>
                @elseif (($sessions[0]['minutes'] ?? null) !== null)
                    <p class="sf-num mt-2 text-[13px] text-sf-muted">{{ $sessions[0]['time'] }} WIB · {{ $sessions[0]['minutes'] }} menit</p>
                @endif

                {{-- Actions by state --}}
                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($continuePaymentUrl ?? null)
                        <a href="{{ $continuePaymentUrl }}" class="sf-btn">Lanjutkan pembayaran</a>
                    @endif
                    @if ($rescheduleUrl)
                        <a href="{{ $rescheduleUrl }}" class="sf-btn">Ubah jadwal</a>
                    @elseif (($deadlinePassed || $isMultiSlot) && $merchantWaUrl)
                        <a href="{{ $merchantWaUrl }}" target="_blank" rel="noopener noreferrer" class="sf-btn">Hubungi bisnis</a>
                    @endif
                    @if (in_array($state, ['completed', 'cancelled'], true))
                        <a href="{{ $storefront }}" class="sf-btn">Pesan lagi</a>
                    @endif
                    @if ($state !== 'cancelled')
                        @if ($calendarUrl)
                            <a href="{{ $calendarUrl }}" target="_blank" rel="noopener noreferrer" class="sf-btn-secondary">Simpan ke Google Calendar</a>
                        @endif
                        @if ($shareUrl)
                            <a href="{{ $shareUrl }}" target="_blank" rel="noopener noreferrer" class="sf-btn-secondary">Bagikan</a>
                        @endif
                    @endif
                    @if ($invoiceUrl)
                        <a href="{{ $invoiceUrl }}" class="sf-btn-secondary">Lihat invoice</a>
                    @endif
                    @if ($reference && ! $isGroupPage)
                        <span x-data="{ copied: false }" class="contents">
                            <button type="button" class="sf-btn-secondary" x-on:click="
                                if (navigator.clipboard) {
                                    try { await navigator.clipboard.writeText(@js($reference)); copied = true; } catch { $refs.code.focus(); $refs.code.select(); }
                                } else { $refs.code.focus(); $refs.code.select(); }
                            " x-text="copied ? 'Tersalin' : 'Salin kode'">Salin kode</button>
                            <input x-ref="code" aria-label="Kode reservasi untuk disalin" readonly value="{{ $reference }}" class="sr-only">
                        </span>
                    @endif
                </div>

                {{-- Note by state --}}
                <div class="mt-4">
                    @if ($isMultiSlot)
                        <x-customer.status-note tone="info">Reservasi ini terdiri dari beberapa sesi yang berurutan. Perubahan atau pembatalan per sesi tidak tersedia. Hubungi bisnis bila perlu bantuan.</x-customer.status-note>
                    @elseif ($state === 'pending')
                        <x-customer.status-note tone="warning" title="Pembayaran belum selesai">
                            @if ($continuePaymentUrl ?? null)
                                Selesaikan pembayaran sebelum batas waktunya agar jadwal tidak dilepas.
                            @else
                                Selesaikan pembayaran lewat halaman pembayaran sebelum batas waktunya, atau hubungi bisnis.
                            @endif
                        </x-customer.status-note>
                    @elseif ($state === 'cancelled')
                        <x-customer.status-note tone="danger" title="Reservasi ini dibatalkan">Jadwal sudah dilepas. Bantuan pengembalian dana ada di bagian Bantuan.</x-customer.status-note>
                    @elseif ($deadlinePassed)
                        <x-customer.status-note tone="warning" title="Batas ubah jadwal sudah lewat">Batasnya <span class="sf-num">{{ $rescheduleDeadline }} WIB</span>. Hubungi bisnis untuk bantuan.</x-customer.status-note>
                    @elseif ($rescheduleDeadline)
                        <x-customer.status-note tone="brand">Batas ubah jadwal <span class="sf-num">{{ $rescheduleDeadline }} WIB</span></x-customer.status-note>
                    @endif
                </div>
            </section>

            @if ($canReview)
                <div class="[grid-area:ulasan]">
                    @include('customer.partials.manage.review-section')
                </div>
            @endif

            <div class="[grid-area:riwayat]">
                @include('customer.partials.manage.timeline')
            </div>
            </div>

            <div class="sf-manage-col">
            @if ($totalLabel)
                <section aria-labelledby="judul-bayar" class="sf-card p-[18px] [grid-area:bayar]">
                    <h2 id="judul-bayar" class="text-[15px] font-bold">Pembayaran</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        @if ($paymentLabel)
                            <div class="flex justify-between gap-3"><dt class="text-sf-muted">Status</dt><dd class="font-semibold">{{ $paymentLabel }}</dd></div>
                        @endif
                        @if ($methodLabel)
                            <div class="flex justify-between gap-3"><dt class="text-sf-muted">Metode</dt><dd class="text-right font-semibold">{{ $methodLabel }}</dd></div>
                        @endif
                        <div class="flex justify-between gap-3 border-t border-sf-divider pt-2"><dt class="text-sf-muted">Total</dt><dd class="sf-num font-bold">{{ $totalLabel }}</dd></div>
                        @if ($refundTotal)
                            <div class="flex justify-between gap-3"><dt class="text-sf-muted">Pengembalian dana</dt><dd class="sf-num font-semibold text-sf-success">{{ $refundTotal }}</dd></div>
                        @endif
                    </dl>
                </section>
            @endif

            <section aria-labelledby="judul-pemesan" class="sf-card p-[18px] [grid-area:pemesan]">
                <h2 id="judul-pemesan" class="text-[15px] font-bold">Data pemesan</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    @foreach (array_filter(['Nama' => $booking->namapelanggan, 'Email' => $booking->email, 'Nomor WhatsApp' => $booking->nomorhp]) as $label => $value)
                        <div class="flex justify-between gap-3"><dt class="shrink-0 text-sf-muted">{{ $label }}</dt><dd class="min-w-0 break-words text-right font-semibold">{{ $value }}</dd></div>
                    @endforeach
                </dl>
            </section>

            @if ($tenant->alamat || $merchantWaUrl)
                <section aria-labelledby="judul-lokasi" class="sf-card p-[18px] [grid-area:lokasi]">
                    <h2 id="judul-lokasi" class="text-[15px] font-bold">Lokasi dan kontak</h2>
                    @if ($tenant->alamat)
                        <p class="mt-2 text-sm">{{ $tenant->alamat }}</p>
                    @endif
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if ($mapsUrl)
                            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="sf-btn-secondary sf-btn-sm">Petunjuk arah</a>
                        @endif
                        @if ($merchantWaUrl)
                            <a href="{{ $merchantWaUrl }}" target="_blank" rel="noopener noreferrer" class="sf-btn-secondary sf-btn-sm sf-num">WhatsApp {{ $tenant->nomorhp }}</a>
                        @endif
                    </div>
                </section>
            @endif

            <section aria-labelledby="refund-help-title" class="sf-card p-[18px] [grid-area:bantuan]">
                <h2 id="refund-help-title" class="text-[15px] font-bold">Bantuan</h2>
                <div class="mt-2">
                    @include('customer.partials.manage.refund-form')
                </div>
                <p class="mt-3 text-[13px] text-sf-muted">Buka reservasi ini lagi lewat tautan di email Anda.</p>
            </section>

            </div>
        </div>
    </div>
@endsection
