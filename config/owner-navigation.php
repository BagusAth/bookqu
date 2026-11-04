<?php

return [
    ['id' => 'dashboard', 'label' => 'Beranda', 'route' => 'owner.dashboard', 'module' => null, 'feature' => null, 'ability' => 'dashboard.view', 'group' => null, 'icon' => 'dashboard'],
    ['id' => 'calendar', 'label' => 'Kalender', 'route' => 'owner.calendar', 'module' => null, 'feature' => null, 'ability' => 'calendar.view', 'group' => null, 'icon' => 'calendar'],
    ['id' => 'bookings', 'label' => 'Booking', 'route' => 'owner.bookings', 'module' => null, 'feature' => null, 'ability' => 'bookings.view', 'group' => null, 'icon' => 'bookings'],
    ['id' => 'customers', 'label' => 'Pelanggan', 'route' => 'owner.customers', 'module' => null, 'feature' => null, 'ability' => 'customers.view', 'group' => null, 'icon' => 'customers'],
    ['id' => 'services', 'label' => 'Layanan', 'route' => 'owner.services', 'module' => null, 'feature' => null, 'ability' => 'services.manage', 'group' => 'Bisnis saya', 'icon' => 'services'],
    ['id' => 'units', 'label' => 'Unit', 'route' => 'owner.units', 'module' => 'units', 'feature' => 'units', 'ability' => 'units.manage', 'group' => 'Bisnis saya', 'icon' => 'staff-resources'],
    ['id' => 'schedule', 'label' => 'Jam Buka & Harga', 'route' => 'owner.schedule', 'module' => null, 'feature' => null, 'ability' => 'schedule.manage', 'group' => 'Bisnis saya', 'icon' => 'calendar'],
    ['id' => 'booking-page', 'label' => 'Halaman Booking', 'route' => 'owner.booking-page', 'module' => 'booking-page', 'feature' => null, 'ability' => 'booking-page.manage', 'group' => 'Pemasaran', 'icon' => 'landing-page'],
    ['id' => 'promos', 'label' => 'Promo', 'route' => 'owner.promos', 'module' => 'promos', 'feature' => 'promos', 'ability' => 'promos.manage', 'group' => 'Pemasaran', 'icon' => 'vouchers'],
    ['id' => 'reviews', 'label' => 'Ulasan', 'route' => 'owner.reviews', 'module' => null, 'feature' => null, 'ability' => 'reviews.manage', 'group' => 'Pemasaran', 'icon' => 'reviews'],
    ['id' => 'payments', 'label' => 'Pembayaran', 'route' => 'owner.settings.balance', 'module' => 'balance', 'feature' => null, 'ability' => 'payouts.manage', 'group' => 'Keuangan', 'icon' => 'balance'],
    ['id' => 'reports', 'label' => 'Laporan', 'route' => 'owner.analytics', 'module' => 'reports', 'feature' => 'analytics', 'ability' => 'reports.view', 'group' => 'Keuangan', 'icon' => 'analytics'],
    ['id' => 'settings', 'label' => 'Pengaturan', 'route' => 'owner.settings', 'module' => null, 'feature' => null, 'ability' => 'account.manage', 'group' => 'Lainnya', 'icon' => 'settings'],
    ['id' => 'subscription', 'label' => 'Paket & Tagihan', 'route' => 'owner.subscription', 'module' => null, 'feature' => null, 'ability' => 'subscription.manage', 'group' => 'Lainnya', 'icon' => 'subscription'],
    ['id' => 'help', 'label' => 'Bantuan', 'route' => 'owner.help', 'module' => 'help', 'feature' => null, 'ability' => 'help.view', 'group' => 'Lainnya', 'icon' => 'settings'],
];
