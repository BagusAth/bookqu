<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ $tenant->namabisnis }} - Payment Failed</title>
        @vite('resources/css/app.css')
        <link rel="stylesheet" href="{{ asset('css/booking-program.css') }}" />
    </head>
    <body class="booking-page flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md px-6 py-12">
            <div class="booking-card p-8 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100 text-red-600 mb-6">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                
                <h1 class="text-2xl font-bold text-[#111827]">Payment Failed</h1>
                <p class="mt-2 text-sm text-[#6B7280]">
                    We couldn't process your payment. Your selected time slot is no longer reserved. Please try again.
                </p>

                <div class="mt-8 space-y-3">
                    <a href="{{ route('customer.booking.date', $tenant->slug) }}" class="inline-flex w-full justify-center rounded-xl bg-[#4F46E5] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#4338CA]">
                        Retry Booking
                    </a>
                    <a href="#" class="inline-flex w-full justify-center rounded-xl border border-[#E5E7EB] bg-white px-4 py-3 text-sm font-bold text-[#374151] transition hover:bg-[#F9FAFB]">
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </body>
</html>
