# Customer access (management links)

Lets customers without an account view, pay, cancel, reschedule and review their bookings through tokenized links.

## Entry points

| Route | Token checked | Controller method |
|---|---|---|
| `booking.manage.payment` `/manage/payment/{order_id}?token=` | `payments.manage_token` | `BookingManageController::showPaymentGroup` |
| `booking.manage.payment.invoice` | `manage_token` | `invoicePaymentGroup` |
| `booking.manage` `/manage/{booking_code}?token=` (scope `show`) | manage, cancellation or reschedule token | `show` |
| `booking.manage.cancel` (scope `cancel`) | `cancellation_token` only | `cancel` → `CancelBooking` |
| `booking.manage.reschedule.*` (scope `reschedule`) | `reschedule_token` only | `showReschedule`, `getTimeSlots`, `reschedule` → `RescheduleBooking` |
| `booking.manage.invoice` (scope `invoice`) | any of the three | `invoice` |
| `booking.manage.review` (scope `review`) | any of the three | `storeReview` |
| Storefront payment pages `customer.booking[.slug].payment/check-status/callback/cancel/invoice` | **none — `order_id` only** | `Customer/BookingController` |

Token comparison uses `hash_equals`. Tokens are 64 random characters (`Booking::generateSecureToken`). `booking_code` format: `BKQ-YYYYMMDD-XXXXXXXX`.

Links are e-mailed (`BookingGroupInvoiceMail`, `BookingInvoiceMail`) and shown on the invoice page.

## Invariants

- **INV-TOK-01** — an access credential authorizes only its bound booking/payment group and explicitly granted operations; rendering or link construction never widens that authority. The accepted target profiles are in ADR-0006; current token checks still have the gaps below.

## Evidence

`tests/Feature/Customer/BookingManageTest.php`, `PaymentGroupManagementTest.php`, `BookingReviewTest.php`, `P0SecurityTest.php`.

## Status

Functional: Implemented · Verification: Needs Verification · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|
| `order_id` is guessable (`BKG-{tenantId}-{unixTime}-{100..999}`, `PaymentRules::generateOrderId`) and the storefront invoice page shows the management link with `manage_token` to anyone who has it | BQ-001 |
| Storefront `cancel` cancels any pending payment by `order_id` alone | BQ-002 |
| Eligible single-slot payment-group pages embed the booking's cancellation/reschedule credentials, so a management read can obtain mutation credentials (`payment-show.blade.php:295/434`) | BQ-001, BQ-053 |
| Customer reschedule uses its own availability query | BQ-012 |
| Audit P0: token scope escalation through rendered links/forms; individual booking `show.blade.php:25-42` still exports its token in Calendar/WhatsApp text, although the group page removes it (confirmed by source inspection 2026-10-30) | BQ-053, BQ-052 |
| `GenerateTestBookingToken` command can regenerate real customer tokens in any environment | BQ-010 |

Target decision: [ADR-0006](../adr/ADR-0006-management-link-module.md), implemented by BQ-001 with BQ-053/BQ-052 acceptance and BQ-002 integration. Its grant storage, profiles, expiry/recovery, session bootstrap and legacy cutover are not implemented yet; current entry points and verification status above remain unchanged.
