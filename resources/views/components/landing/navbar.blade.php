<header class="landing-navbar sticky top-0 z-50 border-b border-slate-200 bg-white" x-data="{ mobileNavOpen: false }">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6 lg:px-8">
        <a href="/" class="flex items-center rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F46E5]">
            <img src="{{ asset('images/logo.png') }}" alt="BookQu Logo" class="h-8 w-auto" />
        </a>

        <!-- Desktop Navigation -->
        <nav class="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex">
            <a href="{{ url('/#hero') }}" data-scroll-target="hero" class="nav-link text-slate-600 hover:text-[#4F46E5] transition-colors rounded-lg px-1.5 py-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F46E5]">Solutions</a>
            <a href="{{ url('/#features') }}" data-scroll-target="features" class="nav-link text-slate-600 hover:text-[#4F46E5] transition-colors rounded-lg px-1.5 py-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F46E5]">Features</a>
            <a href="{{ url('/#pricing') }}" data-scroll-target="pricing" class="nav-link text-slate-600 hover:text-[#4F46E5] transition-colors rounded-lg px-1.5 py-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F46E5]">Pricing</a>
            <a href="{{ url('/#about') }}" data-scroll-target="about" class="nav-link text-slate-600 hover:text-[#4F46E5] transition-colors rounded-lg px-1.5 py-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F46E5]">About</a>
        </nav>

        <!-- Desktop Actions -->
        @php($user = auth()->user())
        <div class="hidden items-center gap-4 md:flex">
            @if (!$user || !$user->role || $user->isCustomer())
                <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 transition hover:text-[#4F46E5] hover:font-semibold rounded-lg px-2.5 py-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F46E5]">Login</a>
                <a href="{{ route('register') }}" class="rounded-xl bg-[#4F46E5] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#4338CA] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#4F46E5]">
                    Get Started
                </a>
            @elseif ($user->isOwner())
                @if ($user->hasVerifiedEmail())
                    <a href="{{ url('/owner/dashboard') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#4F46E5] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#4338CA] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#4F46E5]">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h7v7H3V3zm11 0h7v7h-7V3zM3 14h7v7H3v-7zm11 0h7v7h-7v-7z" />
                        </svg>
                        <span>Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('verification.notice') }}" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-amber-500" title="Verifikasi email Anda untuk mengakses dashboard">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Verifikasi Email</span>
                    </a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-rose-500 px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 hover:text-rose-600 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500">
                        Logout
                    </button>
                </form>
            @elseif ($user->isAdmin())
                <a href="{{ url('/admin/dashboard') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#4F46E5] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#4338CA] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#4F46E5]">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h7v7H3V3zm11 0h7v7h-7V3zM3 14h7v7H3v-7zm11 0h7v7h-7v-7z" />
                    </svg>
                    <span>Dashboard</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-rose-500 px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 hover:text-rose-600 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500">
                        Logout
                    </button>
                </form>
            @endif
        </div>

        <!-- Mobile Hamburger Button -->
        <div class="flex items-center gap-2 md:hidden">
            <button 
                type="button" 
                @click.stop="mobileNavOpen = !mobileNavOpen" 
                class="inline-flex items-center justify-center p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#4F46E5]" 
                aria-expanded="false"
                :aria-expanded="mobileNavOpen.toString()"
                aria-label="Menu Utama">
                <!-- Hamburger icon -->
                <svg x-show="!mobileNavOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <!-- Close icon -->
                <svg x-show="mobileNavOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile Drawer Menu -->
    <div 
        x-show="mobileNavOpen" 
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        @click.away="mobileNavOpen = false"
        class="md:hidden border-b border-slate-200 bg-white px-6 pt-2 pb-6 shadow-lg"
        style="display: none;">
        <nav class="flex flex-col space-y-2 py-3 text-base font-medium text-slate-700">
            <a href="{{ url('/#hero') }}" @click="mobileNavOpen = false" class="px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-[#4F46E5] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F46E5]">Solutions</a>
            <a href="{{ url('/#features') }}" @click="mobileNavOpen = false" class="px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-[#4F46E5] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F46E5]">Features</a>
            <a href="{{ url('/#pricing') }}" @click="mobileNavOpen = false" class="px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-[#4F46E5] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F46E5]">Pricing</a>
            <a href="{{ url('/#about') }}" @click="mobileNavOpen = false" class="px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-[#4F46E5] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F46E5]">About</a>
        </nav>
        <div class="mt-4 pt-4 border-t border-slate-100 flex flex-col gap-2.5">
            @if (!$user || !$user->role || $user->isCustomer())
                <a href="{{ route('login') }}" class="w-full text-center py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4F46E5]">
                    Login
                </a>
                <a href="{{ route('register') }}" class="w-full text-center py-2.5 rounded-xl bg-[#4F46E5] text-sm font-semibold text-white shadow-sm hover:bg-[#4338CA] transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#4F46E5]">
                    Get Started
                </a>
            @elseif ($user->isOwner())
                @if ($user->hasVerifiedEmail())
                    <a href="{{ url('/owner/dashboard') }}" class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#4F46E5] py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#4338CA] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#4F46E5]">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h7v7H3V3zm11 0h7v7h-7V3zM3 14h7v7H3v-7zm11 0h7v7h-7v-7z" />
                        </svg>
                        <span>Buka Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('verification.notice') }}" class="w-full flex items-center justify-center gap-2 rounded-xl bg-amber-500 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-amber-500">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Verifikasi Email Anda</span>
                    </a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="w-full text-center py-2.5 rounded-xl border border-rose-500 text-sm font-semibold text-rose-600 hover:bg-rose-50 transition cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500">
                        Logout
                    </button>
                </form>
            @elseif ($user->isAdmin())
                <a href="{{ url('/admin/dashboard') }}" class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#4F46E5] py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#4338CA] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#4F46E5]">
                    <span>Dashboard Admin</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="w-full text-center py-2.5 rounded-xl border border-rose-500 text-sm font-semibold text-rose-600 hover:bg-rose-50 transition cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500">
                        Logout
                    </button>
                </form>
            @endif
        </div>
    </div>
</header>
