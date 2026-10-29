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

- **INV-TOK-01** — a token authorizes only its own booking or payment group, and only its own capability.

## Evidence

`tests/Feature/Customer/BookingManageTest.php`, `PaymentGroupManagementTest.php`, `BookingReviewTest.php`, `P0SecurityTest.php`.

## Status

Functional: Implemented · Verification: Needs Verification · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|
| `order_id` is guessable (`BKG-{tenantId}-{unixTime}-{100..999}`, `PaymentRules::generateOrderId`) and the storefront invoice page shows the management link with `manage_token` to anyone who has it | BQ-001 |
| Storefront `cancel` cancels any pending payment by `order_id` alone | BQ-002 |
| The payment-group page embeds each booking's `cancellation_token`, so `manage_token` acts as a master token (`payment-show.blade.php:434`) | BQ-001, BQ-028 (ADR) |
| Customer reschedule uses its own availability query | BQ-012 |
| Audit P0: token scope mismatch in the payment-group portal; token leak in calendar/WhatsApp share (looks fixed) | BQ-053, BQ-052 |
| `GenerateTestBookingToken` command can regenerate real customer tokens in any environment | BQ-010 |
