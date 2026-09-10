{{-- Owner Portal Topbar Component --}}
@php
    $tenantContextId = app(\App\Support\TenantContext::class)->getTenantId();
    $tenant = $tenantContextId ? \App\Models\Tenant::find($tenantContextId) : auth()->user()?->tenant;
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
    } elseif (str_contains($currentPath, 'notifications')) {
        $parentSection = 'Account';
        $currentBreadcrumbLabel = 'Notifications';
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
        <nav class="flex items-center gap-1.5 text-xs font-semibold text-[#6e6584]" aria-label="Breadcrumb">
            <a href="{{ route('owner.dashboard') }}" class="inline-flex items-center justify-center h-7 w-7 rounded-lg text-[#6e6584] hover:bg-[#f7f7fa] hover:text-[#382186] transition" title="Dashboard">
                <svg class="h-3.5 w-3.5 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </a>
            @if ($parentSection)
                <span class="text-[#cbd5e1]">/</span>
                <span class="hidden sm:inline-block text-[#6e6584]">{{ $parentSection }}</span>
            @endif
            <span class="text-[#cbd5e1]">/</span>
            <span class="inline-flex items-center rounded-lg bg-[#f3effe] px-2.5 py-1 text-xs font-bold text-[#382186] border border-[#b499ff]/30 shadow-2xs">
                {{ $currentBreadcrumbLabel }}
            </span>
        </nav>
    </div>

    {{-- Right: Quick Action, View/Copy Public Link, Profile Dropdown --}}
    <div class="flex items-center gap-2 sm:gap-3">
        @if ($tenant && $tenant->slug)
            {{-- Unified Public Booking Page Action Group --}}
            <div class="inline-flex items-stretch rounded-xl border border-[#b499ff]/40 bg-[#f3effe] p-0.5 shadow-2xs">
                <a
                    href="/{{ $tenant->slug }}"
                    target="_blank"
                    rel="noopener"
                    class="craft-btn inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold text-[#382186] hover:bg-white transition-all active:scale-[0.98]"
                    title="Lihat reservasi publik di tab baru"
                    id="btn-topbar-view-booking-page"
                >
                    <svg class="h-3.5 w-3.5 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    <span class="hidden md:inline">Booking Page</span>
                </a>
                <div class="w-px bg-[#b499ff]/30 my-0.5"></div>
                <button
                    type="button"
                    x-data="{ copied: false }"
                    @click="
                        navigator.clipboard.writeText(window.location.origin + '/{{ $tenant->slug }}');
                        copied = true;
                        setTimeout(() => copied = false, 2500);
                        $dispatch('toast', { message: 'Link reservasi berhasil disalin ke clipboard!', type: 'success' });
                    "
                    class="craft-btn inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-bold text-[#382186] hover:bg-white transition-all active:scale-[0.98] cursor-pointer"
                    title="Salin tautan reservasi publik"
                    id="btn-topbar-copy-link"
                >
                    <svg x-show="!copied" class="h-3.5 w-3.5 text-[#382186]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <svg x-show="copied" x-cloak class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="hidden lg:inline text-[11px]" x-text="copied ? 'Tersalin!' : 'Salin'"></span>
                </button>
            </div>
        @endif

        {{-- Business Status Chip --}}
        <a
            href="{{ route('owner.settings.business') }}"
            class="hidden lg:inline-flex items-center gap-2 rounded-xl border border-[#e7e2f7] bg-[#f7f7fa] px-3 py-1.5 hover:bg-[#e7e2f7]/60 hover:border-[#b499ff] transition-colors"
            title="Kelola Pengaturan Bisnis"
        >
            <span class="relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
            </span>
            <span class="text-xs font-bold text-[#231a3d] truncate max-w-[140px]">{{ $tenant->namabisnis ?? 'Bisnis' }}</span>
        </a>

        {{-- Notification Bell Dropdown --}}
        <div class="relative" x-data="ownerNotificationBell()" x-init="init()" @click.outside="isOpen = false">
            <button
                type="button"
                @click="toggleDropdown()"
                class="craft-btn group relative flex h-10 w-10 items-center justify-center rounded-2xl border border-[#e7e2f7] bg-white text-[#6e6584] hover:border-[#b499ff] hover:bg-[#f8f6ff] hover:text-[#382186] hover:shadow-[0_4px_16px_rgba(56,33,134,0.08)] transition-all cursor-pointer active:scale-95"
                id="btn-owner-notifications"
                aria-label="Lihat Notifikasi"
                :aria-expanded="isOpen"
            >
                {{-- Modern Bell Icon with subtle hover motion --}}
                <svg
                    class="h-5 w-5 transition-transform duration-300 group-hover:scale-105"
                    :class="unreadCount > 0 ? 'text-[#382186]' : 'text-[#6e6584] group-hover:text-[#382186]'"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.85"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0M10.5 5.25a1.5 1.5 0 013 0"/>
                </svg>

                {{-- Unread Glowing Badge Counter --}}
                <span
                    x-show="unreadCount > 0"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-50"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-cloak
                    class="absolute -top-1 -right-1 flex h-4.5 min-w-4.5 items-center justify-center"
                >
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-60"></span>
                    <span class="relative inline-flex items-center justify-center min-h-4.5 min-w-4.5 rounded-full bg-gradient-to-r from-rose-500 to-rose-600 px-1 text-[9px] font-black tracking-tight text-white shadow-xs border-2 border-white" x-text="unreadCount > 99 ? '99+' : unreadCount"></span>
                </span>
            </button>

            {{-- Dropdown Menu --}}
            <div
                x-show="isOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-120"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                x-cloak
                class="absolute right-0 mt-2.5 w-84 sm:w-[410px] origin-top-right rounded-3xl border border-[#e7e2f7] bg-white/95 backdrop-blur-xl shadow-[0_20px_50px_-12px_rgba(35,26,61,0.18)] z-50 overflow-hidden divide-y divide-[#f2eefc]"
                style="display: none;"
                id="owner-notifications-dropdown"
            >
                {{-- Decorative Top Accent Gradient --}}
                <div class="h-1 w-full bg-gradient-to-r from-[#382186] via-[#7a5af8] to-[#b499ff]"></div>

                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-3.5 bg-[#fdfcff]/90">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#f3effe] text-[#382186]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs font-extrabold text-[#231a3d] block leading-none">Notifikasi Bisnis</span>
                            <span class="text-[10px] text-[#6e6584] mt-0.5 block" x-text="unreadCount > 0 ? `${unreadCount} belum dibaca` : 'Semua sudah dibaca'"></span>
                        </div>
                    </div>

                    <button
                        type="button"
                        x-show="unreadCount > 0"
                        @click="markAllAsRead()"
                        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-[11px] font-bold text-[#382186] hover:bg-[#f3effe] transition cursor-pointer"
                        id="btn-mark-all-read"
                    >
                        <svg class="h-3.5 w-3.5 text-[#7a5af8]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Tandai dibaca</span>
                    </button>
                </div>

                {{-- List of Notifications --}}
                <div class="max-h-96 overflow-y-auto divide-y divide-[#f7f7fa]">
                    <template x-if="loading && notifications.length === 0">
                        <div class="p-8 text-center text-xs text-[#6e6584]">
                            <svg class="inline-block h-6 w-6 animate-spin text-[#382186] mb-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <p class="font-medium">Memuat notifikasi...</p>
                        </div>
                    </template>

                    <template x-if="!loading && notifications.length === 0">
                        <div class="p-9 text-center" id="empty-notifications-state">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-[#f3effe] to-[#e7e2f7] text-[#382186] shadow-inner">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0M10.5 5.25a1.5 1.5 0 013 0"/>
                                </svg>
                            </div>
                            <p class="text-xs font-extrabold text-[#231a3d]">Belum ada notifikasi baru</p>
                            <p class="text-[11px] text-[#6e6584] mt-1 max-w-xs mx-auto leading-relaxed">Reservasi masuk dan perubahan status operasional akan muncul secara instan di sini.</p>
                        </div>
                    </template>

                    <template x-for="item in notifications" :key="item.id">
                        <div
                            @click="handleNotificationClick(item)"
                            :class="item.is_read ? 'bg-white hover:bg-[#faf9fe]' : 'bg-[#f7f5ff] hover:bg-[#efeafc] border-l-[3px] border-[#7a5af8]'"
                            class="group relative flex items-start gap-3.5 p-4 transition-all cursor-pointer"
                        >
                            {{-- Event Icon Badge --}}
                            <div class="shrink-0 mt-0.5">
                                <template x-if="item.event_type === 'new_booking'">
                                    <div class="flex h-8.5 w-8.5 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200/80 shadow-2xs">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                </template>
                                <template x-if="item.event_type === 'cancelled'">
                                    <div class="flex h-8.5 w-8.5 items-center justify-center rounded-xl bg-rose-50 text-rose-600 border border-rose-200/80 shadow-2xs">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                </template>
                                <template x-if="item.event_type === 'rescheduled'">
                                    <div class="flex h-8.5 w-8.5 items-center justify-center rounded-xl bg-amber-50 text-amber-600 border border-amber-200/80 shadow-2xs">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                </template>
                                <template x-if="item.event_type === 'completed' || (item.event_type !== 'new_booking' && item.event_type !== 'cancelled' && item.event_type !== 'rescheduled')">
                                    <div class="flex h-8.5 w-8.5 items-center justify-center rounded-xl bg-[#f3effe] text-[#382186] border border-[#b499ff]/40 shadow-2xs">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                </template>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0 pr-1">
                                <div class="flex items-baseline justify-between gap-1 mb-1">
                                    <p class="text-xs font-bold text-[#231a3d] truncate group-hover:text-[#382186] transition-colors" x-text="item.title"></p>
                                    <span class="text-[10px] text-[#6e6584] whitespace-nowrap shrink-0 font-medium" x-text="item.created_at"></span>
                                </div>
                                <p class="text-[11px] text-[#6e6584] line-clamp-2 leading-relaxed" x-text="item.message"></p>
                            </div>

                            {{-- Unread Dot Indicator --}}
                            <div class="shrink-0 self-center pl-1" x-show="!item.is_read">
                                <span class="relative flex h-2.5 w-2.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#7a5af8] opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#382186]"></span>
                                </span>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Footer --}}
                <div class="p-3 bg-[#fdfcff]/90 text-center">
                    <a
                        href="{{ route('owner.notifications') }}"
                        class="group inline-flex items-center justify-center gap-1.5 text-xs font-extrabold text-[#382186] hover:text-[#231a3d] transition py-1.5 px-4 rounded-xl hover:bg-[#f3effe]"
                        id="link-view-all-notifications"
                    >
                        <span>Lihat Semua Notifikasi</span>
                        <svg class="h-3.5 w-3.5 text-[#7a5af8] transition-transform duration-200 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

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

<script>
function ownerNotificationBell() {
    return {
        isOpen: false,
        unreadCount: {{ auth()->user()?->unreadNotifications()->count() ?? 0 }},
        notifications: [],
        loading: false,
        pollInterval: null,

        init() {
            this.fetchNotifications();
            this.pollInterval = setInterval(() => {
                this.fetchNotifications(true);
            }, 30000);
        },

        toggleDropdown() {
            this.isOpen = !this.isOpen;
            if (this.isOpen && this.notifications.length === 0) {
                this.fetchNotifications();
            }
        },

        async fetchNotifications(isBackground = false) {
            if (!isBackground) this.loading = true;
            try {
                const res = await fetch('{{ route("owner.notifications") }}?limit=8', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (res.ok) {
                    const json = await res.json();
                    if (json.success) {
                        this.notifications = json.data;
                        this.unreadCount = json.unread_count;
                    }
                }
            } catch (e) {
                console.error('Failed to load notifications', e);
            } finally {
                if (!isBackground) this.loading = false;
            }
        },

        async handleNotificationClick(item) {
            if (!item.is_read) {
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                item.is_read = true;
                try {
                    await fetch(`/owner/notifications/${item.id}/read`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        }
                    });
                } catch (e) {
                    console.error('Failed to mark read', e);
                }
            }
            if (item.url) {
                window.location.href = item.url;
            }
        },

        async markAllAsRead() {
            this.unreadCount = 0;
            this.notifications.forEach(n => n.is_read = true);
            try {
                await fetch('{{ route("owner.notifications.read-all") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });
            } catch (e) {
                console.error('Failed to mark all as read', e);
            }
        }
    };
}
</script>
