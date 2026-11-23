{{--
    Platform admin shell: one header with the admin navigation, flash messages and the page content.
    Built from the shared bq-* tokens and x-ui primitives; access is decided by the `role:admin` route middleware.
--}}
@php
    $adminNavigation = [
        ['label' => 'Ringkasan', 'href' => route('admin.dashboard'), 'icon' => 'dashboard', 'active' => request()->routeIs('admin.dashboard')],
        ['label' => 'Withdraw', 'href' => route('admin.payouts'), 'icon' => 'wallet', 'active' => request()->routeIs('admin.payouts*')],
        ['label' => 'Refund', 'href' => route('admin.refunds'), 'icon' => 'card', 'active' => request()->routeIs('admin.refunds*')],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', 'Admin') — BookQu</title>

    <link rel="icon" type="image/png" href="{{ asset('logo_icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bq-owner bg-bq-background font-owner text-bq-text antialiased">
    <header class="border-b border-bq-border bg-bq-surface">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3 md:px-6">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                <img src="{{ asset('logo_icon.png') }}" alt="" class="size-7">
                <span class="font-semibold text-bq-ink">BookQu</span>
                <x-ui.badge tone="brand">Admin platform</x-ui.badge>
            </a>

            <nav aria-label="Navigasi admin" class="order-last -mx-1 flex w-full gap-1 overflow-x-auto md:order-none md:w-auto [scrollbar-width:none]">
                @foreach ($adminNavigation as $item)
                    <a
                        href="{{ $item['href'] }}"
                        @if ($item['active']) aria-current="page" @endif
                        @class([
                            'flex h-9 shrink-0 items-center gap-2 rounded-bq-control px-3 text-bq-body',
                            'bg-bq-sidebar-active font-semibold text-bq-sidebar-active-text' => $item['active'],
                            'font-medium text-bq-text-muted hover:bg-bq-background hover:text-bq-text' => ! $item['active'],
                        ])
                    >
                        <x-owner.icon :name="$item['icon']" class="size-4 shrink-0" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                @csrf
                <x-ui.button type="submit" variant="ghost" size="sm" icon="logout">Keluar</x-ui.button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-6xl space-y-6 px-4 py-6 md:px-6 md:py-8">
        @if (session('sukses'))
            <x-ui.banner :title="session('sukses')" icon="check" />
        @endif
        @if ($errors->any())
            <x-ui.banner tone="danger" title="Permintaan belum diproses" :description="$errors->first()" />
        @endif

        @yield('content')
    </main>
</body>
</html>
