{{-- ── Elevated Header ── --}}
<header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur-md">
    <div class="mx-auto flex w-full max-w-5xl items-center justify-between px-4 sm:px-6 py-3.5">
        <div class="flex items-center gap-3">
            <a href="{{ $booking->tenant->slug ? route(\App\Support\CustomerBookingRoutes::name('customer.booking.program'), $booking->tenant->slug) : '/' }}" class="flex items-center gap-2.5 group">
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
                href="{{ $booking->tenant->slug ? route(\App\Support\CustomerBookingRoutes::name('customer.booking.program'), $booking->tenant->slug) : '/' }}"
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
