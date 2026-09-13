@extends('customer.layouts.booking-shell')

@section('title', 'Konfirmasi Booking & Invoice')
@section('current_step', 6)
@section('back_url', url('/' . $tenant->slug))
@section('back_label', 'Beranda')

@php
    if (!$booking->booking_code) {
        $booking->assignManagementTokens();
        $booking->refresh();
    }
    $bookingDate = $booking->tanggalbooking ? \Carbon\Carbon::parse($booking->tanggalbooking)->format('Y-m-d') : now()->format('Y-m-d');
    $startDateTime = \Carbon\Carbon::parse($bookingDate . ' ' . $booking->jam);
    $durasiMenit = (int) ($booking->layanan->durasi ?? 60);
    $endDateTime = (clone $startDateTime)->addMinutes($durasiMenit);

    $eventTitle = 'Booking ' . ($booking->layanan->namalayanan ?? 'Layanan') . ' - ' . $tenant->namabisnis;
    $eventLocation = ($tenant->alamat ? $tenant->alamat . ', ' : '') . $tenant->namabisnis;
    $manageUrl = !empty($booking->booking_code)
        ? route('booking.manage', ['booking_code' => $booking->booking_code]) . ($booking->cancellation_token ? '?token=' . $booking->cancellation_token : '')
        : '#';
    $eventDetails = 'Reservasi resmi di ' . $tenant->namabisnis . "\nKode Booking: " . ($booking->booking_code ?: $payment->order_id) . ($manageUrl !== '#' ? "\nKelola Booking: " . $manageUrl : '');

    // Google Calendar URL
    $gCalDates = $startDateTime->format('Ymd\THis') . '/' . $endDateTime->format('Ymd\THis');
    $gCalUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
        . '&text=' . urlencode($eventTitle)
        . '&dates=' . $gCalDates
        . '&details=' . urlencode($eventDetails)
        . '&location=' . urlencode($eventLocation);

    // WhatsApp Share URL
    $waText = "Halo! Saya telah melakukan booking sesi *" . ($booking->layanan->namalayanan ?? 'Layanan') . "* di *" . $tenant->namabisnis . "*\n"
        . "📅 Tanggal: " . \Carbon\Carbon::parse($booking->tanggalbooking)->translatedFormat('l, d F Y') . "\n"
        . "⏰ Jam: " . $booking->jam . " WIB\n"
        . "🔖 Kode Booking: " . ($booking->booking_code ?: $payment->order_id) . "\n"
        . ($manageUrl !== '#' ? "Kelola Reservasi: " . $manageUrl : '');
    $waShareUrl = 'https://api.whatsapp.com/send?text=' . urlencode($waText);

    // Google Maps Search URL
    $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode(($tenant->alamat ? $tenant->alamat . ' ' : '') . $tenant->namabisnis);
@endphp

@section('content')
<div class="mx-auto max-w-2xl">
    {{-- Success Hero Banner --}}
    <div class="text-center mb-8">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 mb-4 shadow-sm">
            <svg class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black text-[#0F172A] tracking-tight">Booking Berhasil Dikonfirmasi!</h1>
        <p class="mt-2 text-sm text-[#64748B] max-w-md mx-auto">
            Terima kasih! Pembayaran Anda telah diterima dan sesi jadwal Anda sudah resmi terdaftar.
        </p>
    </div>

    {{-- Professional Receipt Card --}}
    <div class="booking-receipt-card rounded-2xl border border-[#E2E8F0] bg-white shadow-sm overflow-hidden mb-6">
        {{-- Receipt Header --}}
        <div class="bg-gradient-to-r from-[#4F46E5] to-[#6366F1] p-6 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-white/80">Tanda Terima Resmi</p>
                <h2 class="text-xl font-bold mt-0.5">{{ $tenant->namabisnis }}</h2>
            </div>
            <div class="sm:text-right">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/20 backdrop-blur-xs px-3 py-1 text-xs font-bold text-white border border-white/30">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    {{ $payment->status === 'sukses' ? 'LUNAS' : strtoupper($payment->status) }}
                </span>
            </div>
        </div>

        {{-- Order Identifiers Bar --}}
        <div class="bg-[#F8FAFC] px-6 py-4 border-b border-[#E2E8F0] grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs sm:text-sm">
            <div>
                <span class="text-[#64748B] block text-[11px] font-semibold uppercase tracking-wider">Kode Booking</span>
                <span class="font-mono text-base font-bold text-[#4F46E5] tracking-wide">{{ $booking->booking_code ?? '-' }}</span>
            </div>
            <div class="sm:text-right">
                <span class="text-[#64748B] block text-[11px] font-semibold uppercase tracking-wider">Order ID</span>
                <span class="font-mono text-xs sm:text-sm font-semibold text-[#0F172A]">{{ $payment->order_id }}</span>
            </div>
        </div>

        {{-- Session Details --}}
        <div class="p-6 space-y-6">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-[#94A3B8] mb-3">Rincian Layanan &amp; Jadwal</h3>
                <div class="rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-4 space-y-3 text-xs sm:text-sm">
                    <div class="flex justify-between items-start">
                        <span class="text-[#64748B]">Layanan</span>
                        <span class="font-bold text-[#0F172A] text-right">{{ $booking->layanan->namalayanan ?? 'Layanan' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[#64748B]">Durasi</span>
                        <span class="font-semibold text-[#0F172A]">{{ $booking->layanan->durasi ?? 60 }} {{ $booking->layanan->satuan_durasi ?? 'menit' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[#64748B]">Hari &amp; Tanggal</span>
                        <span class="font-bold text-[#0F172A]">{{ \Carbon\Carbon::parse($booking->tanggalbooking)->translatedFormat('l, d F Y') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[#64748B]">Jam Sesi</span>
                        <span class="font-bold text-[#4F46E5]">Pukul {{ $booking->jam }} WIB</span>
                    </div>
                    <div class="flex justify-between items-start pt-2 border-t border-[#E2E8F0]">
                        <span class="text-[#64748B]">Lokasi / Tempat</span>
                        <div class="text-right">
                            <span class="font-medium text-[#0F172A] block">{{ $tenant->namabisnis }}</span>
                            @if($tenant->alamat)
                                <span class="text-xs text-[#64748B] block mt-0.5">{{ $tenant->alamat }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Customer Details --}}
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-[#94A3B8] mb-3">Data Pemesan</h3>
                <div class="rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-4 space-y-2 text-xs sm:text-sm">
                    <div class="flex justify-between">
                        <span class="text-[#64748B]">Nama Pemesan</span>
                        <span class="font-bold text-[#0F172A]">{{ $booking->namapelanggan }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#64748B]">Nomor WhatsApp</span>
                        <span class="font-medium text-[#0F172A]">{{ $booking->nomorhp }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#64748B]">Email</span>
                        <span class="font-medium text-[#0F172A]">{{ $booking->email }}</span>
                    </div>
                </div>
            </div>

            {{-- Payment Summary --}}
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-[#94A3B8] mb-3">Rincian Pembayaran</h3>
                <div class="rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-4 space-y-2.5 text-xs sm:text-sm">
                    <div class="flex justify-between">
                        <span class="text-[#64748B]">Metode Pembayaran</span>
                        <span class="font-bold text-[#0F172A] uppercase">{{ $payment->metode ?? 'Midtrans' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#64748B]">Status Transaksi</span>
                        <span class="font-semibold text-emerald-700">Berhasil Dikonfirmasi</span>
                    </div>
                    <div class="flex justify-between items-center pt-3 border-t border-[#E2E8F0]">
                        <span class="text-sm font-bold text-[#0F172A]">Total Pembayaran</span>
                        <span class="text-lg font-black text-[#4F46E5]">Rp {{ number_format($payment->jumlah, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Self-Service Manage Booking Box --}}
            @if ($booking->booking_code && $booking->cancellation_token)
                <div class="rounded-xl border border-[#C7D2FE] bg-[#EEF2FF]/60 p-4 text-xs">
                    <div class="flex items-start gap-2.5">
                        <svg class="h-4 w-4 text-[#4F46E5] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="font-bold text-[#4F46E5]">Kelola Booking Mandiri (Tanpa Perlu Login)</p>
                            <p class="text-[#64748B] mt-0.5 leading-relaxed">
                                Anda dapat melihat detail, membatalkan, atau mengubah jadwal booking ini kapan saja melalui tautan berikut:
                            </p>
                            <a
                                href="{{ $manageUrl }}"
                                class="mt-2 inline-flex items-center gap-1 font-bold text-[#4F46E5] hover:underline break-all"
                            >
                                <span>{{ url('/manage/' . $booking->booking_code) }}</span>
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Quick Utilities & Calendar Integrations --}}
    <div class="no-print rounded-2xl border border-[#E2E8F0] bg-white p-5 shadow-xs mb-8">
        <h3 class="text-xs font-bold uppercase tracking-wider text-[#94A3B8] mb-3">Pengingat Jadwal &amp; Bagikan</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
            {{-- Google Calendar --}}
            <a
                href="{{ $gCalUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center gap-2.5 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-3 text-xs font-semibold text-[#0F172A] hover:border-[#4F46E5] hover:bg-white transition-all shadow-2xs group"
            >
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#EEF2FF] text-[#4F46E5] group-hover:scale-105 transition-transform">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 002 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM9 14H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2zm-8 4H7v-2h2v2zm4 4h-2v-2h2v2zm4 0h-2v-2h2v2z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold truncate">Google Calendar</p>
                    <p class="text-[11px] text-[#64748B]">Simpan ke kalender Google</p>
                </div>
            </a>

            {{-- Download .ics (Apple / Outlook) --}}
            <button
                type="button"
                id="btn-download-ics"
                class="flex items-center gap-2.5 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-3 text-xs font-semibold text-[#0F172A] hover:border-[#4F46E5] hover:bg-white transition-all shadow-2xs group cursor-pointer text-left"
            >
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-[#4F46E5] group-hover:scale-105 transition-transform">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold truncate">Apple / Outlook (.ics)</p>
                    <p class="text-[11px] text-[#64748B]">Unduh file kalender .ics</p>
                </div>
            </button>

            {{-- WhatsApp Share --}}
            <a
                href="{{ $waShareUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center gap-2.5 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-3 text-xs font-semibold text-[#0F172A] hover:border-emerald-500 hover:bg-white transition-all shadow-2xs group"
            >
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 group-hover:scale-105 transition-transform">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2m.01 1.67c4.54 0 8.24 3.7 8.24 8.24 0 2.2-.86 4.27-2.42 5.82a8.19 8.19 0 01-5.82 2.42c-1.46 0-2.9-.38-4.16-1.13l-.3-.18-3.11.82.83-3.03-.2-.31a8.214 8.214 0 01-1.26-4.42c0-4.54 3.7-8.24 8.24-8.24m4.52 11.66c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.26-1.5-1.41-1.75-.14-.25-.02-.39.11-.51.11-.11.25-.29.38-.44.13-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1s.9 2.43 1.03 2.6c.13.17 1.77 2.7 4.29 3.78.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.06-.11-.23-.17-.48-.3z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold truncate">Bagikan ke WhatsApp</p>
                    <p class="text-[11px] text-[#64748B]">Kirim rincian ke teman / keluarga</p>
                </div>
            </a>

            {{-- Google Maps Direction --}}
            <a
                href="{{ $mapsUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center gap-2.5 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-3 text-xs font-semibold text-[#0F172A] hover:border-red-500 hover:bg-white transition-all shadow-2xs group"
            >
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 group-hover:scale-105 transition-transform">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold truncate">Petunjuk Arah</p>
                    <p class="text-[11px] text-[#64748B]">Buka di Google Maps</p>
                </div>
            </a>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="no-print flex flex-col sm:flex-row items-center justify-center gap-3">
        <button
            onclick="window.print()"
            type="button"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-[#CBD5E1] bg-white px-5 py-3 text-sm font-bold text-[#0F172A] shadow-xs hover:bg-[#F8FAFC] hover:border-[#94A3B8] transition active:scale-98 cursor-pointer"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            <span>Cetak / Simpan Invoice</span>
        </button>

        <a
            href="{{ route('customer.booking.program', $tenant->slug) }}"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-[#4F46E5] hover:bg-[#4338CA] px-6 py-3 text-sm font-bold text-white shadow-md shadow-[#4F46E5]/20 transition active:scale-98"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            <span>Pesan Sesi Baru</span>
        </a>

        <a
            href="{{ url('/' . $tenant->slug) }}"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-[#CBD5E1] bg-[#F8FAFC] hover:bg-[#F1F5F9] hover:border-[#94A3B8] px-5 py-3 text-sm font-bold text-[#1E293B] shadow-2xs transition active:scale-98 cursor-pointer"
        >
            <svg class="h-4 w-4 text-[#475569]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span>Kembali ke Beranda</span>
        </a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnDownloadIcs = document.getElementById('btn-download-ics');
        if (btnDownloadIcs) {
            btnDownloadIcs.addEventListener('click', function () {
                const title = @json($eventTitle);
                const description = @json($eventDetails);
                const location = @json($eventLocation);
                const startIso = '{{ $startDateTime->format("Ymd\THis") }}';
                const endIso = '{{ $endDateTime->format("Ymd\THis") }}';
                const code = '{{ $booking->booking_code }}';

                const icsLines = [
                    'BEGIN:VCALENDAR',
                    'VERSION:2.0',
                    'PRODID:-//Bookqu//Booking//ID',
                    'CALSCALE:GREGORIAN',
                    'METHOD:PUBLISH',
                    'BEGIN:VEVENT',
                    `UID:booking-${code}@bookqu.id`,
                    `DTSTAMP:${startIso}`,
                    `DTSTART:${startIso}`,
                    `DTEND:${endIso}`,
                    `SUMMARY:${title}`,
                    `DESCRIPTION:${description.replace(/\n/g, '\\n')}`,
                    `LOCATION:${location}`,
                    'STATUS:CONFIRMED',
                    'END:VEVENT',
                    'END:VCALENDAR'
                ];

                const blob = new Blob([icsLines.join('\r\n')], { type: 'text/calendar;charset=utf-8' });
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.setAttribute('download', `booking-${code}.ics`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        }
    });
</script>
@endsection
