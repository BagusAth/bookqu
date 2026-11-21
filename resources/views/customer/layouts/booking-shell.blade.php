<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Reservasi') — {{ $tenant->namabisnis ?? 'BookQu' }}</title>
    @unless (View::hasSection('minimal'))
        <link rel="icon" href="{{ asset('logo_icon.png') }}">
    @endunless
    @vite('resources/css/app.css')
    @yield('head')
    @isset ($branding)
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $branding['font']) }}:wght@400;500;600;700&display=swap" rel="stylesheet">
        {{-- Every value comes from StorefrontBranding, which normalises what the owner saved (design A §2–§3). --}}
        <style>
            .customer-shell {
                --sf-primary: {{ $branding['color'] }};
                --sf-primary-hover: {{ $branding['hover'] }};
                --sf-on-primary: {{ $branding['onPrimary'] }};
                --sf-on-primary-soft: {{ $branding['onPrimarySoft'] }};
                --sf-on-primary-faint: {{ $branding['onPrimaryFaint'] }};
                --sf-on-primary-line: {{ $branding['onPrimaryLine'] }};
                --sf-primary-text: {{ $branding['primaryText'] }};
                --sf-tint: {{ $branding['tint'] }};
                --sf-tint-strong: {{ $branding['tintStrong'] }};
                --sf-radius-control: {{ $branding['shape']['control'] }};
                --sf-radius-tile: {{ $branding['shape']['tile'] }};
                --sf-radius-area: {{ $branding['shape']['area'] }};
                --sf-radius-chip: {{ $branding['shape']['chip'] }};
                --sf-radius-card: {{ $branding['shape']['card'] }};
                --sf-radius-card-small: {{ $branding['shape']['cardSmall'] }};
                --sf-shadow-card: {{ $branding['surface']['card'] }};
                --sf-shadow-raised: {{ $branding['surface']['raised'] }};
                --sf-font: '{{ $branding['font'] }}';
                {{-- BQ-164 bridge for pages not yet rebuilt on design A; removed with the last of them. --}}
                --bq-primary: {{ $branding['color'] }};
                --bq-primary-hover: {{ $branding['hover'] }};
                --bq-primary-light: {{ $branding['light'] }};
                --bq-primary-border: {{ $branding['border'] }};
                --bq-on-primary: {{ $branding['onPrimary'] }};
                --bq-button-radius: {{ $branding['buttonRadius'] }};
                --bq-card-shadow: {{ $branding['cardShadow'] }};
                --bq-card-border: {{ $branding['cardBorder'] }};
            }
        </style>
    @endisset
</head>
<body class="customer-shell booking-page flex min-h-screen flex-col bg-sf-page text-sf-text antialiased" @isset($branding) data-branded data-button-style="{{ $branding['buttonStyle'] }}" data-card-style="{{ $branding['cardStyle'] }}" @endisset @yield('body_attributes')>
    <header class="no-print sticky top-0 z-40 border-b border-sf-divider bg-sf-surface">
        <div class="booking-shell mx-auto flex w-full max-w-7xl items-center justify-between gap-2 px-4 py-3 sm:gap-4 sm:px-6">
            <a href="{{ $storefrontUrl ?? url('/') }}" class="flex min-w-0 items-center gap-3 rounded-sf-card-small">
                @if ($businessLogo ?? null)
                    <img src="{{ $businessLogo }}" alt="" class="size-10 shrink-0 rounded-sf-card-small object-cover">
                @else
                    <span aria-hidden="true" class="grid size-10 shrink-0 place-items-center rounded-sf-card-small bg-sf-primary font-semibold text-sf-on-primary">{{ $businessInitials ?? 'BQ' }}</span>
                @endif
                <span class="truncate font-semibold" title="{{ $tenant->namabisnis ?? 'BookQu' }}">{{ $tenant->namabisnis ?? 'BookQu' }}</span>
            </a>
            <div class="flex shrink-0 items-center gap-1 sm:gap-2">
                @hasSection('back_url')
                    <x-ui.button :href="trim($__env->yieldContent('back_url'))" icon="chevron-left" :aria-label="$__env->yieldContent('back_label', 'Kembali')" class="min-h-11">
                        <span class="hidden sm:inline">@yield('back_label', 'Kembali')</span>
                    </x-ui.button>
                @endif
                <x-customer.help-link :href="$merchantWaUrl ?? null" />
                @yield('header_actions')
            </div>
        </div>
    </header>

    @if (isset($tenant) && request()->routeIs('customer.booking.program', 'customer.booking.slug.program'))
        @if ($businessBanner ?? null)
            <img src="{{ $businessBanner }}" alt="Banner {{ $tenant->namabisnis }}" class="no-print h-40 w-full object-cover sm:h-56">
        @endif
        @if (filled($tenant->deskripsi))
            <section class="no-print border-b border-sf-divider bg-sf-surface">
                <p class="mx-auto max-w-7xl whitespace-pre-line px-4 py-3 text-sm text-sf-muted sm:px-6">{{ $tenant->deskripsi }}</p>
            </section>
        @endif
    @endif

    @hasSection('current_step')
        <div class="no-print border-b border-sf-divider bg-sf-surface px-4 py-3 sm:px-6">
            <div class="mx-auto max-w-7xl">
                <x-customer.progress-header :current="intval($__env->yieldContent('current_step', 1))" />
            </div>
        </div>
    @endif

    <main class="booking-shell mx-auto w-full max-w-7xl grow space-y-4 px-4 py-6 sm:px-6 sm:py-8 @unless(View::hasSection('minimal')) pb-28 sm:pb-24 @endunless">
        @unless (View::hasSection('minimal'))
            @if (session('info'))
                <x-ui.banner :title="session('info')" />
            @endif
            @if (session('success'))
                <x-ui.banner :title="session('success')" icon="check" />
            @endif
            @if (session('error'))
                <x-ui.banner :title="session('error')" tone="danger" />
            @endif
        @endunless
        @yield('content')
    </main>

    <footer class="no-print mt-auto border-t border-sf-divider px-4 py-5 text-center text-sm text-sf-muted">
        © {{ date('Y') }} {{ $tenant->namabisnis ?? 'BookQu' }} · Didukung oleh BookQu
    </footer>

    @unless (View::hasSection('minimal'))
        <script>
            window.addEventListener('pageshow', event => {
                if (event.persisted) {
                    window.location.reload();
                    return;
                }
                window.dispatchEvent(new CustomEvent('booking-reset-submitting'));
            });
        </script>
    @endunless
    @yield('scripts')
    @unless (View::hasSection('minimal'))
        @vite('resources/js/customer.js')
    @endunless
</body>
</html>
