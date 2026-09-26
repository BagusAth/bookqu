<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Kelola booking Anda di {{ $booking->tenant->namabisnis }} — lihat detail, unduh invoice, reschedule jadwal, atau batalkan reservasi." />
    <title>{{ $booking->booking_code }} — Kelola Booking | {{ $booking->tenant->namabisnis }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_icon.png') }}">
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
    @include('customer.partials.manage.header')

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
        @include('customer.partials.manage.ticket-card')

        {{-- ── TWO COLUMN DETAILS & ACTION CONTROL ── --}}
        <div class="grid gap-6 lg:grid-cols-[1fr_360px]">

            {{-- ── LEFT COLUMN: BOOKING DETAILS & TIMELINE ── --}}
            <div class="space-y-6">
                @include('customer.partials.manage.customer-card')
                @include('customer.partials.manage.review-section')
                @include('customer.partials.manage.timeline')
            </div>

            {{-- ── RIGHT COLUMN: MANAGEMENT ACTION CONTROL & MERCHANT INFO ── --}}
            <div class="space-y-6">
                @include('customer.partials.manage.action-panel')
                @include('customer.partials.manage.merchant-card')
            </div>
        </div>

    </main>

    {{-- ── MODERN CANCEL CONFIRMATION MODAL ── --}}
    @include('customer.partials.manage.cancel-modal')

    {{-- ── Minimalist Clean Footer ── --}}
    <footer class="border-t border-slate-200/80 bg-white py-6 mt-12 text-center text-xs text-slate-500">
        <div class="mx-auto flex w-full max-w-5xl flex-col items-center justify-between gap-3 px-4 sm:px-6">
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
