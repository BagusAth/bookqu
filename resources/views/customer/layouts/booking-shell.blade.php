<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>@yield('title', 'Booking') — {{ $tenant->namabisnis ?? 'BookQu' }}</title>
        @vite('resources/css/app.css')
        <link rel="stylesheet" href="{{ asset('css/booking-program.css') }}" />
        @yield('head')
        @if(isset($tenant) && $tenant->theme_color)
            <style>
                :root {
                    --bq-primary: {{ $tenant->theme_color }};
                    --bq-primary-hover: {{ $tenant->theme_color }};
                    --color-primary-600: {{ $tenant->theme_color }};
                    --color-primary-700: {{ $tenant->theme_color }};
                }
            </style>
        @endif
    </head>
    <body class="booking-page min-h-screen flex flex-col bg-[#F8FAFC]">
        {{-- Clean, Focused Booking Header --}}
        <header class="sticky top-0 z-40 border-b border-[#E2E8F0] bg-white/95 backdrop-blur-md shadow-xs">
            <div class="booking-shell mx-auto flex w-full max-w-[1280px] items-center justify-between px-4 sm:px-6 py-3.5">
                {{-- Business & Platform Identity --}}
                <div class="flex items-center gap-3">
                    <a href="{{ url('/' . ($tenant->slug ?? '')) }}" class="flex items-center gap-2.5 transition hover:opacity-90">
                        @if(isset($tenant) && $tenant->logo_path)
                            <img src="{{ Storage::url($tenant->logo_path) }}" alt="{{ $tenant->namabisnis }}" class="h-9 w-9 rounded-xl object-cover border border-[#E2E8F0] shadow-xs" />
                        @else
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#4F46E5] text-white font-bold text-sm shadow-xs">
                                {{ strtoupper(substr($tenant->namabisnis ?? 'BQ', 0, 2)) }}
                            </div>
                        @endif
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-[#0F172A] leading-tight">{{ $tenant->namabisnis ?? 'BookQu' }}</span>
                            <span class="text-[11px] font-medium text-[#64748B]">Reservasi Online Resmi</span>
                        </div>
                    </a>
                </div>

                {{-- Contextual Back & Support Actions --}}
                <div class="flex items-center gap-2 sm:gap-4">
                    @hasSection('back_url')
                        <a href="@yield('back_url')" class="inline-flex items-center gap-1.5 rounded-xl border border-[#E2E8F0] bg-white px-3 py-1.5 text-xs sm:text-sm font-semibold text-[#475569] shadow-2xs transition hover:border-[#CBD5E1] hover:bg-[#F8FAFC] hover:text-[#0F172A]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span class="hidden sm:inline">@yield('back_label', 'Kembali')</span>
                        </a>
                    @endif

                    @if(isset($tenant) && $tenant->nomorhp)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $tenant->nomorhp) }}?text=Halo%20{{ urlencode($tenant->namabisnis) }},%20saya%20butuh%20bantuan%20mengenai%20booking"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center gap-1.5 rounded-xl border border-transparent px-3 py-1.5 text-xs sm:text-sm font-medium text-[#64748B] transition hover:bg-[#F1F5F9] hover:text-[#0F172A]"
                           title="Butuh bantuan? Chat WhatsApp">
                            <svg class="h-4 w-4 text-[#10B981]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.969.54 1.761.817 2.796.817 3.182 0 5.768-2.587 5.768-5.766.001-3.181-2.585-5.766-5.768-5.766zm0 10.42c-.93 0-1.636-.26-2.527-.79l-.18-.108-1.58.414.421-1.54-.118-.188c-.604-.962-.976-1.745-.976-2.61 0-2.678 2.181-4.857 4.86-4.857 2.677 0 4.857 2.18 4.857 4.858 0 2.677-2.18 4.857-4.857 4.857z"/>
                            </svg>
                            <span class="hidden sm:inline">Bantuan</span>
                        </a>
                    @endif
                </div>
            </div>
        </header>

        {{-- Optional Banner Image for Tenant --}}
        @if(isset($tenant) && $tenant->banner_path && Request::routeIs('customer.booking.program'))
            <div class="w-full bg-[#0F172A] relative h-40 sm:h-56 overflow-hidden">
                <img src="{{ Storage::url($tenant->banner_path) }}" alt="Banner {{ $tenant->namabisnis }}" class="w-full h-full object-cover opacity-85">
                <div class="absolute inset-0 bg-gradient-to-t from-[#0F172A]/70 via-transparent to-transparent"></div>
            </div>
        @endif

        {{-- Stepper Progress Bar (Step 1-6) --}}
        @hasSection('current_step')
            <section class="border-b border-[#E2E8F0] bg-white py-3.5 sm:py-4">
                <div class="booking-shell mx-auto w-full max-w-[1280px] px-4 sm:px-6">
                    <x-customer.progress-header :current="intval($__env->yieldContent('current_step', 1))" />
                </div>
            </section>
        @endif

        {{-- Main Page Content --}}
        <main class="booking-shell mx-auto w-full max-w-[1280px] px-4 sm:px-6 pb-28 sm:pb-24 pt-6 sm:pt-8 flex-grow">
            @yield('content')
        </main>

        {{-- Minimalist Trust Footer --}}
        <footer class="mt-auto border-t border-[#E2E8F0] bg-white py-6 text-center text-xs text-[#64748B]">
            <div class="booking-shell mx-auto flex w-full max-w-[1280px] flex-col items-center justify-between gap-3 px-4 sm:flex-row sm:px-6">
                <p>&copy; {{ date('Y') }} {{ $tenant->namabisnis ?? 'BookQu' }}. Hak cipta dilindungi.</p>
                <div class="flex items-center gap-4 text-[#64748B]">
                    <span class="inline-flex items-center gap-1.5 text-emerald-600 font-medium">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Transaksi Aman &amp; Terenkripsi
                    </span>
                    <span>&bull;</span>
                    <span>Didukung oleh <strong>BookQu</strong></span>
                </div>
            </div>
        </footer>

        @yield('scripts')
    </body>
</html>
