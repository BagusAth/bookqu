<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Kelola booking Anda di {{ $booking->tenant->namabisnis }} — lihat detail, unduh invoice, reschedule jadwal, atau batalkan reservasi." />
    <title>{{ $booking->booking_code }} — Kelola Booking | {{ $booking->tenant->namabisnis }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ asset('css/booking-manage.css') }}" />
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased min-h-screen flex flex-col" x-data="manageBooking()" x-init="init()">

    @php
        $bookingDate = $booking->tanggalbooking ? \Carbon\Carbon::parse($booking->tanggalbooking)->format('Y-m-d') : now()->format('Y-m-d');
        $startDateTime = \Carbon\Carbon::parse($bookingDate . ' ' . $booking->jam);
        $durasiMenit = (int) ($booking->layanan?->durasi ?? 60);
        $endDateTime = (clone $startDateTime)->addMinutes($durasiMenit);

        $eventTitle = 'Booking ' . ($booking->layanan?->namalayanan ?? 'Layanan') . ' - ' . ($booking->tenant?->namabisnis ?? '');
        $eventLocation = ($booking->tenant?->alamat ? $booking->tenant->alamat . ', ' : '') . ($booking->tenant?->namabisnis ?? '');
        $manageUrl = route('booking.manage', ['booking_code' => $booking->booking_code]) . ($token ? '?token=' . $token : '');
        $eventDetails = 'Reservasi resmi di ' . ($booking->tenant?->namabisnis ?? '') . "\nKode Booking: " . $booking->booking_code . "\nKelola Booking: " . $manageUrl;

        // Google Calendar URL
        $gCalDates = $startDateTime->format('Ymd\THis') . '/' . $endDateTime->format('Ymd\THis');
        $gCalUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
            . '&text=' . urlencode($eventTitle)
            . '&dates=' . $gCalDates
            . '&details=' . urlencode($eventDetails)
            . '&location=' . urlencode($eventLocation);

        // WhatsApp Share URL
        $waText = "Halo! Reservasi booking sesi *" . ($booking->layanan->namalayanan ?? 'Layanan') . "* saya di *" . $booking->tenant->namabisnis . "*\n"
            . "📅 Tanggal: " . \Carbon\Carbon::parse($booking->tanggalbooking)->translatedFormat('l, d F Y') . "\n"
            . "⏰ Jam: " . \Carbon\Carbon::parse($booking->jam)->format('H:i') . " WIB\n"
            . "🔖 Kode Booking: " . $booking->booking_code . "\n"
            . "Kelola Reservasi: " . $manageUrl;
        $waShareUrl = 'https://api.whatsapp.com/send?text=' . urlencode($waText);

        // Merchant WhatsApp URL
        $cleanMerchantPhone = preg_replace('/[^0-9]/', '', $booking->tenant->nomorhp ?? '');
        $merchantWaUrl = !empty($cleanMerchantPhone)
            ? 'https://wa.me/' . $cleanMerchantPhone . '?text=' . urlencode("Halo {$booking->tenant->namabisnis}, saya ingin bertanya mengenai booking saya ({$booking->booking_code}).")
            : null;
    @endphp

    {{-- ── Elevated Header ── --}}
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex w-full max-w-5xl items-center justify-between px-4 sm:px-6 py-3.5">
            <div class="flex items-center gap-3">
                <a href="{{ $booking->tenant->slug ? url('/' . $booking->tenant->slug) : '/' }}" class="flex items-center gap-2.5 group">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-white font-bold text-sm shadow-xs group-hover:scale-105 transition-transform">
                        {{ strtoupper(substr($booking->tenant->namabisnis, 0, 1)) }}
                    </div>
                    <div>
                        <span class="block text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ $booking->tenant->namabisnis }}</span>
                        <span class="block text-[11px] font-medium text-slate-500">Portal Kelola Reservasi</span>
                    </div>
                </a>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                @if($merchantWaUrl)
                    <a
                        href="{{ $merchantWaUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50/80 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition-all cursor-pointer"
                        title="Bantuan WhatsApp"
                    >
                        <svg class="h-4 w-4 text-emerald-600 fill-current" viewBox="0 0 24 24">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.969.54 1.761.817 2.796.817 3.182 0 5.768-2.587 5.768-5.766.001-3.181-2.585-5.766-5.768-5.766zm0 10.42c-.93 0-1.636-.26-2.527-.79l-.18-.108-1.58.414.421-1.54-.118-.188c-.604-.962-.976-1.745-.976-2.61 0-2.678 2.181-4.857 4.86-4.857 2.677 0 4.857 2.18 4.857 4.858 0 2.677-2.18 4.857-4.857 4.857z"/>
                        </svg>
                        <span class="hidden sm:inline">Bantuan Merchant</span>
                    </a>
                @endif
                <a
                    href="{{ $booking->tenant->slug ? url('/' . $booking->tenant->slug) : '/' }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-all cursor-pointer"
                >
                    <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Katalog</span>
                </a>
            </div>
        </div>
    </header>

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="mx-auto w-full max-w-5xl px-4 sm:px-6 pt-5">
            <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/95 p-4 text-xs sm:text-sm font-semibold text-emerald-800 shadow-2xs">
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="flex-1">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mx-auto w-full max-w-5xl px-4 sm:px-6 pt-5">
            <div class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50/95 p-4 text-xs sm:text-sm font-semibold text-rose-800 shadow-2xs">
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">{{ $errors->first() }}</div>
            </div>
        </div>
    @endif

    <main class="mx-auto w-full max-w-5xl px-4 sm:px-6 py-6 sm:py-8 space-y-6 flex-grow">

        {{-- ── DIGITAL RESERVATION PASS (HERO TICKET CARD) ── --}}
        <div class="overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-md relative">
            {{-- Top Header Ribbon --}}
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 px-6 py-5 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-indigo-300 text-xs font-semibold uppercase tracking-wider">
                        <span>Tiket Resmi Reservasi</span>
                        <span>•</span>
                        <span>{{ $booking->tenant->namabisnis }}</span>
                    </div>
                    <div class="flex items-center gap-3 mt-1.5">
                        <span class="font-mono text-xl sm:text-2xl font-black tracking-wider text-white select-all">{{ $booking->booking_code }}</span>
                        <button
                            type="button"
                            @click="copyCode('{{ $booking->booking_code }}')"
                            class="inline-flex items-center gap-1 rounded-lg bg-white/10 hover:bg-white/20 px-2.5 py-1 text-xs font-semibold text-white transition-all cursor-pointer"
                            title="Salin Kode Booking"
                        >
                            <svg x-show="!copied" class="h-3.5 w-3.5 text-indigo-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                            </svg>
                            <svg x-show="copied" x-cloak class="h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span x-text="copied ? 'Disalin!' : 'Salin'">Salin</span>
                        </button>
                    </div>
                </div>

                {{-- Status Badge --}}
                <div class="flex flex-col sm:items-end">
                    @php
                        $statusBadge = match($booking->status) {
                            'paid'      => ['bg' => 'bg-emerald-500/15 border-emerald-400/40 text-emerald-300', 'pulse' => 'bg-emerald-400', 'label' => 'Terkonfirmasi'],
                            'pending'   => ['bg' => 'bg-amber-500/15 border-amber-400/40 text-amber-300', 'pulse' => 'bg-amber-400', 'label' => 'Menunggu Bayar'],
                            'cancelled' => ['bg' => 'bg-rose-500/15 border-rose-400/40 text-rose-300', 'pulse' => 'bg-rose-400', 'label' => 'Dibatalkan'],
                            'completed' => ['bg' => 'bg-indigo-500/15 border-indigo-400/40 text-indigo-300', 'pulse' => 'bg-indigo-400', 'label' => 'Selesai'],
                            default     => ['bg' => 'bg-slate-500/15 border-slate-400/40 text-slate-300', 'pulse' => 'bg-slate-400', 'label' => $booking->status],
                        };
                    @endphp
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1 text-xs font-bold tracking-wide {{ $statusBadge['bg'] }}">
                        <span class="h-2 w-2 rounded-full {{ $statusBadge['pulse'] }} {{ in_array($booking->status, ['paid', 'pending']) ? 'animate-pulse' : '' }}"></span>
                        {{ $statusBadge['label'] }}
                    </span>
                    <span class="text-[11px] text-slate-400 mt-1">Dipesan {{ $booking->created_at->translatedFormat('d M Y, H:i') }} WIB</span>
                </div>
            </div>

            {{-- Ticket Body --}}
            <div class="p-6 sm:p-8">
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {{-- Service Info --}}
                    <div class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Layanan Dipesan</span>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">{{ $booking->layanan?->namalayanan ?? 'Layanan' }}</h2>
                        <div class="flex items-center gap-2 pt-1">
                            <span class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-bold text-indigo-700">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 3"/>
                                </svg>
                                {{ $booking->layanan?->durasi ?? 60 }} {{ $booking->layanan?->satuan_durasi ?? 'menit' }}
                            </span>
                            @if($booking->layanan?->kapasitas)
                                <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">
                                    Maks {{ $booking->layanan->kapasitas }} orang
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Schedule Date & Time --}}
                    <div class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Jadwal Sesi</span>
                        <div class="flex items-start gap-2.5">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 mt-0.5">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="3"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-base sm:text-lg font-bold text-slate-900 leading-snug">
                                    {{ \Carbon\Carbon::parse($booking->tanggalbooking)->translatedFormat('l, d F Y') }}
                                </p>
                                <p class="text-sm font-semibold text-indigo-600">
                                    {{ \Carbon\Carbon::parse($booking->jam)->format('H:i') }} - {{ $endDateTime->format('H:i') }} WIB
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Summary --}}
                    <div class="space-y-1 md:col-span-2 lg:col-span-1">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Pembayaran</span>
                        <div class="flex items-baseline gap-2">
                            <p class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $booking->priceLabel }}</p>
                        </div>
                        <p class="text-xs font-medium text-emerald-600 flex items-center gap-1 pt-0.5">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            Pembayaran Berhasil &amp; Terverifikasi
                        </p>
                    </div>
                </div>

                {{-- Previous Reschedule Notice if any --}}
                @if($booking->rescheduled_from_date)
                    <div class="mt-6 rounded-2xl border border-[#C7D2FE] bg-[#EEF2FF]/70 p-4 text-xs sm:text-sm text-[#312E81] flex items-start gap-3">
                        <svg class="w-5 h-5 flex-shrink-0 text-[#4F46E5] mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <strong>Jadwal sesi telah diubah.</strong>
                            Jadwal sebelumnya adalah <strong>{{ \Carbon\Carbon::parse($booking->rescheduled_from_date)->translatedFormat('l, d M Y') }}</strong> pukul <strong>{{ \Carbon\Carbon::parse($booking->rescheduled_from_time)->format('H:i') }} WIB</strong>.
                        </div>
                    </div>
                @endif

                {{-- Quick Utility Actions Inside Ticket --}}
                <div class="mt-6 pt-6 border-t border-slate-100 flex flex-wrap items-center gap-2 sm:gap-3">
                    <a
                        href="{{ $gCalUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 hover:border-slate-300 transition-all cursor-pointer"
                    >
                        <svg class="h-4 w-4 text-[#4285F4]" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 002 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                        </svg>
                        <span>Google Calendar</span>
                    </a>

                    <a
                        href="{{ $waShareUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50/80 px-3.5 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition-all cursor-pointer"
                    >
                        <svg class="h-4 w-4 text-emerald-600 fill-current" viewBox="0 0 24 24">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.969.54 1.761.817 2.796.817 3.182 0 5.768-2.587 5.768-5.766.001-3.181-2.585-5.766-5.768-5.766zm0 10.42c-.93 0-1.636-.26-2.527-.79l-.18-.108-1.58.414.421-1.54-.118-.188c-.604-.962-.976-1.745-.976-2.61 0-2.678 2.181-4.857 4.86-4.857 2.677 0 4.857 2.18 4.857 4.858 0 2.677-2.18 4.857-4.857 4.857z"/>
                        </svg>
                        <span>Bagikan ke WA</span>
                    </a>

                    <a
                        id="btn-invoice"
                        href="{{ route('booking.manage.invoice', ['booking_code' => $booking->booking_code, 'token' => $token]) }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50/70 px-3.5 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition-all cursor-pointer"
                    >
                        <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Unduh Invoice Resmi</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- ── TWO COLUMN DETAILS & ACTION CONTROL ── --}}
        <div class="grid gap-6 lg:grid-cols-[1fr_360px]">

            {{-- ── LEFT COLUMN: BOOKING DETAILS & TIMELINE ── --}}
            <div class="space-y-6">

                {{-- Detail Pelanggan & Booking --}}
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
                    <h3 class="text-base font-bold text-slate-900 pb-4 border-b border-slate-100 flex items-center gap-2">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Informasi Pemesan
                    </h3>
                    <div class="divide-y divide-slate-100 text-xs sm:text-sm">
                        <div class="py-3 flex justify-between items-center gap-4">
                            <span class="text-slate-500">Nama Pelanggan</span>
                            <span class="font-semibold text-slate-900">{{ $booking->namapelanggan }}</span>
                        </div>
                        <div class="py-3 flex justify-between items-center gap-4">
                            <span class="text-slate-500">Email Konfirmasi</span>
                            <span class="font-semibold text-slate-900">{{ $booking->email }}</span>
                        </div>
                        <div class="py-3 flex justify-between items-center gap-4">
                            <span class="text-slate-500">Nomor WhatsApp</span>
                            <span class="font-semibold text-slate-900">{{ $booking->nomorhp }}</span>
                        </div>
                        @if($booking->catatan)
                            <div class="py-3 flex flex-col sm:flex-row sm:justify-between items-start gap-1 sm:gap-4">
                                <span class="text-slate-500">Catatan Khusus</span>
                                <span class="font-medium text-slate-800 sm:text-right max-w-sm">{{ $booking->catatan }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Review Section for Completed Bookings --}}
                @if($booking->status === 'completed')
                    @if($booking->review)
                        <div class="rounded-3xl border border-indigo-100 bg-gradient-to-b from-white to-indigo-50/20 p-6 shadow-xs space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-amber-100 text-amber-600 font-bold text-sm">★</span>
                                    Ulasan Anda
                                </h3>
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 border border-emerald-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Terkirim
                                </span>
                            </div>
                            <div class="rounded-2xl bg-white border border-slate-200/80 p-4 shadow-2xs space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1 text-amber-400">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <svg class="h-5 w-5 {{ $i <= $booking->review->rating ? 'fill-current' : 'text-slate-200 fill-current' }}" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        @endfor
                                        <span class="ml-1.5 text-xs font-bold text-slate-900">{{ $booking->review->rating }}.0</span>
                                    </div>
                                    <span class="text-xs text-slate-400">{{ $booking->review->created_at->translatedFormat('d M Y, H:i') }} WIB</span>
                                </div>
                                @if($booking->review->komentar)
                                    <p class="text-xs sm:text-sm text-slate-700 italic bg-slate-50 rounded-xl p-3 border border-slate-100">
                                        "{{ $booking->review->komentar }}"
                                    </p>
                                @endif
                                @if($booking->review->balasan)
                                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-3.5 space-y-1 mt-3">
                                        <div class="flex items-center gap-1.5 text-xs font-bold text-indigo-700">
                                            <span>Tanggapan dari {{ $booking->tenant->namabisnis }}</span>
                                            @if($booking->review->dibalas_pada)
                                                <span class="font-normal text-slate-500">· {{ $booking->review->dibalas_pada->translatedFormat('d M Y') }}</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-600 leading-relaxed">{{ $booking->review->balasan }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                        {{-- Review Form --}}
                        <div class="rounded-3xl border border-indigo-100 bg-gradient-to-b from-white to-indigo-50/20 p-6 shadow-xs"
                             x-data="{
                                 rating: 0,
                                 hoverRating: 0,
                                 labels: {1: 'Sangat Kurang', 2: 'Kurang Memuaskan', 3: 'Cukup', 4: 'Puas', 5: 'Sangat Puas!'}
                             }">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-100 text-amber-600 font-bold text-sm">★</span>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Berikan Ulasan Layanan</h3>
                                    <p class="text-xs text-slate-500">Bagikan pengalaman Anda saat menggunakan layanan dari {{ $booking->tenant->namabisnis }}.</p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('booking.manage.review', ['booking_code' => $booking->booking_code]) }}" class="mt-5 space-y-4">
                                @csrf
                                <input type="hidden" name="token" value="{{ $token }}">
                                <input type="hidden" name="rating" :value="rating">

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Penilaian Bintang <span class="text-rose-500">*</span></label>
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center gap-1">
                                            <template x-for="star in [1, 2, 3, 4, 5]" :key="star">
                                                <button
                                                    type="button"
                                                    @click="rating = star"
                                                    @mouseenter="hoverRating = star"
                                                    @mouseleave="hoverRating = 0"
                                                    class="p-1 focus:outline-none transition-transform hover:scale-115 active:scale-95 cursor-pointer"
                                                >
                                                    <svg
                                                        class="h-8 w-8 transition-colors"
                                                        :class="(hoverRating || rating) >= star ? 'text-amber-400 fill-amber-400' : 'text-slate-200 fill-slate-200 hover:text-amber-300'"
                                                        viewBox="0 0 20 20"
                                                    >
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                    </svg>
                                                </button>
                                            </template>
                                        </div>
                                        <span
                                            x-text="labels[hoverRating || rating] || 'Pilih rating bintang'"
                                            class="text-xs font-bold"
                                            :class="rating > 0 ? 'text-indigo-600' : 'text-slate-400'"
                                        ></span>
                                    </div>
                                </div>

                                <div>
                                    <label for="komentar" class="block text-xs font-bold text-slate-700 mb-1.5">Komentar &amp; Testimoni (Opsional)</label>
                                    <textarea
                                        id="komentar"
                                        name="komentar"
                                        rows="3"
                                        maxlength="1000"
                                        placeholder="Ceritakan kepuasan Anda terhadap staf, fasilitas, atau hasil layanan..."
                                        class="w-full rounded-2xl border border-slate-200 p-3 text-xs sm:text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-3 focus:ring-indigo-600/15 transition"
                                    >{{ old('komentar') }}</textarea>
                                </div>

                                <div class="flex justify-end">
                                    <button
                                        type="submit"
                                        :disabled="rating === 0"
                                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs sm:text-sm font-bold text-white shadow-md shadow-indigo-600/25 transition-all hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                                    >
                                        <span>Kirim Ulasan</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                @endif

                {{-- Timeline Riwayat Aktivitas --}}
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs">
                    <h3 class="text-base font-bold text-slate-900 pb-4 border-b border-slate-100 flex items-center gap-2">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Riwayat &amp; Status Reservasi
                    </h3>
                    <div class="mt-4 space-y-4">
                        @forelse($booking->logs as $log)
                            @php
                                $badgeStyle = match($log->event) {
                                    'created'         => ['bg' => 'bg-indigo-50 text-indigo-600 border-indigo-200', 'title' => 'Reservasi Dibuat'],
                                    'payment_pending' => ['bg' => 'bg-amber-50 text-amber-600 border-amber-200', 'title' => 'Menunggu Pembayaran'],
                                    'payment_success' => ['bg' => 'bg-emerald-50 text-emerald-600 border-emerald-200', 'title' => 'Pembayaran Terkonfirmasi'],
                                    'payment_failed'  => ['bg' => 'bg-rose-50 text-rose-600 border-rose-200', 'title' => 'Pembayaran Gagal'],
                                    'cancelled'       => ['bg' => 'bg-rose-50 text-rose-600 border-rose-200', 'title' => 'Booking Dibatalkan'],
                                    'rescheduled'     => ['bg' => 'bg-indigo-50 text-[#4F46E5] border-[#C7D2FE]', 'title' => 'Jadwal Diubah'],
                                    'viewed'          => ['bg' => 'bg-slate-100 text-slate-600 border-slate-200', 'title' => 'Detail Dilihat'],
                                    'reviewed'        => ['bg' => 'bg-amber-50 text-amber-600 border-amber-200', 'title' => 'Ulasan Diberikan'],
                                    default           => ['bg' => 'bg-slate-100 text-slate-600 border-slate-200', 'title' => ucfirst($log->event)],
                                };
                            @endphp
                            <div class="flex items-start gap-3 text-xs sm:text-sm">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border {{ $badgeStyle['bg'] }} font-bold text-xs">
                                    •
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="font-bold text-slate-900">{{ $badgeStyle['title'] }}</p>
                                        <span class="text-[11px] text-slate-400">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</span>
                                    </div>
                                    <p class="text-xs text-slate-600 mt-0.5">{{ $log->note }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 text-center py-4">Belum ada riwayat aktivitas tercatat.</p>
                        @endforelse
                    </div>
                </div>

            </div>

            {{-- ── RIGHT COLUMN: MANAGEMENT ACTION CONTROL & MERCHANT INFO ── --}}
            <div class="space-y-6">

                {{-- Action Management Card --}}
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-bold text-slate-900 pb-4 border-b border-slate-100 flex items-center gap-2">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        Tindakan Reservasi
                    </h3>

                    <div class="mt-5 space-y-4">
                        @if($booking->status === 'paid')

                            {{-- Reschedule Action Section --}}
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Ubah Jadwal (Reschedule)
                                    </span>
                                    @if($canReschedule)
                                        <span class="rounded-md bg-indigo-100 px-2 py-0.5 text-[10px] font-bold text-indigo-700">Tersedia</span>
                                    @else
                                        <span class="rounded-md bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600">Berakhir</span>
                                    @endif
                                </div>

                                @if($canReschedule)
                                    <p class="text-xs text-slate-500 leading-relaxed">
                                        Perlu menyesuaikan waktu kunjungan? Anda dapat memilih tanggal dan jam baru tanpa biaya tambahan.
                                    </p>
                                    @if($rescheduleDeadline)
                                        <p class="text-[11px] font-medium text-indigo-700 bg-indigo-50/90 rounded-lg px-2.5 py-1.5 border border-indigo-100">
                                            Batas waktu: {{ $rescheduleDeadline->translatedFormat('d M Y, H:i') }} WIB
                                        </p>
                                    @endif
                                    <a
                                        id="btn-reschedule"
                                        href="{{ route('booking.manage.reschedule.show', ['booking_code' => $booking->booking_code, 'token' => $token]) }}"
                                        class="w-full flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-xs sm:text-sm font-bold text-white shadow-md shadow-indigo-600/20 hover:bg-indigo-700 active:scale-[0.98] transition-all cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span>Pilih Jadwal Baru</span>
                                    </a>
                                @else
                                    <p class="text-xs text-slate-500 leading-relaxed">
                                        Batas pengubahan jadwal mandiri telah berlalu (minimal {{ $booking->tenant->reschedule_before_hours ?? 24 }} jam sebelum sesi).
                                    </p>
                                    @if($merchantWaUrl)
                                        <a
                                            href="{{ $merchantWaUrl }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                                        >
                                            <span>Ajukan ke Merchant via WhatsApp</span>
                                        </a>
                                    @endif
                                @endif
                            </div>

                            {{-- Cancellation Action Section --}}
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Batalkan Booking
                                    </span>
                                    @if($canCancel)
                                        <span class="rounded-md bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-700">Tersedia</span>
                                    @else
                                        <span class="rounded-md bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600">Berakhir</span>
                                    @endif
                                </div>

                                @if($canCancel)
                                    <p class="text-xs text-slate-500 leading-relaxed">
                                        Pembatalan akan melepas slot jadwal dan dana Anda akan diproses untuk pengembalian (*refund*).
                                    </p>
                                    @if($cancelDeadline)
                                        <p class="text-[11px] font-medium text-rose-700 bg-rose-50/90 rounded-lg px-2.5 py-1.5 border border-rose-100">
                                            Batas pembatalan: {{ $cancelDeadline->translatedFormat('d M Y, H:i') }} WIB
                                        </p>
                                    @endif
                                    <button
                                        id="btn-cancel"
                                        type="button"
                                        @click="showCancelModal = true"
                                        class="w-full flex items-center justify-center gap-2 rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-rose-600 hover:bg-rose-50 hover:border-rose-300 active:scale-[0.98] transition-all cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        <span>Batalkan Booking Ini</span>
                                    </button>
                                @else
                                    <p class="text-xs text-slate-500 leading-relaxed">
                                        Batas pembatalan mandiri telah berakhir (minimal {{ $booking->tenant->cancel_before_hours ?? 24 }} jam sebelum sesi).
                                    </p>
                                @endif
                            </div>

                        @elseif($booking->status === 'cancelled')
                            {{-- Cancelled State Card --}}
                            <div class="rounded-2xl border border-rose-200 bg-rose-50/80 p-5 space-y-3">
                                <div class="flex items-center gap-2.5 text-rose-700 font-bold text-sm">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </div>
                                    <span>Booking Telah Dibatalkan</span>
                                </div>
                                <p class="text-xs text-rose-800 leading-relaxed">
                                    Reservasi ini telah dibatalkan secara resmi.
                                    @if($booking->refund)
                                        Pengembalian dana sebesar <strong>Rp {{ number_format($booking->refund->jumlah, 0, ',', '.') }}</strong> tercatat dengan status <strong>{{ ucfirst($booking->refund->status) }}</strong>.
                                    @endif
                                </p>
                                <a
                                    href="{{ $booking->tenant->slug ? url('/' . $booking->tenant->slug) : '/' }}"
                                    class="w-full flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white hover:bg-slate-800 transition-colors"
                                >
                                    <span>Pesan Sesi Baru di {{ $booking->tenant->namabisnis }}</span>
                                </a>
                            </div>

                        @elseif($booking->status === 'completed')
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-center space-y-2">
                                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 font-bold">
                                    ✓
                                </div>
                                <h4 class="text-sm font-bold text-emerald-900">Sesi Telah Selesai</h4>
                                <p class="text-xs text-emerald-700">Terima kasih telah menggunakan layanan dari {{ $booking->tenant->namabisnis }}.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Tenant Information Card --}}
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <h3 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100 flex items-center gap-2">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        Lokasi &amp; Kontak Merchant
                    </h3>

                    <div class="space-y-3 text-xs sm:text-sm">
                        <div class="flex items-start gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 mt-0.5">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <span class="font-bold text-slate-900 block">{{ $booking->tenant->namabisnis }}</span>
                                <span class="text-slate-500 block leading-relaxed">{{ $booking->tenant->alamat ?? 'Alamat belum diatur' }}</span>
                                @if($booking->tenant->alamat)
                                    <a
                                        href="https://www.google.com/maps/search/?api=1&query={{ urlencode($booking->tenant->alamat . ' ' . $booking->tenant->namabisnis) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 mt-1"
                                    >
                                        <span>Buka di Google Maps</span>
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                @endif
                            </div>
                        </div>

                        @if($booking->tenant->nomorhp)
                            <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 block">Nomor Telepon</span>
                                    <a href="tel:{{ $booking->tenant->nomorhp }}" class="font-bold text-slate-900 hover:text-indigo-600 transition-colors">
                                        {{ $booking->tenant->nomorhp }}
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Security Notice --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-4 text-xs text-slate-500 space-y-1.5">
                    <div class="flex items-center gap-1.5 font-bold text-slate-700">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span>Tautan Akses Rahasia</span>
                    </div>
                    <p class="leading-relaxed">
                        Halaman ini memuat akses langsung untuk mengelola booking Anda tanpa login. Simpan tautan ini atau jangan bagikan kepada pihak yang tidak berwenang.
                    </p>
                </div>

            </div>
        </div>

    </main>

    {{-- ── MODERN CANCEL CONFIRMATION MODAL ── --}}
    <div
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        x-show="showCancelModal"
        x-cloak
        role="dialog"
        aria-modal="true"
    >
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
            x-show="showCancelModal"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="showCancelModal = false"
        ></div>

        {{-- Modal Card --}}
        <div
            class="relative w-full max-w-md overflow-hidden rounded-3xl bg-white p-6 sm:p-7 shadow-2xl transition-all z-10"
            x-show="showCancelModal"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-base sm:text-lg font-bold text-slate-900">
                        Batalkan Booking Ini?
                    </h3>
                    <p class="mt-1 text-xs text-slate-500 leading-relaxed">
                        Tindakan ini tidak dapat diulang kembali. Sesi jadwal Anda akan dibatalkan dan slot waktu akan dibuka kembali untuk publik.
                    </p>
                </div>
            </div>

            {{-- Summary Card in Modal --}}
            <div class="mt-5 rounded-2xl bg-slate-50 p-4 border border-slate-100 text-xs space-y-2">
                <div class="flex justify-between">
                    <span class="text-slate-500">Kode Booking</span>
                    <span class="font-mono font-bold text-slate-900">{{ $booking->booking_code }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Layanan</span>
                    <span class="font-semibold text-slate-900">{{ $booking->layanan->namalayanan ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Jadwal Sesi</span>
                    <span class="font-semibold text-slate-900">
                        {{ \Carbon\Carbon::parse($booking->tanggalbooking)->translatedFormat('d M Y') }}, {{ \Carbon\Carbon::parse($booking->jam)->format('H:i') }} WIB
                    </span>
                </div>
                <div class="flex justify-between pt-2 border-t border-slate-200">
                    <span class="text-slate-600 font-medium">Estimasi Refund</span>
                    <span class="font-bold text-emerald-600 text-sm">{{ $booking->priceLabel }}</span>
                </div>
            </div>

            <p class="mt-3 text-[11px] text-slate-400 leading-relaxed text-center">
                Dana refund akan dikembalikan sesuai ketentuan sistem pembayaran dan kebijakan merchant {{ $booking->tenant->namabisnis }}.
            </p>

            {{-- Modal Buttons --}}
            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
                <button
                    type="button"
                    class="inline-flex justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 hover:bg-slate-50 active:scale-[0.98] transition-all cursor-pointer"
                    @click="showCancelModal = false"
                    :disabled="isCancelling"
                >
                    Kembali / Jangan Batalkan
                </button>
                <form
                    method="POST"
                    action="{{ route('booking.manage.cancel', ['booking_code' => $booking->booking_code, 'token' => $token]) }}"
                    class="inline"
                    @submit="isCancelling = true"
                >
                    @csrf
                    <button
                        type="submit"
                        class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-md shadow-rose-600/20 hover:bg-rose-700 active:scale-[0.98] transition-all cursor-pointer"
                        :disabled="isCancelling"
                    >
                        <span x-show="!isCancelling">Ya, Batalkan Booking</span>
                        <span x-show="isCancelling" class="inline-flex items-center gap-2" x-cloak>
                            <span class="spinner"></span> Memproses...
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Minimalist Clean Footer ── --}}
    <footer class="border-t border-slate-200/80 bg-white py-6 mt-12 text-center text-xs text-slate-500">
        <div class="mx-auto flex w-full max-w-5xl flex-col items-center justify-between gap-3 px-4 sm:flex-row sm:px-6">
            <p>&copy; {{ date('Y') }} {{ $booking->tenant->namabisnis }}. Hak cipta dilindungi.</p>
            <div class="flex items-center gap-2 text-slate-400 text-xs">
                <span>Didukung oleh <strong class="text-slate-700">BookQu</strong></span>
                <span>•</span>
                <span class="text-emerald-600 font-medium">Koneksi Aman &amp; Terenkripsi</span>
            </div>
        </div>
    </footer>

    <script>
        function manageBooking() {
            return {
                showCancelModal: false,
                isCancelling:    false,
                copied:          false,
                copyCode(code) {
                    navigator.clipboard.writeText(code).then(() => {
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    }).catch(() => {});
                },
                init() {
                    @if($errors->has('cancel'))
                        this.showCancelModal = true;
                    @endif
                },
            };
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
