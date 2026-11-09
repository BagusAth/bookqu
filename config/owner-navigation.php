<?php

/*
| The one owner navigation definition (ADR-0021 decision 5, owner-portal.md §4.1).
| GetOwnerNavigation filters it through the module, entitlement and permission gates; the
| sidebar, the phone bottom bar and its "Lainnya" sheet render the result (BQ-128).
| `match` lists the route-name prefixes that mark the menu active; the longest prefix wins.
*/

return [
    ['id' => 'dashboard', 'label' => 'Beranda', 'route' => 'owner.dashboard', 'module' => null, 'feature' => null, 'ability' => 'dashboard.view', 'group' => null, 'icon' => 'dashboard', 'match' => ['owner.dashboard']],
    ['id' => 'calendar', 'label' => 'Kalender', 'route' => 'owner.calendar', 'module' => null, 'feature' => null, 'ability' => 'calendar.view', 'group' => null, 'icon' => 'calendar', 'match' => ['owner.calendar']],
    ['id' => 'bookings', 'label' => 'Booking', 'route' => 'owner.bookings', 'module' => null, 'feature' => null, 'ability' => 'bookings.view', 'group' => null, 'icon' => 'bookings', 'match' => ['owner.bookings', 'owner.refunds']],
    ['id' => 'customers', 'label' => 'Pelanggan', 'route' => 'owner.customers', 'module' => null, 'feature' => null, 'ability' => 'customers.view', 'group' => null, 'icon' => 'customers', 'match' => ['owner.customers']],
    ['id' => 'services', 'label' => 'Layanan', 'route' => 'owner.services', 'module' => null, 'feature' => null, 'ability' => 'services.manage', 'group' => 'Bisnis saya', 'icon' => 'services', 'match' => ['owner.services', 'owner.programs', 'owner.categories', 'owner.additional-items']],
    ['id' => 'units', 'label' => 'Unit', 'route' => 'owner.units', 'module' => 'units', 'feature' => 'units', 'ability' => 'units.manage', 'group' => 'Bisnis saya', 'icon' => 'units', 'match' => ['owner.units']],
    ['id' => 'schedule', 'label' => 'Jam Buka & Harga', 'route' => 'owner.schedule', 'module' => null, 'feature' => null, 'ability' => 'schedule.manage', 'group' => 'Bisnis saya', 'icon' => 'clock', 'match' => ['owner.schedule']],
    ['id' => 'booking-page', 'label' => 'Halaman Booking', 'route' => 'owner.booking-page', 'module' => 'booking-page', 'feature' => null, 'ability' => 'booking-page.manage', 'group' => 'Pemasaran', 'icon' => 'globe', 'match' => ['owner.booking-page', 'owner.landing-page']],
    ['id' => 'promos', 'label' => 'Promo', 'route' => 'owner.promos', 'module' => 'promos', 'feature' => 'promos', 'ability' => 'promos.manage', 'group' => 'Pemasaran', 'icon' => 'tag', 'match' => ['owner.promos']],
    ['id' => 'reviews', 'label' => 'Ulasan', 'route' => 'owner.reviews', 'module' => null, 'feature' => null, 'ability' => 'reviews.manage', 'group' => 'Pemasaran', 'icon' => 'star', 'match' => ['owner.reviews']],
    ['id' => 'payments', 'label' => 'Pembayaran', 'route' => 'owner.settings.balance', 'module' => 'balance', 'feature' => null, 'ability' => 'payouts.manage', 'group' => 'Keuangan', 'icon' => 'wallet', 'match' => ['owner.settings.balance', 'owner.payouts', 'owner.payments']],
    ['id' => 'reports', 'label' => 'Laporan', 'route' => 'owner.analytics', 'module' => 'reports', 'feature' => 'analytics', 'ability' => 'reports.view', 'group' => 'Keuangan', 'icon' => 'chart', 'match' => ['owner.analytics', 'owner.reports', 'owner.schedule-report']],
    ['id' => 'settings', 'label' => 'Pengaturan', 'route' => 'owner.settings', 'module' => null, 'feature' => null, 'ability' => 'account.manage', 'group' => 'Lainnya', 'icon' => 'settings', 'match' => ['owner.settings', 'owner.team']],
    ['id' => 'subscription', 'label' => 'Paket & Tagihan', 'route' => 'owner.subscription', 'module' => null, 'feature' => null, 'ability' => 'subscription.manage', 'group' => 'Lainnya', 'icon' => 'card', 'match' => ['owner.subscription', 'owner.checkout']],
    ['id' => 'help', 'label' => 'Bantuan', 'route' => 'owner.help', 'module' => 'help', 'feature' => null, 'ability' => 'help.view', 'group' => 'Lainnya', 'icon' => 'help', 'match' => ['owner.help']],
];
