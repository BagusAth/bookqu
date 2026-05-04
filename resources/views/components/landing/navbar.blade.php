<header class="sticky top-0 z-50 border-b border-slate-200 bg-white/80 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6 lg:px-8">
        <a href="/" class="text-lg font-extrabold text-blue-600">BookQu</a>
        <nav class="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex">
            <a href="#hero" onclick="event.preventDefault(); document.getElementById('hero').scrollIntoView({ behavior: 'smooth' });" class="transition hover:text-slate-900">Solutions</a>
            <a href="#features" onclick="event.preventDefault(); document.getElementById('features').scrollIntoView({ behavior: 'smooth' });" class="transition hover:text-slate-900">Features</a>
            <a href="#pricing" onclick="event.preventDefault(); document.getElementById('pricing').scrollIntoView({ behavior: 'smooth' });" class="transition hover:text-slate-900">Pricing</a>
            <a href="#about" onclick="event.preventDefault(); document.getElementById('about').scrollIntoView({ behavior: 'smooth' });" class="transition hover:text-slate-900">About</a>
        </nav>
        <div class="flex items-center gap-3">
            <a href="/login" class="text-sm font-semibold text-slate-600 transition hover:text-slate-900">Login</a>
            <a href="/dummy-register" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                Get Started
            </a>
        </div>
    </div>
</header>
