---
id: BQ-047
title: [BUG-T3-01] Seluruh Rute Form Submission & Navigasi Rusak Total pada Custom Domain
type: bug-fix
status: proposed
priority: P0
phase: 4
domain: tenancy
requirements: []
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-039]
source: docs/audit-bug/TAHAP-3-CUSTOMER-BOOKING-JOURNEY.md at a68ffe8
---

# BQ-047: [BUG-T3-01] Seluruh Rute Form Submission & Navigasi Rusak Total pada Custom Domain

Migrated verbatim from the 2026-10 audit (TAHAP-3-CUSTOMER-BOOKING-JOURNEY.md, stage 3: Customer Booking Journey: Storefront & 6-Langkah Reservasi hingga Checkout). Status in the current code is unverified — check it first (BQ-039).

> Confirmed 2026-10-30 (architecture review): 23 Blade call sites build `route(CustomerBookingRoutes::name(..), $tenant->slug)`; on a custom domain this yields `http://{slug}/booking/...` (slug used as host). Still open.

## Audit finding

- **Lokasi Berkas Terdampak**:
  - [`resources/views/customer/layouts/booking-shell.blade.php:78`](file:///c:/laragon/www/bookqu/resources/views/customer/layouts/booking-shell.blade.php#L78)
  - [`resources/views/customer/booking/program-selection.blade.php:13`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/program-selection.blade.php#L13)
  - [`resources/views/customer/booking/date-selection.blade.php:5,25`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/date-selection.blade.php#L5)
  - [`resources/views/customer/booking/time-selection.blade.php:5,25,142`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/time-selection.blade.php#L5)
  - [`resources/views/customer/booking/checkout.blade.php:5,59`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/checkout.blade.php#L5)
  - [`resources/views/customer/booking/payment.blade.php:5`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/payment.blade.php#L5)
  - [`resources/views/customer/partials/payment/cancel-modal.blade.php:66`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/payment/cancel-modal.blade.php#L66)
  - [`resources/views/customer/partials/payment/scripts.blade.php:5,6`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/payment/scripts.blade.php#L5)
  - [`resources/views/customer/booking/invoice.blade.php:5,335,345`](file:///c:/laragon/www/bookqu/resources/views/customer/booking/invoice.blade.php#L5)
  - [`resources/views/customer/partials/payment/failed-state.blade.php:35,42`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/payment/failed-state.blade.php#L35)
  - [`resources/views/customer/partials/payment/expired-state.blade.php:26`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/payment/expired-state.blade.php#L26)
  - [`resources/views/customer/manage/payment-show.blade.php:107`](file:///c:/laragon/www/bookqu/resources/views/customer/manage/payment-show.blade.php#L107)
  - [`resources/views/customer/manage/reschedule.blade.php:19`](file:///c:/laragon/www/bookqu/resources/views/customer/manage/reschedule.blade.php#L19)
  - [`resources/views/customer/partials/manage/header.blade.php:5,31`](file:///c:/laragon/www/bookqu/resources/views/customer/partials/manage/header.blade.php#L5)
- **Deskripsi & Dampak**:
  Rute custom domain di `routes/web.php:293` didefinisikan dengan parameter domain: `Route::domain('{custom_domain}')`. Ketika view memanggil `route(CustomerBookingRoutes::name(...), $tenant->slug)`, argumen pertama mengikat `{custom_domain}`, menghasilkan URL salah:
  `http://studio-brama/booking/select-program`
  Peramban gagal melakukan resolusi DNS (`DNS_PROBE_FINISHED_NXDOMAIN`). **Seluruh navigasi, submit formulir, tombol kembali, polling status, dan pembatalan di custom domain lumpuh 100%**.
- **Akar Masalah**:
  Helper `CustomerBookingRoutes::url(...)` telah dibuat khusus untuk mengatasi parameter binding ini, tetapi template Blade keliru menggunakan `route(CustomerBookingRoutes::name(...))`.
- **Rekomendasi Perbaikan**:
  Ganti seluruh pemanggilan di 14 berkas Blade tersebut dengan:
  ```blade
  \App\Support\CustomerBookingRoutes::url('nama.rute', [$tenant->slug, ...])
  ```

## Evidence

_Fill in when done._
