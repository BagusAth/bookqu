---
id: BQ-080
title: Owner subscription and checkout UI revisions
type: bug-fix
status: proposed
priority: P1
phase: 4
domain: subscription
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: []
---

## Problem

Client revision list "Revisian BookQu" (tab 19/10), checked against `agathan` on 2026-10-31. All items are still present.
- `resources/views/owner/subscription.blade.php`: the `#btn-change-plan` button has no action (also BUG-T6-04 in BQ-066).
- `resources/views/owner/checkout-payment.blade.php`: the countdown starts from `diffInSeconds()`, which returns a float in Carbon 3, so seconds show decimals.
- The SingaPay and Snap pay buttons should read "Bayar Sekarang" without an icon.
- `resources/views/owner/checkout-invoice.blade.php`: "Simpan Gambar Invoice" (html2canvas) should become "Download Invoice". Lead decision: `window.print()` plus `@media print`, no new dependency.
- The plan feature list in `owner/subscription.blade.php` differs from the landing page (`components/landing/pricing.blade.php`).

## Plan

1. Remove `#btn-change-plan`, and tick BUG-T6-04 in BQ-066.
2. Countdown: `(int) max(0, now()->diffInSeconds($payment->expired_at, false))`.
3. Pay buttons: "Bayar Sekarang", no SVG. Keep `btn-reopen-payment`.
4. Invoice: print button and print CSS that shows only the invoice card. Align the customer invoice after that.
5. `config/plans.php` holds the feature list per `namapaket`, used by both views. Price and limits stay on `Plan`.

## Invariants at risk

None. Presentation only (AGENTS.md rule 11). Entitlement stays in `EntitlementRules`.

## Tests

- View test: the countdown's initial value is an integer.
- View test: subscription page and landing page render the same features from config.
- Manual check of the print dialog.

## Docs to update

None beyond this item's Evidence.

## Evidence (fill when done)
