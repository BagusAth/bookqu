<section class="bg-[#4F46E5]">
    <div class="mx-auto max-w-6xl px-6 py-16 text-center text-white lg:px-8">
        <h2 class="text-3xl font-semibold">Siap Mengembangkan Bisnis Anda?</h2>
        <p class="mt-3 text-sm text-indigo-100">
            Bergabunglah dengan pemilik bisnis yang telah beralih ke BookQu dan hemat hingga 15 jam kerja setiap minggunya.
        </p>
        @auth
            @if (auth()->user()->isOwner())
                @if (auth()->user()->hasVerifiedEmail())
                    <a href="{{ route('owner.dashboard') }}" class="mt-6 inline-flex items-center justify-center rounded-xl bg-white px-6 py-3 text-sm font-semibold text-[#4F46E5] shadow-sm transition hover:bg-[#EEF2FF]">
                        Masuk ke Dashboard
                    </a>
                @else
                    <a href="{{ route('verification.notice') }}" class="mt-6 inline-flex items-center justify-center rounded-xl bg-amber-400 px-6 py-3 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-amber-300">
                        Verifikasi Email Anda
                    </a>
                @endif
            @elseif (auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="mt-6 inline-flex items-center justify-center rounded-xl bg-white px-6 py-3 text-sm font-semibold text-[#4F46E5] shadow-sm transition hover:bg-[#EEF2FF]">
                    Masuk ke Dashboard Admin
                </a>
            @else
                <a href="{{ route('register') }}" class="mt-6 inline-flex items-center justify-center rounded-xl bg-white px-6 py-3 text-sm font-semibold text-[#4F46E5] shadow-sm transition hover:bg-[#EEF2FF]">
                    Daftar Sekarang - Gratis 7 Hari
                </a>
            @endif
        @else
            <a href="{{ route('register') }}" class="mt-6 inline-flex items-center justify-center rounded-xl bg-white px-6 py-3 text-sm font-semibold text-[#4F46E5] shadow-sm transition hover:bg-[#EEF2FF]">
                Daftar Sekarang - Gratis 7 Hari
            </a>
        @endauth
    </div>
</section>
