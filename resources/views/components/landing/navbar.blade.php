<header class="landing-navbar sticky top-0 z-50 border-b border-slate-200 bg-white">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6 lg:px-8">
        <a href="/" class="text-lg font-extrabold text-blue-600">BookQu</a>
        <nav class="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex">
            <a href="#hero" data-scroll-target="hero" class="nav-link text-slate-600">Solutions</a>
            <a href="#features" data-scroll-target="features" class="nav-link text-slate-600">Features</a>
            <a href="#pricing" data-scroll-target="pricing" class="nav-link text-slate-600">Pricing</a>
            <a href="#about" data-scroll-target="about" class="nav-link text-slate-600">About</a>
        </nav>
        <div class="flex items-center gap-4">
            @php($user = auth()->user())
            @if (!$user || !$user->role || $user->isCustomer())
                <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 transition hover:text-blue-600 hover:font-semibold">Login</a>
                <a href="{{ route('register') }}" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    Get Started
                </a>
            @elseif ($user->isOwner())
                <a href="{{ url('/owner/dashboard') }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h7v7H3V3zm11 0h7v7h-7V3zM3 14h7v7H3v-7zm11 0h7v7h-7v-7z" />
                    </svg>
                    <span>Dashboard</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-rose-500 px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 hover:text-rose-600">
                        Logout
                    </button>
                </form>
            @elseif ($user->isAdmin())
                <a href="{{ url('/admin/dashboard') }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h7v7H3V3zm11 0h7v7h-7V3zM3 14h7v7H3v-7zm11 0h7v7h-7v-7z" />
                    </svg>
                    <span>Dashboard</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-rose-500 px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 hover:text-rose-600">
                        Logout
                    </button>
                </form>
            @endif
        </div>
    </div>
</header>
