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
                
                <h1 class="text-2xl font-bold text-[#111827]">Booking Confirmed!</h1>
                <p class="mt-2 text-sm text-[#6B7280]">
                    Your booking has been successfully confirmed. A receipt has been sent to your email.
                </p>

                @if($booking && $service)
                <div class="mt-8 rounded-xl bg-[#F9FAFB] p-5 text-left border border-[#E5E7EB]">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-[#6B7280] mb-4">Booking Details</h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-[#6B7280]">Booking ID</span>
                            <span class="font-medium text-[#111827]">#{{ $booking->id }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-[#6B7280]">Program</span>
                            <span class="font-medium text-[#111827]">{{ $service->namalayanan }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-[#6B7280]">Date</span>
                            <span class="font-medium text-[#111827]">{{ \Carbon\Carbon::parse($booking->tanggalbooking)->format('d M Y') }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-[#6B7280]">Time</span>
                            <span class="font-medium text-[#111827]">{{ \Carbon\Carbon::parse($booking->jam)->format('H:i') }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-[#6B7280]">Location</span>
                            <span class="font-medium text-[#111827]">{{ $tenant->namabisnis }}</span>
                        </div>
                    </div>
                </div>
                @endif

                <div class="mt-8">
                    <a href="{{ route('customer.booking.program', $tenant->slug) }}" class="inline-flex w-full justify-center rounded-xl bg-[#4F46E5] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#4338CA]">
                        Back to Homepage
                    </a>
                </div>
            </div>
        </div>
    </body>
</html>
