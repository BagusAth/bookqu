@extends('customer.layouts.booking-shell')

@section('title', 'Kelola reservasi')
@section('back_url', $storefront)
@section('back_label', 'Kembali ke layanan')

@section('content')
    <div class="mx-auto max-w-4xl space-y-4">
        <x-customer.page-header title="Kelola reservasi">
            <x-customer.status :label="$statusLabel" :tone="$statusTone" />
        </x-customer.page-header>
        @if ($errors->any())
            <x-ui.banner :title="$errors->first()" tone="danger" />
        @endif

        <x-ui.panel title="Jadwal reservasi" :description="$sessionDate">
            <ul class="divide-y divide-bq-border">
                @foreach ($sessions as $session)
                    <li class="flex flex-wrap justify-between gap-2 py-2 text-bq-body">
                        <span class="font-semibold">{{ $session['service'] }}</span>
                        <span class="bq-num">{{ $session['time'] }} WIB @if($session['minutes'] !== null) · {{ $session['minutes'] }} menit @endif</span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-2 text-bq-small text-bq-text-muted">{{ count($sessions) }} sesi</p>
            @if ($reference)
                <div x-data="{ copied: false }" class="mt-3 flex flex-wrap items-center gap-2 border-t border-bq-border pt-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-bq-small text-bq-text-muted">{{ $referenceLabel }}</p>
                        <p class="break-all font-semibold">{{ $reference }}</p>
                    </div>
                    @unless ($isGroupPage)
                        <button type="button" class="bq-btn bq-btn-secondary min-h-11" x-on:click="
                            if (navigator.clipboard) {
                                try { await navigator.clipboard.writeText(@js($reference)); copied = true; } catch { $refs.code.focus(); $refs.code.select(); }
                            } else { $refs.code.focus(); $refs.code.select(); }
                        " x-text="copied ? 'Tersalin' : 'Salin kode'">Salin kode</button>
                        <input x-ref="code" aria-label="Kode reservasi untuk disalin" readonly value="{{ $reference }}"
                            class="sr-only focus:not-sr-only focus:bq-input max-w-full">
                    @endunless
                </div>
            @endif
        </x-ui.panel>

        @if ($totalLabel)
            <x-customer.summary title="Ringkasan pembayaran" :items="array_filter(['Status' => $paymentLabel, 'Metode pembayaran' => $methodLabel])" :total="$totalLabel">
                @if($refundTotal)
                    <p class="mt-2 text-bq-body">Pengembalian dana <span class="bq-num">{{ $refundTotal }}</span></p>
                @endif
            </x-customer.summary>
        @endif

        <x-ui.panel title="Data pemesan">
            <x-ui.kv :items="array_filter(['Nama' => $booking->namapelanggan, 'Email' => $booking->email, 'Nomor WhatsApp' => $booking->nomorhp])" />
        </x-ui.panel>

        <x-ui.panel title="Tindakan">
            @if ($isMultiSlot)
                <p class="mb-3 text-bq-body text-bq-text-muted">Reservasi ini terdiri dari beberapa sesi yang berurutan. Perubahan atau pembatalan per sesi tidak tersedia.</p>
            @endif
            <div class="flex flex-wrap gap-2">
                @if ($rescheduleUrl)
                    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-bq-border bg-bq-surface p-4 sm:static sm:border-0 sm:p-0">
                        <x-ui.button :href="$rescheduleUrl" variant="primary" class="min-h-11 w-full sm:w-auto">Ubah jadwal</x-ui.button>
                    </div>
                @endif
                @if ($invoiceUrl)
                    <x-ui.button :href="$invoiceUrl" class="min-h-11">Lihat invoice</x-ui.button>
                @endif
                @if ($calendarUrl)
                    <x-ui.button :href="$calendarUrl" target="_blank" rel="noopener noreferrer" class="min-h-11">Simpan ke Google Calendar</x-ui.button>
                @endif
                @if ($shareUrl)
                    <x-ui.button :href="$shareUrl" target="_blank" rel="noopener noreferrer" class="min-h-11">Bagikan via WhatsApp</x-ui.button>
                @endif
            </div>
            @if ($rescheduleDeadline)
                <p class="mt-2 text-bq-small text-bq-text-muted">Batas waktu ubah jadwal <span class="bq-num">{{ $rescheduleDeadline }} WIB</span></p>
                @unless ($rescheduleUrl)
                    <p class="mt-1 text-bq-small text-bq-text-muted">Batas waktu sudah lewat. Hubungi bisnis untuk bantuan.</p>
                @endunless
            @endif
            <div class="mt-3">
                @include('customer.partials.manage.refund-form')
            </div>
            <p class="mt-3 text-bq-small text-bq-text-muted">Buka reservasi ini lagi lewat tautan di email Anda.</p>
        </x-ui.panel>

        @if ($canReview)
            @include('customer.partials.manage.review-section')
        @endif

        @include('customer.partials.manage.timeline')

        @if ($tenant->alamat || $merchantWaUrl)
            <x-ui.panel title="Lokasi dan kontak">
                @if ($tenant->alamat)
                    <p class="mb-3 text-bq-body">{{ $tenant->alamat }}</p>
                @endif
                <div class="flex flex-wrap items-center gap-2">
                    @if ($mapsUrl)
                        <x-ui.button :href="$mapsUrl" target="_blank" rel="noopener noreferrer" class="min-h-11">Petunjuk arah</x-ui.button>
                    @endif
                    @if ($merchantWaUrl)
                        <span class="text-bq-body text-bq-text-muted">WhatsApp {{ $tenant->nomorhp }}</span>
                    @endif
                </div>
            </x-ui.panel>
        @endif
    </div>
@endsection
