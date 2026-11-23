---
id: BQ-095
title: Owner bookings page shows the wrong amount and payment state
type: bug-fix
status: rejected
priority: P1
phase: 8
domain: booking
requirements: [FR-BOOKING-008, FR-BOOKING-009, NFR-UX-005]
invariants: []
lane: light
approval: none
decision:
launch-blocker: false
depends-on: [BQ-087, BQ-092]
---

# BQ-095: Owner bookings page shows the wrong amount and payment state

> **Ditutup 2026-11-04 (BQ-126 Fase 9): dilebur ke [BQ-135](BQ-135-booking-page-and-panel.md).** Semua temuan di item ini menjadi bagian dari lingkup BQ-135.


Found in the owner UI audit of 2026-11-02.

## Problem

| # | Issue | Evidence |
|---|---|---|
| 1 | "Amount" is the service's **current** price, not what the booking costs: wrong after a price change, for weekend pricing and for each slot of a multi-slot booking | `resources/views/owner/partials/bookings/desktop-table.blade.php:99-101`, `mobile-cards.blade.php:22-23` (`$booking->layanan->harga`) |
| 2 | Payment state: checks `'expired'`, which payments never have; `gagal` shows as "Unpaid"; "Paid" is also derived from the booking status | `desktop-table.blade.php:104-118`, `detail-modal.blade.php:65-66` |
| 3 | The six summary cards repeat the six filter pills right below them (same counts, same links) | `partials/bookings/summary-stats.blade.php`, `partials/bookings/filters.blade.php` |
| 4 | Filter links put the search text into the URL without encoding (`?status=…&katakunci={{ $katakunci }}`), so `&` or `#` in a search breaks the filter; summary cards drop the search entirely | `filters.blade.php:16` |
| 5 | Row actions: "✓ Konfirmasi Lunas" runs without confirmation (an owner money action; it disappears with BQ-087), emoji plus an icon on "📅 Ubah Jadwal", the action menu is an absolutely positioned dropdown inside a horizontally scrolling table (risk of being clipped on the last rows; verify) | `desktop-table.blade.php:160-215` |
| 6 | The booking payload for the detail modal is built twice, once per partial | `desktop-table.blade.php` and `mobile-cards.blade.php` `@php` blocks |
| 7 | Staff/resource column and "General Staff / General Facility" placeholders show a hidden Tier 3 module (see BQ-097) | `desktop-table.blade.php:91-96`, `detail-modal.blade.php:120-124` |
| 8 | Inline flash duplicates the toast (BQ-092); title "Bookings Management" (BQ-093) | `owner/bookings.blade.php:15-34` |
| 9 | Calendar detail modal completes booking without confirmation: "✓ Selesai" immediately submits POST `/owner/bookings/{id}/status` with `status: 'completed'` without a confirm dialog; `completed` is an irreversible terminal state (INV-BOOK-02) | `resources/views/owner/partials/calendar/detail-modal.blade.php:250-257` (Added 2026-11-02 deep audit) |

## Plan

1. Show the booking's own price (the amount charged for that slot, or the payment total with "x slot" for a group) prepared in the controller/action, not `layanan->harga`. Bookings store no price; the charged total is `payments.jumlah`, and `Booking` already has a per-slot label from `schedules.harga_override` (`app/Models/Booking.php:171-176`) that ignores weekend pricing. Use the same calculation as checkout (`BookingRules::calculateSlotPrices`) or the payment total.
2. Payment column from `PaymentState` values and labels (`pending`, `sukses`, `gagal`) via BQ-092's status badge; owner-settled and walk-in shown as "Lunas (tunai)".
3. Keep either the summary cards or the filter pills (pills with counts are enough).
4. Build filter URLs with `route('owner.bookings', [...])` / `http_build_query`.
5. Row actions follow BQ-087 (no settle while an online payment is open); every irreversible action goes through `confirm-modal`; no emoji; menu rendered outside the scroll container or as a popover that is never clipped.
6. One booking view model shared by table, cards and modal.

## Linked follow-up from the 2026-11-02 customer audit

[BQ-121](BQ-121-customer-historical-invoice-amount.md) records the same live-price problem on **customer invoices**, with a separate heavy-lane plan. A single-slot payment of Rp150.000 still has that amount in the database after a catalog edit, but the individual invoice then prints Rp250.000 through `Booking::priceLabel`. Group invoice rows can also disagree with their recorded payment total. This trigger requires no reschedule and is distinct from BQ-109/D-006.

Do not treat the existing `priceLabel` accessor or a fresh `BookingRules::calculateSlotPrices` call as an immutable record of a past charge: the underlying catalog/override can change. For this item's owner UI, a clearly labelled recorded payment/group total is an available source; exact historical per-slot allocation and any snapshot schema belong to the Tech Lead decision in BQ-121. The existing light-lane approval of BQ-095 does not approve that schema or payment work. Coordinate the eventual shared presentation source to avoid two implementations.

Related duration drift after a service edit is tracked separately in [BQ-114](BQ-114-historical-booking-duration-drift.md). No change to this item's approval, status or existing owner scope is implied by these links.

## Invariants at risk

None of the domain invariants; amounts must match what the customer was charged (FR-BOOKING-008).

## Tests

- Feature: bookings index shows the charged amount for a weekend-priced booking and for a multi-slot group; a `gagal` payment shows "Gagal"; search with `&` keeps the filter.
- Manual at 375 px and 1024 px, including the action menu on the last row.

## Docs to update

`docs/domains/booking.md` known gaps if any remain.

## Evidence (fill when done)

_Fill in when done._
