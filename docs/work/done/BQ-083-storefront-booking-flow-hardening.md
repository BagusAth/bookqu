---
id: BQ-083
title: Harden the storefront booking flow (stale pages, multi-tab, feedback)
type: bug-fix
status: done
priority: P1
phase: 4
domain: booking
requirements: [FR-BOOKING-004, FR-BOOKING-005, FR-BOOKING-016]
invariants: [INV-TOK-01, INV-BOOK-03]
launch-blocker: false
needs-lead-approval: true
depends-on: [BQ-001]
---

# BQ-083: Harden the storefront booking flow

Reported 2026-11-01 by the lead while testing locally: after reaching the payment page, pressing Back and then "Lanjutkan" showed a bare "419 Page Expired", and every form reached through Back failed the same way. The lead asked for a full edge-case review of the customer booking flow.

## Root causes

- Exchanging the checkout link (`/access`) regenerates the session, which also rotates the CSRF token. Back showed a browser-stored copy of the checkout page carrying the old token. Over HTTPS, modern browsers send `Sec-Fetch-Site: same-origin`, which Laravel 13 accepts without a token, so production is less exposed than `http://bookqu.test`. Old browsers and idle sessions still hit it.
- `APP_LOCALE`/`APP_FALLBACK_LOCALE` are `id`, but `lang/id` only had `passwords.php`. Every server validation message rendered as a raw key (`validation.required`), and pagination showed `pagination.next`.

## Edge cases reviewed and outcome

| # | Scenario | Before | Now |
|---|---|---|---|
| 1 | Back from payment, submit the old checkout page | 419 page | Pages are `no-store` and reload when restored from the back-forward cache; a 419 on a storefront POST returns to the form with an Indonesian hint (JSON: Indonesian 419 message); other areas use a friendly `errors/419` page |
| 2 | Two tabs: tab B changes the selection, tab A submits its checkout | Booked tab B's slot while tab A showed another | Checkout carries a fingerprint of service, date, slots and prices; a mismatch returns to checkout without booking |
| 3 | Owner changes a slot price between showing and submitting checkout | Charged the new price silently | Same fingerprint check; the customer reviews the new total first |
| 4 | Customer leaves the payment page (Back or "Pilih Layanan Lain") | Own pending booking held the slot with no way back | Program and checkout pages show a "Lanjutkan Pembayaran" banner until the payment is no longer payable |
| 5 | Second booking in the same browser | First payment page, polling and invoice returned 404 | Session keeps the newest grant plus four earlier live grants |
| 6 | Invalid phone or one-letter name | UI showed an error but the form still submitted; server accepted it | Client blocks the submit; server validates (10–15 digits, optional +, name ≥ 2) with Indonesian messages |
| 7 | Cancel pressed after the payment succeeded or expired | Bare 422 page | Returns to the payment page with an explanation (JSON keeps 422) |
| 8 | Checkout error after a failed payment link | Message shown twice | Shown once by the booking shell |
| 9 | Date outside the window or fully booked | English messages | Indonesian messages |
| 10 | Countdown while paying in another app | Background timers drifted | Counts against a fixed deadline |
| 11 | Status polling after the token or access ended | "Periksa koneksi internet" | Stops and tells the customer to reload or reopen the e-mail link |
| 12 | Private page after the access session ended | "Halaman tidak tersedia." only | Adds why and what to do |

Verified unchanged (no defect): slot races and double booking (`ClaimSlots`, unique index), past slots at submit (`AvailabilityRules`), cross-tenant session data (`resolveService` is tenant-scoped), payment-link failure (lifecycle cancels the bookings), quota race (checked inside the transaction).

## Residual risk

A double submit while the first request is still in flight (the button is disabled, so it needs a reload or a second device) can leave the first booking pending without its link; the second sees "slot sudah dibooking". The resume banner helps only when the session saved the first request. Proper idempotency needs a checkout key; deferred.

## Evidence

- `tests/Feature/Customer/BookingFlowHardeningTest.php` (9 tests): no-store header, 419 redirect with hint (including after a finished checkout), Indonesian JSON 419, price change and other-tab fingerprint refusals, server validation messages, resume banner, cancel after payment.
- `CustomerAccessTest`: a second booking keeps the first grant; earlier grants keep their own timeouts and only the newest five are kept.
- Full suite 597 passed; Pint and PHPStan clean.
