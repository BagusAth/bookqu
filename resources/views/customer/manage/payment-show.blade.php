<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Kelola reservasi Anda di {{ $tenant->namabisnis }} — lihat detail, unduh invoice, dan informasi jadwal reservasi." />
    <title>{{ $payment->order_id }} — Kelola Reservasi | {{ $tenant->namabisnis }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @php
        $themeColor = $tenant->theme_color ?: '#4F46E5';
        $fontFamily = $tenant->font_family ?: 'Plus Jakarta Sans';
        $buttonRadius = match($tenant->button_style) {
            'rounded-md' => '0.375rem',
            'rounded-full', 'pill' => '9999px',
            default => '0.75rem',
        };
    @endphp
    <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $fontFamily) }}:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ asset('css/booking-manage.css') }}" />
    <style>
        :root {
            --bq-primary: {{ $themeColor }};
            --bq-primary-hover: {{ $themeColor }};
        }
        body {
            font-family: '{{ $fontFamily }}', system-ui, -apple-system, sans-serif !important;
        }
        .btn-theme {
            background-color: {{ $themeColor }} !important;
            border-radius: {{ $buttonRadius }} !important;
        }
        .text-theme {
            color: {{ $themeColor }} !important;
        }
        .border-theme {
            border-color: {{ $themeColor }} !important;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased min-h-screen flex flex-col" x-data="{ showCancelModal: false }">

    @php
        $firstBooking = $bookings->first();
        $lastBooking = $bookings->last();
        $layanan = $firstBooking?->layanan;
        $bookingDate = $firstBooking?->tanggalbooking ? \Carbon\Carbon::parse($firstBooking->tanggalbooking)->format('Y-m-d') : now()->format('Y-m-d');
        $durasiMenit = (int) ($layanan?->durasi ?? 60);

        // Compute contiguous event range for Google Calendar
        $firstStart = \Carbon\Carbon::parse($bookingDate . ' ' . $firstBooking?->jam);
        $lastStart = \Carbon\Carbon::parse($bookingDate . ' ' . $lastBooking?->jam);
        $overallEnd = (clone $lastStart)->addMinutes($durasiMenit);

        $scheduleListText = $bookings->map(function($b) use ($durasiMenit, $bookingDate) {
            $s = \Carbon\Carbon::parse($bookingDate . ' ' . $b->jam);
            $e = (clone $s)->addMinutes($durasiMenit);
            return $s->format('H:i') . ' – ' . $e->format('H:i');
        })->implode(', ');

        $eventTitle = 'Reservasi ' . ($layanan?->namalayanan ?? 'Layanan') . ' - ' . $tenant->namabisnis;
        $eventLocation = ($tenant->alamat ? $tenant->alamat . ', ' : '') . $tenant->namabisnis;

        // Google Calendar Description (RULE-006 & Section 39: NO manageUrl, NO token)
        $eventDetails = "Reservasi di {$tenant->namabisnis}\n\n"
            . "Layanan:\n" . ($layanan?->namalayanan ?? 'Layanan') . "\n\n"
            . "Tanggal:\n" . \Carbon\Carbon::parse($bookingDate)->translatedFormat('l, d F Y') . "\n\n"
            . "Waktu:\n" . $scheduleListText . " WIB\n\n"
            . "Order ID:\n" . $payment->order_id;

        $gCalDates = $firstStart->format('Ymd\THis') . '/' . $overallEnd->format('Ymd\THis');
        $gCalUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
            . '&text=' . urlencode($eventTitle)
            . '&dates=' . $gCalDates
            . '&details=' . urlencode($eventDetails)
            . '&location=' . urlencode($eventLocation);

        // WhatsApp Share Text (RULE-006 & Section 39: NO manageUrl, NO token)
        $waText = "Reservasi saya di {$tenant->namabisnis}\n\n"
            . "Layanan: " . ($layanan?->namalayanan ?? 'Layanan') . "\n"
            . "Tanggal: " . \Carbon\Carbon::parse($bookingDate)->translatedFormat('l, d F Y') . "\n"
            . "Waktu: " . $scheduleListText . " WIB\n"
            . "Order ID: " . $payment->order_id . "\n\n"
            . "Reservasi telah dikonfirmasi.";
        $waShareUrl = 'https://api.whatsapp.com/send?text=' . urlencode($waText);

        // Merchant WhatsApp URL
        $cleanMerchantPhone = preg_replace('/[^0-9]/', '', $tenant->nomorhp ?? '');
        $merchantWaUrl = !empty($cleanMerchantPhone)
            ? 'https://wa.me/' . $cleanMerchantPhone . '?text=' . urlencode("Halo {$tenant->namabisnis}, saya ingin bertanya mengenai reservasi saya ({$payment->order_id}).")
            : null;

        $invoiceUrl = route('booking.manage.payment.invoice', ['order_id' => $payment->order_id]);

        $canReschedule = !$isMultiSlot && $firstBooking && $firstBooking->canBeRescheduled();
        $canCancel = !$isMultiSlot && $firstBooking && $firstBooking->canBeCancelled();
        $cancelHours = $tenant->cancel_before_hours ?? 24;
        $rescheduleHours = $tenant->reschedule_before_hours ?? 24;
    @endphp

    {{-- Header --}}
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex w-full max-w-4xl items-center justify-between gap-3 px-4 sm:px-6 py-3.5">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ $tenant->slug ? \App\Support\CustomerBookingRoutes::url('customer.booking.program', $tenant->slug) : '/' }}" class="flex min-w-0 items-center gap-2.5 group">
                    @if($tenant->logo_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($tenant->logo_path) }}" alt="{{ $tenant->namabisnis }}" class="h-9 w-9 rounded-xl object-cover border border-[#E2E8F0] shadow-xs group-hover:scale-105 transition-transform" />
                    @else
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl btn-theme text-white font-bold text-sm shadow-xs group-hover:scale-105 transition-transform">
                            {{ strtoupper(substr($tenant->namabisnis, 0, 2)) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <span class="block truncate text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ $tenant->namabisnis }}</span>
                        <span class="block truncate text-[11px] font-medium text-slate-500">Portal Kelola Reservasi</span>
                    </div>
                </a>
            </div>
            <div class="flex shrink-0 items-center gap-2 sm:gap-3 whitespace-nowrap">
                @if($merchantWaUrl)
                    <a
                        href="{{ $merchantWaUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 transition-all shadow-xs"
                    >
                        <svg class="h-3.5 w-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        <span class="hidden sm:inline">Hubungi</span> Admin
                    </a>
                @endif

                {{-- Section 16: Conditional Invoice Visibility (ONLY visible when payment status is sukses) --}}
                @if($payment->status === 'sukses')
                    <a
                        href="{{ $invoiceUrl }}"
                        class="inline-flex items-center gap-1.5 rounded-xl btn-theme px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs transition-all"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="hidden sm:inline">Lihat</span> Invoice
                    </a>
                @endif
            </div>
        </div>
    </header>

    {{-- Main Container --}}
    <main class="flex-1 w-full max-w-4xl mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">

        {{-- Flash message if any --}}
        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs sm:text-sm font-semibold text-emerald-800 shadow-2xs">
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs sm:text-sm font-semibold text-rose-800 shadow-2xs">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Status Hero Banner --}}
        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-slate-100">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1.5">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Order ID</span>
                        <span class="font-mono text-xs font-extrabold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md">{{ $payment->order_id }}</span>
                        @if($isMultiSlot)
                            <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-xs font-bold text-purple-700 border border-purple-200">
                                Multi-Slot ({{ $bookings->count() }} Sesi)
                            </span>
                        @endif
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                        {{ $layanan->namalayanan ?? 'Layanan Reservasi' }}
                    </h1>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if($payment->status === 'sukses')
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 border border-emerald-200 px-3.5 py-1.5 text-xs font-bold text-emerald-700 shadow-xs">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Reservasi Dikonfirmasi
                        </span>
                    @elseif($payment->isExpired())
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 border border-slate-300 px-3.5 py-1.5 text-xs font-bold text-slate-700 shadow-xs">
                            <span class="h-2 w-2 rounded-full bg-slate-500"></span>
                            Waktu Pembayaran Habis
                        </span>
                    @elseif($payment->status === 'pending')
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-amber-50 border border-amber-200 px-3.5 py-1.5 text-xs font-bold text-amber-700 shadow-xs">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            Menunggu Pembayaran
                        </span>
                        {{-- The provider's hosted link lets the payer resume (BQ-064 T4-09) --}}
                        @if(! empty($payment->payment_url))
                            <a href="{{ $payment->payment_url }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-1.5 rounded-xl btn-theme px-3.5 py-1.5 text-xs font-bold text-white shadow-xs">
                                Lanjutkan Pembayaran
                            </a>
                        @endif
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-red-50 border border-red-200 px-3.5 py-1.5 text-xs font-bold text-red-700 shadow-xs">
                            <span class="h-2 w-2 rounded-full bg-red-500"></span>
                            Pembayaran Tidak Berhasil
                        </span>
                    @endif
                </div>
            </div>

            {{-- Multi-Slot Notice Banner (Section 19 & 52 Standard Wording) --}}
            @if($isMultiSlot)
                <div class="mt-6 rounded-2xl border border-indigo-100 bg-indigo-50/60 p-4 sm:p-5 flex items-start gap-3.5">
                    <div class="rounded-xl btn-theme p-2 text-white shrink-0 mt-0.5">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Reservasi Multi-Slot</h2>
                        <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                            Reservasi ini terdiri dari beberapa sesi yang berurutan. Perubahan atau pembatalan per sesi tidak tersedia.
                        </p>
                    </div>
                </div>
            @endif

            {{-- Booking Details Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                {{-- Jadwal Anda (Section 52) --}}
                <div class="space-y-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Jadwal Anda</h2>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 space-y-3">
                        <div class="flex items-center gap-2.5 text-sm font-semibold text-slate-800">
                            <svg class="h-4 w-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ \Carbon\Carbon::parse($bookingDate)->translatedFormat('l, d F Y') }}</span>
                        </div>

                        <div class="border-t border-slate-200/60 pt-3 space-y-2">
                            <span class="text-xs font-semibold text-slate-500 block mb-1">Daftar Sesi Terjadwal:</span>
                            @foreach($bookings as $idx => $b)
                                @php
                                    $slotStart = \Carbon\Carbon::parse($bookingDate . ' ' . $b->jam);
                                    $slotEnd = (clone $slotStart)->addMinutes($durasiMenit);
                                @endphp
                                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-0.5 text-xs py-1.5 px-2.5 rounded-lg bg-white border border-slate-200/70">
                                    <div class="flex items-center gap-2 whitespace-nowrap">
                                        <span class="font-bold text-slate-700">Sesi #{{ $idx + 1 }}:</span>
                                        <span class="font-semibold text-indigo-700 font-mono">{{ $slotStart->format('H:i') }} – {{ $slotEnd->format('H:i') }} WIB</span>
                                    </div>
                                    <span class="font-bold text-slate-800 whitespace-nowrap">{{ $b->priceLabel }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Detail Pemesan & Pembayaran --}}
                <div class="space-y-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Ringkasan Pembayaran &amp; Kontak</h2>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 space-y-3 text-xs">
                        <div class="flex justify-between gap-3 py-1 border-b border-slate-200/50">
                            <span class="shrink-0 text-slate-500">Nama Pelanggan</span>
                            <span class="min-w-0 break-words text-right font-bold text-slate-800">{{ $firstBooking->namapelanggan }}</span>
                        </div>
                        <div class="flex justify-between gap-3 py-1 border-b border-slate-200/50">
                            <span class="shrink-0 text-slate-500">Email</span>
                            <span class="min-w-0 break-all text-right font-bold text-slate-800 font-mono">{{ $firstBooking->email }}</span>
                        </div>
                        <div class="flex justify-between gap-3 py-1 border-b border-slate-200/50">
                            <span class="shrink-0 text-slate-500">No. WhatsApp</span>
                            <span class="min-w-0 break-all text-right font-bold text-slate-800 font-mono">{{ $firstBooking->nomorhp }}</span>
                        </div>
                        <div class="flex justify-between gap-3 py-1 border-b border-slate-200/50">
                            <span class="shrink-0 text-slate-500">Metode Pembayaran</span>
                            <span class="min-w-0 break-words text-right font-bold text-slate-800 uppercase">{{ $payment->metode ?: 'Midtrans' }}</span>
                        </div>
                        <div class="flex flex-wrap justify-between items-center gap-x-3 pt-2">
                            <span class="text-sm font-bold text-slate-900">Total Transaksi</span>
                            <span class="text-base font-black text-indigo-700 whitespace-nowrap">Rp {{ number_format((float) $payment->jumlah, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Single-Slot Eligible Management Actions (Section 18) --}}
            @if(!$isMultiSlot && $payment->status === 'sukses' && $firstBooking)
                <div class="mt-6 pt-6 border-t border-slate-100">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Tindakan Reservasi</h3>
                    <div class="flex flex-wrap items-center gap-3">
                        @if($canReschedule)
                            <a
                                href="{{ route('booking.manage.reschedule.show', ['booking_code' => $firstBooking->booking_code]) }}"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl btn-theme px-4 py-2.5 text-xs font-bold text-white shadow-xs transition-all cursor-pointer"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Ubah Jadwal</span>
                            </a>
                        @endif

                        @if($canCancel)
                            <button
                                type="button"
                                @click="showCancelModal = true"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-xs font-bold text-rose-600 shadow-xs hover:bg-rose-50 hover:border-rose-300 transition-all cursor-pointer"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                <span>Batalkan Reservasi</span>
                            </button>
                        @endif
                    </div>
                    @if(!$canReschedule && !$canCancel)
                        <p class="text-xs text-slate-500 mt-2">
                            Batas waktu perubahan jadwal atau pembatalan mandiri telah berakhir (minimal {{ $cancelHours }} jam sebelum sesi).
                        </p>
                    @endif
                </div>
            @endif

            {{-- Quick Utilities --}}
            <div class="mt-8 pt-6 border-t border-slate-100 flex flex-wrap items-center gap-3">
                <a
                    href="{{ $gCalUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 transition-all cursor-pointer"
                >
                    <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Simpan ke Google Calendar
                </a>

                <a
                    href="{{ $waShareUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 transition-all cursor-pointer"
                >
                    <svg class="h-4 w-4 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    Bagikan via WhatsApp
                </a>

                {{-- Section 16: Conditional Invoice Visibility --}}
                @if($payment->status === 'sukses')
                    <a
                        href="{{ $invoiceUrl }}"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs hover:border-slate-300 hover:bg-slate-50 transition-all cursor-pointer"
                    >
                        <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Cetak / Unduh Invoice
                    </a>
                @endif
            </div>
        </div>
    </main>

    {{-- Modal Konfirmasi Batalkan Reservasi (Single-Slot) --}}
    @if(!$isMultiSlot && $firstBooking && $canCancel)
        <div
            x-show="showCancelModal"
            x-cloak
            class="fixed inset-0 z-50 flex justify-center overflow-y-auto p-4"
            role="dialog"
            aria-modal="true"
        >
            <div
                x-show="showCancelModal"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="showCancelModal = false"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
            ></div>

            <div
                x-show="showCancelModal"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="my-auto relative w-full max-w-md overflow-hidden rounded-2xl bg-white p-6 shadow-2xl transition-all z-10"
            >
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-[#0F172A]">
                            Yakin ingin membatalkan reservasi ini?
                        </h3>
                        <p class="mt-1 text-xs text-[#64748B] leading-relaxed">
                            Slot jadwal akan dilepaskan dan reservasi Anda akan dibatalkan. Pembatalan tersedia hingga {{ $cancelHours }} jam sebelum sesi.
                        </p>

                        <div class="mt-3 rounded-xl bg-[#F8FAFC] p-3 border border-[#E2E8F0] text-xs text-[#475569] space-y-1">
                            <div class="flex justify-between">
                                <span class="text-[#64748B]">Booking Code:</span>
                                <span class="font-mono font-medium text-[#0F172A]">{{ $firstBooking->booking_code }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#64748B]">Layanan:</span>
                                <span class="font-medium text-[#0F172A]">{{ $layanan->namalayanan ?? 'Layanan' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
                    <button
                        type="button"
                        @click="showCancelModal = false"
                        class="inline-flex justify-center rounded-xl border border-[#CBD5E1] bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#334155] hover:bg-[#F8FAFC] transition-colors cursor-pointer"
                    >
                        Kembali
                    </button>
                    <form method="POST" action="{{ route('booking.manage.cancel', ['booking_code' => $firstBooking->booking_code]) }}" class="inline">
                        @csrf
                        <button
                            type="submit"
                            class="w-full inline-flex justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-rose-700 transition-colors cursor-pointer"
                        >
                            Ya, Batalkan Reservasi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Footer --}}
    <footer class="mt-auto border-t border-slate-200/80 bg-white px-4 py-6 text-center text-xs text-slate-500">
        <p>&copy; {{ date('Y') }} {{ $tenant->namabisnis }} — Didukung oleh BookQu Platform.</p>
    </footer>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.15.12/dist/cdn.min.js"></script>
</body>
</html>
