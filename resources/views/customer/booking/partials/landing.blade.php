{{--
    Landing page of a Pro business (BQ-154, FR-PUBLIC-007), shown at the top of the storefront home
    and in the owner preview. Data from App\Actions\Tenant\GetLandingPage; every value is escaped.
    Templates: modern (banner behind the headline), creative (banner beside it), clinic (quiet, light).
--}}

@php
    $template = $landing['template'];
    $sections = $landing['sections'];
@endphp

<section data-landing-template="{{ $template }}" aria-labelledby="landing-headline" class="mx-auto mb-8 w-full max-w-5xl space-y-6">
    @if ($template === 'creative')
        <div class="grid items-center gap-6 rounded-3xl bg-slate-900 p-6 text-white sm:grid-cols-2 sm:p-10">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-white/70">{{ $tenant->namabisnis }}</p>
                <h2 id="landing-headline" class="mt-2 text-3xl font-black leading-tight sm:text-4xl">{{ $landing['headline'] }}</h2>
                <a href="#booking-program-root" class="mt-5 inline-flex items-center rounded-full bg-[var(--bq-primary)] px-5 py-2.5 text-sm font-semibold text-white">Booking sekarang</a>
            </div>
            @if ($landing['bannerUrl'])
                <img src="{{ $landing['bannerUrl'] }}" alt="" class="aspect-[4/3] w-full rounded-2xl object-cover">
            @endif
        </div>
    @elseif ($template === 'clinic')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8">
            @if ($landing['bannerUrl'])
                <img src="{{ $landing['bannerUrl'] }}" alt="" class="mb-5 h-40 w-full rounded-xl object-cover sm:h-56">
            @endif
            <h2 id="landing-headline" class="text-2xl font-semibold text-slate-900 sm:text-3xl">{{ $landing['headline'] }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $tenant->namabisnis }}</p>
            <a href="#booking-program-root" class="mt-4 inline-flex items-center rounded-lg border border-[var(--bq-primary)] px-4 py-2 text-sm font-semibold text-[var(--bq-primary)]">Buat janji</a>
        </div>
    @else
        <div class="relative overflow-hidden rounded-3xl bg-slate-900 text-white">
            @if ($landing['bannerUrl'])
                <img src="{{ $landing['bannerUrl'] }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-50">
            @endif
            <div class="relative px-6 py-14 sm:px-10 sm:py-20">
                <p class="text-sm font-semibold text-white/80">{{ $tenant->namabisnis }}</p>
                <h2 id="landing-headline" class="mt-2 max-w-2xl text-3xl font-black leading-tight sm:text-5xl">{{ $landing['headline'] }}</h2>
                <a href="#booking-program-root" class="mt-6 inline-flex items-center rounded-xl bg-[var(--bq-primary)] px-5 py-3 text-sm font-semibold text-white">Booking sekarang</a>
            </div>
        </div>
    @endif

    @if ($sections !== [])
        <div class="grid gap-4 sm:grid-cols-3">
            @isset($sections['tentang'])
                <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:col-span-2">
                    <h3 class="text-sm font-semibold text-slate-900">Tentang kami</h3>
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $sections['tentang'] }}</p>
                </div>
            @endisset
            @isset($sections['kontak'])
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-slate-900">Lokasi &amp; kontak</h3>
                    @if ($sections['kontak']['address'] !== '')
                        <p class="mt-2 text-sm text-slate-600">{{ $sections['kontak']['address'] }}</p>
                    @endif
                    @if ($sections['kontak']['whatsappUrl'])
                        <a href="{{ $sections['kontak']['whatsappUrl'] }}" target="_blank" rel="noopener" class="mt-2 inline-block text-sm font-semibold text-[var(--bq-primary)]">WhatsApp {{ $sections['kontak']['phone'] }}</a>
                    @endif
                </div>
            @endisset
            @isset($sections['layanan'])
                <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:col-span-3">
                    <h3 class="text-sm font-semibold text-slate-900">Layanan unggulan</h3>
                    <ul class="mt-3 grid gap-3 sm:grid-cols-3">
                        @foreach ($sections['layanan'] as $service)
                            <li class="rounded-xl bg-slate-50 p-3">
                                <p class="text-sm font-semibold text-slate-900">{{ $service['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $service['price'] }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endisset
        </div>
    @endif
</section>
