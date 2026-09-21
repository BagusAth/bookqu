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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ asset('css/booking-manage.css') }}" />
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased min-h-screen flex flex-col">

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

        $eventTitle = 'Reservasi ' . ($layanan?->namalayanan ?? 'Layanan') . ' - ' . $tenant->namabisnis;
        $eventLocation = ($tenant->alamat ? $tenant->alamat . ', ' : '') . $tenant->namabisnis;
        $manageUrl = route('booking.manage.payment', ['order_id' => $payment->order_id]) . ($token ? '?token=' . $token : '');
        $eventDetails = 'Reservasi resmi di ' . $tenant->namabisnis . "\nOrder ID: " . $payment->order_id . "\nKelola Reservasi: " . $manageUrl;

        // Google Calendar URL
        $gCalDates = $firstStart->format('Ymd\THis') . '/' . $overallEnd->format('Ymd\THis');
        $gCalUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
            . '&text=' . urlencode($eventTitle)
            . '&dates=' . $gCalDates
            . '&details=' . urlencode($eventDetails)
            . '&location=' . urlencode($eventLocation);

        // WhatsApp Share URL
        $scheduleListText = $bookings->map(function($b) use ($durasiMenit, $bookingDate) {
            $s = \Carbon\Carbon::parse($bookingDate . ' ' . $b->jam);
            $e = (clone $s)->addMinutes($durasiMenit);
            return $s->format('H:i') . ' - ' . $e->format('H:i');
        })->implode(', ');

        $waText = "Halo! Saya telah melakukan booking sesi *" . ($layanan?->namalayanan ?? 'Layanan') . "* di *" . $tenant->namabisnis . "*\n"
            . "📅 Tanggal: " . \Carbon\Carbon::parse($bookingDate)->translatedFormat('l, d F Y') . "\n"
            . "⏰ Jadwal Sesi: " . $scheduleListText . " WIB\n"
            . "🔖 Order ID: " . $payment->order_id . "\n"
            . "Kelola Reservasi: " . $manageUrl;
        $waShareUrl = 'https://api.whatsapp.com/send?text=' . urlencode($waText);

        // Merchant WhatsApp URL
        $cleanMerchantPhone = preg_replace('/[^0-9]/', '', $tenant->nomorhp ?? '');
        $merchantWaUrl = !empty($cleanMerchantPhone)
            ? 'https://wa.me/' . $cleanMerchantPhone . '?text=' . urlencode("Halo {$tenant->namabisnis}, saya ingin bertanya mengenai reservasi saya ({$payment->order_id}).")
            : null;

        $invoiceUrl = route('booking.manage.payment.invoice', ['order_id' => $payment->order_id]) . ($token ? '?token=' . $token : '');
    @endphp

    {{-- Header --}}
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex w-full max-w-4xl items-center justify-between px-4 sm:px-6 py-3.5">
            <div class="flex items-center gap-3">
                <a href="{{ $tenant->slug ? url('/' . $tenant->slug) : '/' }}" class="flex items-center gap-2.5 group">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-white font-bold text-sm shadow-xs group-hover:scale-105 transition-transform">
                        {{ strtoupper(substr($tenant->namabisnis, 0, 1)) }}
                    </div>
                    <div>
                        <span class="block text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ $tenant->namabisnis }}</span>
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
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 transition-all shadow-xs"
                    >
                        <svg class="h-3.5 w-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.592 2.654-.696c1.02.57 2.09.873 3.328.873 3.181 0 5.767-2.586 5.768-5.766.001-3.181-2.585-5.768-5.766-5.768zm0 10.365c-1.127 0-2.12-.34-2.981-.926l-.213-.127-1.574.413.42-1.533-.139-.221c-.655-.95-1.002-1.896-1.001-3.197.001-2.518 2.049-4.567 4.568-4.567 2.518 0 4.566 2.048 4.567 4.567 0 2.519-2.049 4.568-4.568 4.568z"/>
                        </svg>
                        <span class="hidden sm:inline">Hubungi</span> Admin
                    </a>
                @endif
                <a
                    href="{{ $invoiceUrl }}"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 transition-all"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Lihat Invoice
                </a>
            </div>
        </div>
    </header>

    {{-- Main Container --}}
    <main class="flex-1 w-full max-w-4xl mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">

        {{-- Status Hero Banner --}}
        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-slate-100">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
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
                <div>
                    @if($payment->status === 'sukses')
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 border border-emerald-200 px-3.5 py-1.5 text-xs font-bold text-emerald-700 shadow-xs">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Terkonfirmasi
                        </span>
                    @elseif($payment->status === 'pending')
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-amber-50 border border-amber-200 px-3.5 py-1.5 text-xs font-bold text-amber-700 shadow-xs">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            Menunggu Pembayaran
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-red-50 border border-red-200 px-3.5 py-1.5 text-xs font-bold text-red-700 shadow-xs">
                            <span class="h-2 w-2 rounded-full bg-red-500"></span>
                            {{ ucfirst($payment->status) }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Multi-Slot Notice Banner --}}
            @if($isMultiSlot)
                <div class="mt-6 rounded-2xl border border-indigo-100 bg-indigo-50/60 p-4 sm:p-5 flex items-start gap-3.5">
                    <div class="rounded-xl bg-indigo-600 p-2 text-white shrink-0 mt-0.5">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Reservasi Multi-Slot Terpadu</h2>
                        <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                            Paket reservasi ini terdiri dari <strong>{{ $bookings->count() }} sesi berurutan</strong> yang terhubung dalam satu grup transaksi pembayaran. Untuk menjaga kesinambungan dan ketersediaan waktu, pembatalan atau perubahan jadwal per slot tidak tersedia secara parsial. Jika Anda membutuhkan penyesuaian khusus, silakan hubungi pengelola bisnis secara langsung.
                        </p>
                    </div>
                </div>
            @endif

            {{-- Booking Details Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                {{-- Jadwal Reservasi --}}
                <div class="space-y-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Jadwal Reservasi</h2>
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
                                <div class="flex items-center justify-between text-xs py-1.5 px-2.5 rounded-lg bg-white border border-slate-200/70">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-700">Slot #{{ $idx + 1 }}:</span>
                                        <span class="font-semibold text-indigo-700 font-mono">{{ $slotStart->format('H:i') }} – {{ $slotEnd->format('H:i') }} WIB</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-slate-500 font-mono text-[11px]">{{ $b->booking_code }}</span>
                                        <span class="font-bold text-slate-800">{{ $b->priceLabel }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Detail Pemesan & Pembayaran --}}
                <div class="space-y-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Ringkasan Pembayaran & Kontak</h2>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 space-y-3 text-xs">
                        <div class="flex justify-between py-1 border-b border-slate-200/50">
                            <span class="text-slate-500">Nama Pelanggan</span>
                            <span class="font-bold text-slate-800">{{ $firstBooking->namapelanggan }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-200/50">
                            <span class="text-slate-500">Email</span>
                            <span class="font-bold text-slate-800 font-mono">{{ $firstBooking->email }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-200/50">
                            <span class="text-slate-500">No. Handphone</span>
                            <span class="font-bold text-slate-800 font-mono">{{ $firstBooking->nomorhp }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-200/50">
                            <span class="text-slate-500">Metode Pembayaran</span>
                            <span class="font-bold text-slate-800 uppercase">{{ $payment->metode ?: 'Midtrans' }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-2">
                            <span class="text-sm font-bold text-slate-900">Total Transaksi</span>
                            <span class="text-base font-black text-indigo-700">Rp {{ number_format((float) $payment->jumlah, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Utilities --}}
            <div class="mt-8 pt-6 border-t border-slate-100 flex flex-wrap items-center gap-3">
                <a
                    href="{{ $gCalUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 transition-all"
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
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 transition-all"
                >
                    <svg class="h-4 w-4 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.592 2.654-.696c1.02.57 2.09.873 3.328.873 3.181 0 5.767-2.586 5.768-5.766.001-3.181-2.585-5.768-5.766-5.768zm0 10.365c-1.127 0-2.12-.34-2.981-.926l-.213-.127-1.574.413.42-1.533-.139-.221c-.655-.95-1.002-1.896-1.001-3.197.001-2.518 2.049-4.567 4.568-4.567 2.518 0 4.566 2.048 4.567 4.567 0 2.519-2.049 4.568-4.568 4.568z"/>
                    </svg>
                    Bagikan via WhatsApp
                </a>

                <a
                    href="{{ $invoiceUrl }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs hover:border-slate-300 hover:bg-slate-50 transition-all"
                >
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Cetak / Unduh Invoice
                </a>
            </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="mt-auto border-t border-slate-200/80 bg-white py-6 text-center text-xs text-slate-500">
        <p>&copy; {{ date('Y') }} {{ $tenant->namabisnis }} — Didukung oleh BookQu Platform.</p>
    </footer>
</body>
</html>
