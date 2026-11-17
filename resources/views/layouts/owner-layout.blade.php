<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="BookQu - Kelola bisnis booking Anda">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#382186">

    <title>@yield('title', 'Beranda') — BookQu</title>

    <link rel="icon" type="image/png" href="{{ asset('logo_icon.png') }}">

    {{-- Plus Jakarta Sans, only the weights the owner UI uses (BQ-090, BQ-161) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Shared owner blocks register their Alpine behaviour before Alpine starts (BQ-129) --}}
    <script defer src="{{ asset('js/owner-ui.js') }}"></script>

    {{-- Page-specific assets, e.g. Chart.js on the pages that draw charts (BQ-090) --}}
    @stack('head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bq-owner bg-bq-background font-owner text-bq-text antialiased">
    @php $shell ??= ['ready' => false]; @endphp

    <div class="flex min-h-screen">
        @if ($shell['ready'])
            @include('components.owner.sidebar')
        @endif

        <main class="flex min-w-0 flex-1 flex-col {{ $shell['ready'] ? 'md:ml-bq-rail owner-wide:ml-bq-sidebar' : '' }}">
            @if ($shell['ready'])
                @include('components.owner.topbar')
            @else
                <header class="flex h-16 items-center justify-between border-b border-bq-border bg-bq-surface px-4">
                    <span class="text-sm font-semibold">BookQu</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-bq-text-muted hover:text-bq-text">Keluar</button>
                    </form>
                </header>
            @endif

            {{-- Bottom padding keeps content above the phone navigation bar (owner-portal.md Â§4.3) --}}
            <div class="min-w-0 flex-1 px-3 pt-4 pb-24 md:px-5 md:pt-6 md:pb-10 owner-wide:px-bq-edge">
                @unless (request()->routeIs('owner.calendar'))
                    <x-owner.walkin-success />
                @endunless
                {{-- Flash messages, including `pesan`, show once in the toast (BQ-129) --}}
                @yield('content')
            </div>
        </main>
    </div>

    @if ($shell['ready'])
        @include('components.owner.bottom-nav')

        @if ($shell['canWalkIn'])
            <x-owner.dialog id="owner-walkin-dialog" title="Catat booking" description="Catat pelanggan yang datang langsung." size="lg" reopen-for="shell-walkin">
                <x-owner.walkin-form :slots-url="route('owner.bookings.walkin-slots')" :search-url="route('owner.search')" id-prefix="shell-walkin" />
            </x-owner.dialog>
        @endif

        {{-- The shared upgrade prompt a locked menu opens (owner-portal.md Â§3 rule 6) --}}
        <x-owner.dialog id="owner-upgrade-dialog" title="Fitur paket lebih tinggi" size="sm">
            <div x-data="{ feature: '' }" x-on:open-upgrade.window="feature = $event.detail.feature">
                <p class="text-sm text-bq-text"><span class="font-semibold" x-text="feature"></span> belum termasuk paket Anda.</p>
                <p class="mt-1 text-sm text-bq-text-muted">Naikkan paket untuk memakai fitur ini. Data dan pengaturan Anda yang lain tidak berubah.</p>
                <a href="{{ route('owner.subscription') }}" class="mt-4 inline-flex items-center rounded-lg bg-bq-primary px-4 py-2 text-sm font-medium text-white hover:bg-bq-primary-hover">Lihat paket</a>
            </div>
        </x-owner.dialog>
    @endif

    @include('components.owner.toast')
    @include('components.owner.confirm-modal')
    {{-- The one booking panel and customer panel every page opens (owner-portal.md rule 3, AC-PNL-06) --}}
    <x-owner.booking-panel />
    <x-owner.modal-reschedule-booking />
    <x-owner.customer-panel :can-walk-in="(bool) ($shell['ready'] && $shell['canWalkIn'])" />
    @include('components.owner.session-expired')
</body>
</html>
