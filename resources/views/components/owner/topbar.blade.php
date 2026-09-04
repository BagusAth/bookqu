{{-- Owner Portal Topbar Component --}}
@php
    $tenant = auth()->user()?->tenant;
    $currentPath = request()->path();
    $currentRoute = request()->route() ? request()->route()->getName() : '';

    // Generate dynamic breadcrumb label based on current route/path
    $breadcrumb = 'Dashboard';
    if (str_contains($currentPath, 'calendar')) {
        $breadcrumb = 'Booking / Calendar';
    } elseif (str_contains($currentPath, 'schedule-report')) {
        $breadcrumb = 'Analytics / Schedule Report';
    } elseif (str_contains($currentPath, 'schedule')) {
        $breadcrumb = 'Booking / Schedule';
    } elseif (str_contains($currentPath, 'bookings')) {
        $breadcrumb = 'Booking / Bookings';
    } elseif (str_contains($currentPath, 'services') || str_contains($currentPath, 'programs')) {
        $breadcrumb = 'Services / Services';
    } elseif (str_contains($currentPath, 'categories')) {
        $breadcrumb = 'Services / Categories';
    } elseif (str_contains($currentPath, 'staff-resources')) {
        $breadcrumb = 'Services / Staff & Resources';
    } elseif (str_contains($currentPath, 'additional-items')) {
        $breadcrumb = 'Services / Additional Items';
    } elseif (str_contains($currentPath, 'customers')) {
        $breadcrumb = 'Customers / Customers';
    } elseif (str_contains($currentPath, 'vouchers')) {
        $breadcrumb = 'Marketing / Vouchers';
    } elseif (str_contains($currentPath, 'reviews')) {
        $breadcrumb = 'Marketing / Reviews';
    } elseif (str_contains($currentPath, 'analytics')) {
        $breadcrumb = 'Analytics / Overview';
    } elseif (str_contains($currentPath, 'settings/business') || str_contains($currentPath, 'settings/profile')) {
        $breadcrumb = 'Settings / Business';
    } elseif (str_contains($currentPath, 'settings/appearance')) {
        $breadcrumb = 'Settings / Appearance';
    } elseif (str_contains($currentPath, 'settings/payment-setting')) {
        $breadcrumb = 'Settings / Payments';
    } elseif (str_contains($currentPath, 'settings/assets')) {
        $breadcrumb = 'Settings / Assets';
    } elseif (str_contains($currentPath, 'settings/integrations')) {
        $breadcrumb = 'Settings / Integrations';
    } elseif (str_contains($currentPath, 'subscription')) {
        $breadcrumb = 'Settings / Subscription';
    } elseif (str_contains($currentPath, 'landing-page')) {
        $breadcrumb = 'Landing Page [PRO]';
    }
@endphp

<header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-bq-border bg-white/95 px-4 sm:px-6 lg:px-8 backdrop-blur-md shadow-2xs">
    {{-- Left: Mobile Toggle & Breadcrumb --}}
    <div class="flex items-center gap-3">
        <button
            @click="sidebaropen = true"
            class="rounded-xl p-2 text-bq-text-muted hover:bg-bq-surface hover:text-bq-text transition-colors lg:hidden cursor-pointer"
            id="btn-mobile-menu"
            aria-label="Open Navigation Menu"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <div class="flex items-center gap-2 text-xs sm:text-sm font-medium text-bq-text-muted">
            <span class="hidden sm:inline-block font-semibold text-bq-text">Owner Portal</span>
            <span class="hidden sm:inline-block text-bq-border-strong">/</span>
            <span class="font-bold text-[#4F46E5]">{{ $breadcrumb }}</span>
        </div>
    </div>

    {{-- Right: Quick Action, View Public Booking Link, Profile --}}
    <div class="flex items-center gap-3">
        @if ($tenant && $tenant->slug)
            <a
                href="/{{ $tenant->slug }}"
                target="_blank"
                rel="noopener"
                class="hidden md:inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-[#EEF2FF] px-3 py-1.5 text-xs font-bold text-[#4F46E5] hover:bg-[#E0E7FF] transition-all shadow-2xs"
                title="Lihat halaman reservasi publik bisnis Anda"
                id="btn-topbar-view-booking-page"
            >
                <span>Lihat Booking Page</span>
                <svg class="h-3.5 w-3.5 text-[#4F46E5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        @endif

        {{-- Business Status Chip --}}
        <div class="hidden sm:flex items-center gap-2 rounded-xl border border-bq-border bg-[#F8FAFC] px-3 py-1.5">
            <span class="relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
            </span>
            <span class="text-xs font-bold text-bq-text truncate max-w-[140px]">{{ $tenant->namabisnis ?? 'Bisnis' }}</span>
        </div>

        {{-- User Avatar / Quick Chip --}}
        <div class="flex items-center gap-2 pl-2 border-l border-bq-border">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-[#4F46E5] to-indigo-700 text-xs font-bold text-white shadow-xs">
                {{ strtoupper(substr(auth()->user()->namalengkap ?? 'O', 0, 1)) }}
            </div>
            <div class="hidden lg:block text-left leading-tight">
                <p class="text-xs font-bold text-bq-text truncate max-w-[110px]">{{ auth()->user()->namalengkap ?? 'Owner' }}</p>
                <p class="text-[10px] text-bq-text-muted">Owner</p>
            </div>
        </div>
    </div>
</header>
