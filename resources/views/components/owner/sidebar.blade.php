{{-- Sidebar Navigation Component --}}
@php
    $halamanaktif = request()->path();
    $currentRoute = request()->route() ? request()->route()->getName() : '';

    $tenant = auth()->user()?->tenant;
    $subscription = $tenant?->subscription;
    $isTrial = $subscription && $subscription->status === 'trial';
    $daysLeft = $subscription && $subscription->trial_berakhir
        ? max(0, (int) now()->diffInDays($subscription->trial_berakhir, false))
        : 8;

    $isActive = function ($href, $routeNames = []) use ($halamanaktif, $currentRoute) {
        if ($currentRoute && in_array($currentRoute, (array)$routeNames, true)) {
            return true;
        }
        $trimmed = ltrim($href, '/');
        if ($trimmed === 'owner/dashboard') {
            return $halamanaktif === 'owner/dashboard';
        }
        if ($trimmed === 'owner/services' && ($halamanaktif === 'owner/programs' || str_starts_with($halamanaktif, 'owner/programs'))) {
            return true;
        }
        if ($trimmed === 'owner/settings/business' && ($halamanaktif === 'owner/settings' || $halamanaktif === 'owner/settings/business')) {
            return true;
        }
        return str_starts_with($halamanaktif, $trimmed);
    };

    $sections = [
        [
            'title' => null, // Standalone top item
            'items' => [
                [
                    'label' => 'Dashboard',
                    'href'  => '/owner/dashboard',
                    'route' => ['owner.dashboard'],
                    'icon'  => 'dashboard',
                ],
            ],
        ],
        [
            'title' => 'BOOKING',
            'items' => [
                [
                    'label' => 'Calendar',
                    'href'  => '/owner/calendar',
                    'route' => ['owner.calendar'],
                    'icon'  => 'calendar',
                ],
                [
                    'label' => 'Schedule',
                    'href'  => '/owner/schedule',
                    'route' => ['owner.schedule'],
                    'icon'  => 'schedule',
                ],
                [
                    'label' => 'Bookings',
                    'href'  => '/owner/bookings',
                    'route' => ['owner.bookings', 'owner.bookings.status'],
                    'icon'  => 'bookings',
                ],
            ],
        ],
        [
            'title' => 'BUSINESS',
            'items' => [
                [
                    'label' => 'Services',
                    'href'  => '/owner/services',
                    'route' => ['owner.services', 'owner.programs'],
                    'icon'  => 'services',
                ],
                [
                    'label' => 'Categories',
                    'href'  => '/owner/categories',
                    'route' => ['owner.categories'],
                    'icon'  => 'categories',
                ],
                [
                    'label' => 'Staff & Resources',
                    'href'  => '/owner/staff-resources',
                    'route' => ['owner.staff-resources'],
                    'icon'  => 'staff-resources',
                ],
                [
                    'label' => 'Additional Items',
                    'href'  => '/owner/additional-items',
                    'route' => ['owner.additional-items'],
                    'icon'  => 'additional-items',
                ],
            ],
        ],
        [
            'title' => 'CUSTOMERS',
            'items' => [
                [
                    'label' => 'Customers',
                    'href'  => '/owner/customers',
                    'route' => ['owner.customers'],
                    'icon'  => 'customers',
                ],
            ],
        ],
        [
            'title' => 'MARKETING',
            'items' => [
                [
                    'label' => 'Vouchers',
                    'href'  => '/owner/vouchers',
                    'route' => ['owner.vouchers'],
                    'icon'  => 'vouchers',
                ],
                [
                    'label' => 'Reviews',
                    'href'  => '/owner/reviews',
                    'route' => ['owner.reviews'],
                    'icon'  => 'reviews',
                ],
            ],
        ],
        [
            'title' => 'ANALYTICS',
            'items' => [
                [
                    'label' => 'Overview',
                    'href'  => '/owner/analytics',
                    'route' => ['owner.analytics', 'owner.analytics.export'],
                    'icon'  => 'analytics',
                ],
                [
                    'label' => 'Schedule Report',
                    'href'  => '/owner/schedule-report',
                    'route' => ['owner.schedule-report'],
                    'icon'  => 'schedule-report',
                ],
            ],
        ],
        [
            'title' => 'SETTINGS',
            'items' => [
                [
                    'label' => 'Business',
                    'href'  => '/owner/settings/business',
                    'route' => ['owner.settings.business', 'owner.settings', 'owner.settings.profile', 'owner.settings.account'],
                    'icon'  => 'business-setting',
                ],
                [
                    'label' => 'Appearance',
                    'href'  => '/owner/settings/appearance',
                    'route' => ['owner.settings.appearance'],
                    'icon'  => 'appearance-setting',
                ],
                [
                    'label' => 'Payments',
                    'href'  => '/owner/settings/payment-setting',
                    'route' => ['owner.settings.payment-setting', 'owner.settings.payment'],
                    'icon'  => 'payment-setting',
                ],
                [
                    'label' => 'Assets',
                    'href'  => '/owner/settings/assets',
                    'route' => ['owner.settings.assets'],
                    'icon'  => 'assets',
                ],
                [
                    'label' => 'Integrations',
                    'href'  => '/owner/settings/integrations',
                    'route' => ['owner.settings.integrations'],
                    'icon'  => 'integrations',
                ],
                [
                    'label' => 'Subscription',
                    'href'  => '/owner/subscription',
                    'route' => ['owner.subscription'],
                    'icon'  => 'subscription',
                ],
            ],
        ],
        [
            'title' => null, // Standalone bottom product feature
            'items' => [
                [
                    'label' => 'Landing Page',
                    'href'  => '/owner/landing-page',
                    'route' => ['owner.landing-page', 'owner.landing-page.store'],
                    'icon'  => 'landing-page',
                    'badge' => 'PRO',
                ],
            ],
        ],
    ];

    // Detect which category contains the active page
    $activeSectionTitle = null;
    foreach ($sections as $sec) {
        if ($sec['title']) {
            foreach ($sec['items'] as $it) {
                if ($isActive($it['href'], $it['route'])) {
                    $activeSectionTitle = $sec['title'];
                    break 2;
                }
            }
        }
    }
@endphp

<aside
    x-data="{
        collapsed: JSON.parse(localStorage.getItem('bookqu_collapsed_sections') || '{}'),
        activeSection: '{{ $activeSectionTitle }}',
        init() {
            // Ensure the category containing active page is ALWAYS open and NEVER stored as collapsed
            if (this.activeSection) {
                delete this.collapsed[this.activeSection];
                try {
                    localStorage.setItem('bookqu_collapsed_sections', JSON.stringify(this.collapsed));
                } catch (e) {}
            }
        },
        isOpen(title) {
            if (!title) return true;
            // Active category is ALWAYS open and cannot be closed
            if (title === this.activeSection) return true;
            return !this.collapsed[title];
        },
        toggleSection(title) {
            // If it's the active category, it is locked open — do NOT toggle or record collapsed state
            if (title === this.activeSection) {
                return;
            }
            this.collapsed[title] = !this.collapsed[title];
            // Ensure activeSection is never saved as true
            if (this.activeSection) {
                delete this.collapsed[this.activeSection];
            }
            try {
                localStorage.setItem('bookqu_collapsed_sections', JSON.stringify(this.collapsed));
            } catch (e) {}
        }
    }"
    :class="sidebaropen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 flex h-screen max-h-screen w-64 flex-col border-r border-[#E2E8F0] bg-white transition-transform duration-300 ease-in-out lg:translate-x-0 select-none shadow-xs"
    id="sidebar-nav"
    style="height: 100vh; max-height: 100vh; display: flex; flex-direction: column; overflow: hidden;"
>
    <!-- Brand Header (Fixed at Top) -->
    <div class="flex items-center gap-3 px-5 py-4 border-b border-[#F1F5F9] shrink-0 bg-white">
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#4F46E5] text-white shadow-xs">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
        </div>
        <div class="min-w-0 flex-1">
            <h1 class="text-base font-extrabold text-[#0F172A] tracking-tight leading-tight truncate">BookQu</h1>
            <p class="text-[11px] font-medium text-[#64748B]">Admin Portal</p>
        </div>
    </div>

    <!-- Scrollable Navigation Container -->
    <nav
        class="flex-1 min-h-0 overflow-y-auto px-3 py-3 space-y-3 sidebar-scroll"
        style="flex: 1 1 0%; min-height: 0; overflow-y: auto; overflow-x: hidden; overscroll-behavior: contain;"
        id="sidebar-scrollable-nav"
    >
        @foreach ($sections as $sectionIndex => $section)
            @if ($sectionIndex === count($sections) - 1 && !$section['title'])
                <div class="pt-2 border-t border-[#F1F5F9]"></div>
            @endif
            <div class="space-y-1">
                @if ($section['title'])
                    @php
                        $isActiveSection = ($section['title'] === $activeSectionTitle);
                    @endphp
                    {{-- Collapsible Category Header Button --}}
                    <button
                        type="button"
                        @click="toggleSection('{{ $section['title'] }}')"
                        class="group flex w-full items-center justify-between px-3 py-2 rounded-xl text-[11px] font-bold uppercase tracking-wider transition-all duration-200 cursor-pointer"
                        :class="isOpen('{{ $section['title'] }}')
                            ? '{{ $isActiveSection ? 'bg-[#EEF2FF]/60 text-[#4F46E5]' : 'bg-[#F8FAFC] text-[#0F172A]' }}'
                            : 'text-[#94A3B8] hover:text-[#0F172A] hover:bg-[#F8FAFC]'"
                        :aria-expanded="isOpen('{{ $section['title'] }}')"
                        id="category-btn-{{ \Illuminate\Support\Str::slug($section['title']) }}"
                    >
                        <span>{{ $section['title'] }}</span>

                        {{-- Smooth Arrow Animation: Down when minimized (rotate-0), Up when expanded (rotate-180) --}}
                        <svg
                            class="h-4 w-4 transition-transform duration-300 ease-in-out shrink-0"
                            :class="isOpen('{{ $section['title'] }}') ? 'rotate-180 text-[#4F46E5]' : 'rotate-0 text-[#94A3B8] group-hover:text-[#475569]'"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2.2"
                        >
                            {{-- Base shape points DOWN: rotate-0 = DOWN (minimized), rotate-180 = UP (expanded) --}}
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                @endif

                {{-- Category Menu Items List with Smooth Slide & Fade Transition --}}
                <div
                    @if ($section['title'])
                        x-show="isOpen('{{ $section['title'] }}')"
                        x-transition:enter="transition-all ease-out duration-250"
                        x-transition:enter-start="opacity-0 max-h-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 max-h-[600px] translate-y-0"
                        x-transition:leave="transition-all ease-in duration-200"
                        x-transition:leave-start="opacity-100 max-h-[600px] translate-y-0"
                        x-transition:leave-end="opacity-0 max-h-0 -translate-y-1"
                        x-cloak
                    @endif
                    class="space-y-0.5 overflow-hidden"
                >
                    @foreach ($section['items'] as $item)
                        @php
                            $active = $isActive($item['href'], $item['route']);
                        @endphp
                        <a
                            href="{{ $item['href'] }}"
                            class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs sm:text-[13px] font-medium transition-all duration-150 {{ $active
                                ? 'bg-[#EEF2FF] text-[#4F46E5] font-semibold shadow-2xs'
                                : 'text-[#475569] hover:bg-[#F8FAFC] hover:text-[#0F172A]' }}"
                            id="nav-{{ \Illuminate\Support\Str::slug($item['label']) }}"
                        >
                            <span class="shrink-0 transition-colors {{ $active ? 'text-[#4F46E5]' : 'text-[#64748B] group-hover:text-[#0F172A]' }}">
                                @switch($item['icon'])
                                    @case('dashboard')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                        </svg>
                                        @break

                                    @case('calendar')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        @break

                                    @case('schedule')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        @break

                                    @case('bookings')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                        </svg>
                                        @break

                                    @case('services')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                        </svg>
                                        @break

                                    @case('categories')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                        </svg>
                                        @break

                                    @case('staff-resources')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                        @break

                                    @case('additional-items')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        @break

                                    @case('customers')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                        </svg>
                                        @break

                                    @case('vouchers')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                        @break

                                    @case('reviews')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                        </svg>
                                        @break

                                    @case('analytics')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        @break

                                    @case('schedule-report')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        @break

                                    @case('business-setting')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        @break

                                    @case('appearance-setting')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4 5 5 0 014-4h4a4 4 0 014 4 5 5 0 01-4 4H7zm0 0l2.5-5.5m7-10.5a3.5 3.5 0 115 5L12 21l-4.5-1 1-4.5 9.5-9.5z"/>
                                        </svg>
                                        @break

                                    @case('payment-setting')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                        </svg>
                                        @break

                                    @case('assets')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        @break

                                    @case('balance')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        @break

                                    @case('integrations')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                        @break

                                    @case('subscription')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                        </svg>
                                        @break

                                    @case('landing-page')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                        </svg>
                                        @break
                                @endswitch
                            </span>
                            <span class="truncate">{{ $item['label'] }}</span>

                            @if (!empty($item['badge']))
                                <span class="ml-auto inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-extrabold tracking-wider uppercase {{ $active ? 'bg-[#4F46E5] text-white' : 'bg-[#EEF2FF] text-[#4F46E5] group-hover:bg-[#E0E7FF]' }}">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <!-- Bottom Section: Upgrade Card & Account Actions (Fixed at Bottom) -->
    <div class="p-3 border-t border-[#F1F5F9] space-y-2.5 shrink-0 bg-white">
        {{-- Subscription Status Card --}}
        <div class="rounded-2xl border border-[#E2E8F0] bg-[#FAFAFC] p-3.5 shadow-2xs">
            <div class="flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#EEF2FF] text-[#4F46E5] shrink-0">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5">
                        <p class="text-xs font-bold text-[#0F172A]">Active</p>
                    </div>
                    <p class="text-[11px] font-medium text-[#64748B]">{{ $daysLeft }} days left</p>
                </div>
            </div>

            <a
                href="{{ route('owner.subscription') }}"
                class="mt-3 flex w-full items-center justify-center rounded-xl bg-[#4F46E5] hover:bg-[#4338CA] py-2 px-3 text-xs font-bold text-white shadow-xs transition-all active:scale-98"
                id="btn-sidebar-upgrade"
            >
                Upgrade now
            </a>
        </div>

        {{-- Logout --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="group flex w-full items-center justify-center gap-2 rounded-xl py-2 px-3 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-all cursor-pointer"
                id="nav-logout"
            >
                <svg class="h-3.5 w-3.5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span>Keluar Akun</span>
            </button>
        </form>
    </div>
</aside>

<script>
    // Universal wheel scrolling delegation: ensures wheel anywhere on the sidebar scrolls the nav list
    (function () {
        const sidebar = document.getElementById('sidebar-nav');
        const nav = document.getElementById('sidebar-scrollable-nav');
        if (sidebar && nav) {
            sidebar.addEventListener('wheel', function (e) {
                if (!e.target.closest('#sidebar-scrollable-nav')) {
                    nav.scrollTop += e.deltaY;
                }
            }, { passive: true });
        }
    })();
</script>
