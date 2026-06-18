<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ $tenant->namabisnis }} - Booking Confirmed</title>
        @vite('resources/css/app.css')
        <link rel="stylesheet" href="{{ asset('css/booking-program.css') }}" />
    </head>
    <body class="booking-page flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md px-6 py-12">
            <div class="booking-card p-8 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 mb-6">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-[#111827]">Booking Dikonfirmasi!</h1>
                <p class="mt-2 text-sm text-[#6B7280]">
                    Pembayaran berhasil. Email konfirmasi beserta link pengelolaan booking sudah dikirim ke email Anda.
                </p>

                @if($booking && $service)
                <div class="mt-8 rounded-xl bg-[#F9FAFB] p-5 text-left border border-[#E5E7EB]">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-[#6B7280] mb-4">Detail Booking</h3>

                    <div class="space-y-3">
                        @if($booking->booking_code)
                        <div class="flex justify-between text-sm">
                            <span class="text-[#6B7280]">Kode Booking</span>
                            <span class="font-bold text-[#4F46E5] font-mono tracking-wide">{{ $booking->booking_code }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between text-sm">
                            <span class="text-[#6B7280]">Program</span>
                            <span class="font-medium text-[#111827]">{{ $service->namalayanan }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-[#6B7280]">Tanggal</span>
                            <span class="font-medium text-[#111827]">{{ \Carbon\Carbon::parse($booking->tanggalbooking)->format('d M Y') }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-[#6B7280]">Jam</span>
                            <span class="font-medium text-[#111827]">{{ \Carbon\Carbon::parse($booking->jam)->format('H:i') }} WIB</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-[#6B7280]">Lokasi</span>
                            <span class="font-medium text-[#111827]">{{ $tenant->namabisnis }}</span>
                        </div>
                    </div>
                </div>

                {{-- Manage link if tokens already generated --}}
                @if($booking->booking_code && $booking->cancellation_token)
                <div class="mt-6 rounded-xl bg-[#EEF2FF] border border-[#C7D2FE] p-4 text-left">
                    <p class="text-xs font-bold text-[#4F46E5] uppercase tracking-wide mb-1">Kelola Booking Anda</p>
                    <p class="text-xs text-[#6B7280]">Gunakan link berikut untuk melihat, membatalkan, atau reschedule booking tanpa login:</p>
                    <a
                        href="{{ route('booking.manage', ['booking_code' => $booking->booking_code]) . '?token=' . $booking->cancellation_token }}"
                        class="mt-2 block text-xs font-medium text-[#4F46E5] hover:underline break-all"
                    >
                        {{ url('/manage/' . $booking->booking_code) }}
                    </a>
                </div>
                @else
                <div class="mt-6 rounded-xl bg-[#FFFBEB] border border-[#FDE68A] p-4 text-left">
                    <p class="text-xs text-[#92400E]">
                        📧 <strong>Cek email Anda!</strong> Link untuk mengelola booking akan dikirimkan ke <strong>{{ $booking->email }}</strong> setelah pembayaran terverifikasi.
                    </p>
                </div>
                @endif
                @endif

                <div class="mt-6 space-y-3">
                    <a href="{{ route('customer.booking.program', $tenant->slug) }}" class="inline-flex w-full justify-center rounded-xl bg-[#4F46E5] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#4338CA]">
                        Buat Booking Baru
                    </a>
                    @if($booking && $booking->booking_code && $booking->cancellation_token)
                    <a
                        href="{{ route('booking.manage', ['booking_code' => $booking->booking_code]) . '?token=' . $booking->cancellation_token }}"
                        class="inline-flex w-full justify-center rounded-xl border border-[#E5E7EB] px-4 py-3 text-sm font-bold text-[#374151] transition hover:bg-[#F9FAFB]"
                    >
                        Kelola Booking Ini
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </body>
</html>
