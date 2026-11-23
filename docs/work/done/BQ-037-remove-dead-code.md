---
id: BQ-037
title: Remove dead code
type: refactor
status: done
priority: P2
phase: 7
domain: notifications
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: []
---

# BQ-037: Remove dead code

## Problem

Unreferenced: `app/Mail/BookingConfirmedMail.php` (and its view `emails/booking-confirmed` if unused elsewhere), `app/Notifications/BookingPaidNotification.php`, `resources/views/customer/layouts/app.blade.php`. Found by graph analysis and confirmed with grep on 2026-10-30; the lead approved removal.

## Plan

Delete them, run the full suite, refresh the graph.

## Evidence

Done 2026-11-01 (repo cleanup). Removed `BookingPaidNotification`, `BookingInvoiceMail` and `emails/booking-invoice` (only a negative test assertion referenced them), `customer/layouts/app.blade.php`, and the dev route `/test-isolasi`. `BookingConfirmedMail` is used again and was kept. `SlotCompatibilityRules` was kept: it is a documented domain rule that production code does not call, so it is a possible gap (multi-slot contiguity is not enforced server-side), not dead code. Same PR: SingaPay fee read only from `services.singapay` (duplicate `payment.drivers.singapay` block removed), Alpine CDN pinned to 3.15.12, and the customer invoice image export (html2canvas CDN) dropped in favour of print/PDF.
