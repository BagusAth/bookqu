{{-- ── Elevated Header ── --}}
<header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur-md">
    <div class="mx-auto flex w-full max-w-5xl items-center justify-between gap-3 px-4 sm:px-6 py-3.5">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ $booking->tenant->slug ? \App\Support\CustomerBookingRoutes::url('customer.booking.program', $booking->tenant->slug) : '/' }}" class="flex min-w-0 items-center gap-2.5 group">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white font-bold text-sm shadow-xs group-hover:scale-105 transition-transform">
                    {{ strtoupper(substr($booking->tenant->namabisnis, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <span class="block truncate text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ $booking->tenant->namabisnis }}</span>
                    <span class="block truncate text-[11px] font-medium text-slate-500">Portal Kelola Reservasi</span>
                </div>
            </a>
        </div>
        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            @if($merchantWaUrl)
                <a
                    href="{{ $merchantWaUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50/80 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition-all cursor-pointer"
                    title="Bantuan WhatsApp"
                >
                    <svg class="h-4 w-4 text-emerald-600 fill-current" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    <span class="hidden sm:inline">Bantuan Merchant</span>
                </a>
            @endif
            <a
                href="{{ $booking->tenant->slug ? \App\Support\CustomerBookingRoutes::url('customer.booking.program', $booking->tenant->slug) : '/' }}"
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
