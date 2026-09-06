{{-- Owner Portal Topbar Component --}}
@php
    $tenant = auth()->user()?->tenant;
    $currentPath = request()->path();
    $currentRoute = request()->route() ? request()->route()->getName() : '';

    // Generate dynamic breadcrumb labels
    $parentSection = '';
    $currentBreadcrumbLabel = 'Dashboard';

    if (str_contains($currentPath, 'calendar')) {
        $parentSection = 'Booking';
        $currentBreadcrumbLabel = 'Calendar';
    } elseif (str_contains($currentPath, 'schedule-report')) {
        $parentSection = 'Analytics';
        $currentBreadcrumbLabel = 'Schedule Report';
    } elseif (str_contains($currentPath, 'schedule')) {
        $parentSection = 'Booking';
        $currentBreadcrumbLabel = 'Schedule';
    } elseif (str_contains($currentPath, 'bookings')) {
        $parentSection = 'Booking';
        $currentBreadcrumbLabel = 'Bookings';
    } elseif (str_contains($currentPath, 'services') || str_contains($currentPath, 'programs')) {
        $parentSection = 'Services';
        $currentBreadcrumbLabel = 'Services';
    } elseif (str_contains($currentPath, 'categories')) {
        $parentSection = 'Services';
        $currentBreadcrumbLabel = 'Categories';
    } elseif (str_contains($currentPath, 'staff-resources')) {
        $parentSection = 'Services';
        $currentBreadcrumbLabel = 'Staff & Resources';
    } elseif (str_contains($currentPath, 'additional-items')) {
        $parentSection = 'Services';
        $currentBreadcrumbLabel = 'Additional Items';
    } elseif (str_contains($currentPath, 'customers')) {
        $parentSection = 'Customers';
        $currentBreadcrumbLabel = 'Customers CRM';
    } elseif (str_contains($currentPath, 'vouchers')) {
        $parentSection = 'Marketing';
        $currentBreadcrumbLabel = 'Vouchers';
    } elseif (str_contains($currentPath, 'reviews')) {
        $parentSection = 'Marketing';
        $currentBreadcrumbLabel = 'Reviews';
    } elseif (str_contains($currentPath, 'analytics')) {
        $parentSection = 'Analytics';
        $currentBreadcrumbLabel = 'Overview';
    } elseif (str_contains($currentPath, 'settings/business') || str_contains($currentPath, 'settings/profile')) {
        $parentSection = 'Settings';
        $currentBreadcrumbLabel = 'Business Profile';
    } elseif (str_contains($currentPath, 'settings/appearance')) {
        $parentSection = 'Settings';
        $currentBreadcrumbLabel = 'Appearance';
    } elseif (str_contains($currentPath, 'settings/payment-setting')) {
        $parentSection = 'Settings';
        $currentBreadcrumbLabel = 'Payments';
    } elseif (str_contains($currentPath, 'settings/assets')) {
        $parentSection = 'Settings';
        $currentBreadcrumbLabel = 'Media Assets';
    } elseif (str_contains($currentPath, 'settings/integrations')) {
        $parentSection = 'Settings';
        $currentBreadcrumbLabel = 'Integrations';
    } elseif (str_contains($currentPath, 'subscription')) {
        $parentSection = 'Settings';
        $currentBreadcrumbLabel = 'Subscription';
    } elseif (str_contains($currentPath, 'landing-page')) {
        $parentSection = 'Marketing';
        $currentBreadcrumbLabel = 'Landing Page [PRO]';
    }
@endphp

<header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-[#e7e2f7] bg-white/85 px-4 sm:px-6 lg:px-8 backdrop-blur-md shadow-2xs">
    {{-- Left: Mobile Toggle & Breadcrumb Navigation --}}
    <div class="flex items-center gap-3">
        <button
            type="button"
            @click="sidebaropen = true"
            class="craft-btn rounded-xl p-2 text-[#6e6584] hover:bg-[#f7f7fa] hover:text-[#231a3d] transition-colors lg:hidden cursor-pointer"
            id="btn-mobile-menu"
            aria-label="Open Navigation Menu"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Dynamic Breadcrumb --}}
        <nav class="flex items-center gap-2 text-xs font-semibold text-[#6e6584]" aria-label="Breadcrumb">
            <a href="{{ route('owner.dashboard') }}" class="hidden sm:inline-flex items-center gap-1 text-[#6e6584] hover:text-[#231a3d] transition">
                <svg class="h-3.5 w-3.5 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Owner Portal</span>
            </a>
            <span class="hidden sm:inline-block text-[#e7e2f7]">/</span>
            @if ($parentSection)
                <span class="hidden md:inline-block text-[#6e6584]">{{ $parentSection }}</span>
                <span class="hidden md:inline-block text-[#e7e2f7]">/</span>
            @endif
            <span class="inline-flex items-center rounded-lg bg-[#f3effe] px-2.5 py-1 text-xs font-bold text-[#382186] border border-[#b499ff]/30 shadow-2xs">
                {{ $currentBreadcrumbLabel }}
            </span>
        </nav>
    </div>

    {{-- Right: Quick Action, View/Copy Public Link, Profile Dropdown --}}
    <div class="flex items-center gap-2 sm:gap-3">
        @if ($tenant && $tenant->slug)
            {{-- Public Booking Page Actions Group --}}
            <div class="flex items-center gap-1.5">
                <a
                    href="/{{ $tenant->slug }}"
                    target="_blank"
                    rel="noopener"
                    class="craft-btn hidden md:inline-flex items-center gap-1.5 rounded-xl border border-[#b499ff]/40 bg-[#f3effe] px-3 py-1.5 text-xs font-bold text-[#382186] hover:bg-[#e7e2f7] transition-all shadow-2xs active:scale-[0.98]"
                    title="Lihat halaman reservasi publik bisnis Anda di tab baru"
                    id="btn-topbar-view-booking-page"
                >
                    <span>Lihat Booking Page</span>
                    <svg class="h-3.5 w-3.5 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>

                {{-- Copy Booking Link Button --}}
                <button
                    type="button"
                    x-data="{ copied: false }"
                    @click="
                        navigator.clipboard.writeText(window.location.origin + '/{{ $tenant->slug }}');
                        copied = true;
                        setTimeout(() => copied = false, 2500);
                        $dispatch('toast', { message: 'Link reservasi berhasil disalin ke clipboard!', type: 'success' });
                    "
                    class="craft-btn inline-flex items-center gap-1.5 rounded-xl border border-[#e7e2f7] bg-white px-2.5 py-1.5 text-xs font-bold text-[#6e6584] hover:text-[#231a3d] hover:bg-[#f7f7fa] hover:border-[#b499ff] transition-all shadow-2xs active:scale-[0.98]"
                    title="Salin tautan reservasi publik untuk dibagikan ke customer"
                    id="btn-topbar-copy-link"
                >
                    <svg x-show="!copied" class="h-3.5 w-3.5 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <svg x-show="copied" x-cloak class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="hidden xl:inline" x-text="copied ? 'Tersalin!' : 'Salin Link'"></span>
                </button>
            </div>
        @endif

        {{-- Business Status Chip --}}
        <a
            href="{{ route('owner.settings.business') }}"
            class="hidden sm:inline-flex items-center gap-2 rounded-xl border border-[#e7e2f7] bg-[#f7f7fa] px-3 py-1.5 hover:bg-[#e7e2f7]/60 hover:border-[#b499ff] transition-colors"
            title="Kelola Pengaturan Bisnis"
        >
            <span class="relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
            </span>
            <span class="text-xs font-bold text-[#231a3d] truncate max-w-[140px]">{{ $tenant->namabisnis ?? 'Bisnis' }}</span>
        </a>

        {{-- Interactive User Profile Dropdown --}}
        <div class="relative pl-2 border-l border-[#e7e2f7]" x-data="{ userMenuOpen: false }">
            <button
                type="button"
                @click="userMenuOpen = !userMenuOpen"
                class="craft-btn flex items-center gap-2 rounded-2xl p-1 hover:bg-[#f7f7fa] transition cursor-pointer"
                id="btn-user-menu"
                aria-haspopup="true"
                :aria-expanded="userMenuOpen"
            >
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-[#382186] to-[#231a3d] text-xs font-bold text-white shadow-2xs border border-[#b499ff]/30">
                    {{ strtoupper(substr(auth()->user()->namalengkap ?? 'O', 0, 1)) }}
                </div>
                <div class="hidden lg:block text-left leading-tight pr-1">
                    <p class="text-xs font-extrabold text-[#231a3d] truncate max-w-[110px]">{{ auth()->user()->namalengkap ?? 'Owner' }}</p>
                    <p class="text-[10px] font-semibold text-[#6e6584]">Owner</p>
                </div>
                <svg
                    class="hidden sm:block h-3.5 w-3.5 text-[#6e6584] transition-transform duration-200"
                    :class="userMenuOpen ? 'rotate-180 text-[#382186]' : ''"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {{-- Dropdown Popover Menu --}}
            <div
                x-show="userMenuOpen"
                @click.outside="userMenuOpen = false"
                @keydown.escape.window="userMenuOpen = false"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                x-cloak
                class="absolute right-0 mt-2 w-64 origin-top-right rounded-2xl border border-[#e7e2f7] bg-white p-2 shadow-xl z-50 divide-y divide-[#e7e2f7]"
                style="display: none;"
                id="user-profile-dropdown"
            >
                <div class="px-3 py-2.5">
                    <p class="text-xs font-bold text-[#231a3d] truncate">{{ auth()->user()->namalengkap ?? 'Owner' }}</p>
                    <p class="text-[11px] text-[#6e6584] truncate">{{ auth()->user()->email ?? '' }}</p>
                    <div class="mt-1.5 flex items-center gap-1.5">
                        <span class="inline-flex rounded-md bg-[#f3effe] px-2 py-0.5 text-[10px] font-bold text-[#382186] border border-[#b499ff]/30 truncate">
                            {{ $tenant->namabisnis ?? 'Bisnis Aktif' }}
                        </span>
                    </div>
                </div>

                <div class="py-1.5 space-y-0.5 text-xs font-medium">
                    <a href="{{ route('owner.settings.business') }}" class="craft-btn flex items-center gap-2.5 rounded-xl px-3 py-2 text-[#231a3d] hover:bg-[#f3effe] hover:text-[#382186] transition">
                        <svg class="h-4 w-4 text-[#6e6584]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span>Profil &amp; Bisnis</span>
                    </a>
                    <a href="{{ route('owner.settings.appearance') }}" class="craft-btn flex items-center gap-2.5 rounded-xl px-3 py-2 text-[#231a3d] hover:bg-[#f3effe] hover:text-[#382186] transition">
                        <svg class="h-4 w-4 text-[#6e6584]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4 5 5 0 014-4h4a4 4 0 014 4 5 5 0 01-4 4H7zm0 0l2.5-5.5m7-10.5a3.5 3.5 0 115 5L12 21l-4.5-1 1-4.5 9.5-9.5z"/>
                        </svg>
                        <span>Tampilan Publik</span>
                    </a>
                    <a href="{{ route('owner.settings.payment-setting') }}" class="craft-btn flex items-center gap-2.5 rounded-xl px-3 py-2 text-[#231a3d] hover:bg-[#f3effe] hover:text-[#382186] transition">
                        <svg class="h-4 w-4 text-[#6e6584]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        <span>Metode Pembayaran</span>
                    </a>
                    <a href="{{ route('owner.subscription') }}" class="craft-btn flex items-center gap-2.5 rounded-xl px-3 py-2 text-[#231a3d] hover:bg-[#f3effe] hover:text-[#382186] transition">
                        <svg class="h-4 w-4 text-[#6e6584]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                        <span>Paket Langganan</span>
                    </a>
                </div>

                <div class="pt-1.5">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="craft-btn flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 hover:text-rose-700 transition cursor-pointer">
                            <svg class="h-4 w-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Keluar Akun</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
