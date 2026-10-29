---
id: BQ-001
title: Stop storefront pages from exposing management links by order_id
type: bug-fix
status: proposed
priority: P0
phase: 4
domain: customer-access
requirements: [FR-BOOKING-015, FR-BOOKING-016, NFR-SEC-008, FR-MANAGE-008]
invariants: [INV-TOK-01]
launch-blocker: true
needs-lead-approval: true
depends-on: [BQ-028]
---

# BQ-001: Stop storefront pages from exposing management links by order_id

## Problem

`order_id` is predictable: `BKG-{tenantId}-{unixTime}-{100..999}` (`app/Domain/Payment/PaymentRules.php:46`). The storefront routes `customer.booking[.slug].payment|invoice|check-status|callback` accept the `order_id` alone (`app/Http/Controllers/Customer/BookingController.php:742`, `:950`). The invoice view renders the management URL including `manage_token` (`resources/views/customer/booking/invoice.blade.php:22`), and the payment-group page embeds each booking's `cancellation_token` (`resources/views/customer/manage/payment-show.blade.php:434`). Anyone who guesses or sees an `order_id` can read customer data and cancel or reschedule the booking. There is no rate limiting.

## Plan

1. Decide the access model in an ADR (BQ-028): bind storefront payment/invoice pages to the checkout session or require the management token; make `order_id` unguessable (random suffix with enough entropy).
2. Enforce it in `BookingController` (or the extracted actions) and the views.
3. Regression tests: a foreign `order_id` without session/token gets 403/404; the invoice never renders tokens to unauthorized viewers.

## Evidence

_Fill in when done._
