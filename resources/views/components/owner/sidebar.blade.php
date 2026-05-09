{{-- Sidebar Navigation Component --}}
@php
    $menuitems = [
        ['label' => 'Dashboard', 'href' => '/owner/dashboard', 'ikon' => 'dashboard'],
        ['label' => 'Programs', 'href' => '/owner/programs', 'ikon' => 'programs'],
        ['label' => 'Schedule', 'href' => '/owner/schedule', 'ikon' => 'schedule'],
        ['label' => 'Bookings', 'href' => '/owner/bookings', 'ikon' => 'bookings'],
        ['label' => 'Analytics', 'href' => '/owner/analytics', 'ikon' => 'analytics'],
        ['label' => 'Subscription', 'href' => '/owner/subscription', 'ikon' => 'subscription'],
    ];

    $halamanaktif = request()->path();
@endphp

<aside
    :class="sidebaropen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-bq-border bg-bq-sidebar transition-transform duration-300 ease-in-out lg:translate-x-0"
    id="sidebar-nav"
>
    <!-- Logo -->
    <div class="flex items-center gap-3 px-6 py-5">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-bq-primary shadow-md shadow-bq-primary/25">
            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
        </div>
        <div>
            <h1 class="text-sm font-bold text-bq-text">BookQu Admin</h1>
            <p class="text-xs text-bq-text-subtle">Owner Portal</p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="mt-2 flex-1 space-y-1 px-3">
        @foreach ($menuitems as $item)
            @php
                $aktif = str_contains($halamanaktif, ltrim($item['href'], '/'));
            @endphp
            <a
                href="{{ $item['href'] }}"
                class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200
                    {{ $aktif
                        ? 'bg-bq-sidebar-active text-bq-sidebar-active-text shadow-sm'
                        : 'text-bq-sidebar-text hover:bg-bq-background hover:text-bq-text'
                    }}"
                id="nav-{{ strtolower($item['label']) }}"
            >
                @switch($item['ikon'])
                    @case('dashboard')
                        <svg class="h-[18px] w-[18px] {{ $aktif ? 'text-bq-primary' : 'text-bq-text-subtle group-hover:text-bq-text-muted' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 12a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z"/>
                        </svg>
                        @break
                    @case('programs')
                        <svg class="h-[18px] w-[18px] {{ $aktif ? 'text-bq-primary' : 'text-bq-text-subtle group-hover:text-bq-text-muted' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        @break
                    @case('schedule')
                        <svg class="h-[18px] w-[18px] {{ $aktif ? 'text-bq-primary' : 'text-bq-text-subtle group-hover:text-bq-text-muted' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        @break
                    @case('bookings')
                        <svg class="h-[18px] w-[18px] {{ $aktif ? 'text-bq-primary' : 'text-bq-text-subtle group-hover:text-bq-text-muted' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        @break
                    @case('analytics')
                        <svg class="h-[18px] w-[18px] {{ $aktif ? 'text-bq-primary' : 'text-bq-text-subtle group-hover:text-bq-text-muted' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        @break
                    @case('subscription')
                        <svg class="h-[18px] w-[18px] {{ $aktif ? 'text-bq-primary' : 'text-bq-text-subtle group-hover:text-bq-text-muted' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        @break
                @endswitch
                {{ $item['label'] }}
            </a>
        @endforeach

        {{-- Landing Page — hanya muncul untuk paket Pro --}}
        @if ($adalahpro ?? false)
            @php
                $aktiflandingpage = str_contains($halamanaktif, 'owner/landing-page');
            @endphp
            <div class="mt-3 pt-3 border-t border-bq-border/50">
                <p class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-wider text-bq-text-subtle">Pro Features</p>
                <a
                    href="/owner/landing-page"
                    class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200
                        {{ $aktiflandingpage
                            ? 'bg-bq-sidebar-active text-bq-sidebar-active-text shadow-sm'
                            : 'text-bq-sidebar-text hover:bg-bq-background hover:text-bq-text'
                        }}"
                    id="nav-landing-page"
                >
                    <svg class="h-[18px] w-[18px] {{ $aktiflandingpage ? 'text-bq-primary' : 'text-bq-text-subtle group-hover:text-bq-text-muted' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                    </svg>
                    Landing Page
                    <span class="ml-auto inline-flex items-center rounded-full bg-violet-500/15 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-violet-400">
                        Pro
                    </span>
                </a>
            </div>
        @endif
    </nav>

    <!-- Bottom: Settings -->
    <div class="border-t border-bq-border px-3 py-3">
        <a href="/owner/settings" class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-bq-sidebar-text transition-all duration-200 hover:bg-bq-background hover:text-bq-text {{ str_contains($halamanaktif, 'owner/settings') ? 'bg-bq-sidebar-active text-bq-sidebar-active-text shadow-sm' : '' }}" id="nav-settings">
            <svg class="h-[18px] w-[18px] text-bq-text-subtle group-hover:text-bq-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Settings
        </a>
        <form method="POST" action="{{ route('owner.logout') }}" class="mt-2">
            @csrf
            <button type="submit" class="group flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-bq-sidebar-text transition-all duration-200 hover:bg-bq-background hover:text-bq-text" id="nav-logout">
                <svg class="h-[18px] w-[18px] text-bq-text-subtle group-hover:text-bq-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
                </svg>
                Logout
            </button>
        </form>
    </div>
</aside>

