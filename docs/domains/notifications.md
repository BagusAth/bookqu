# Notifications & mail

Owns owner in-app notifications, dashboard polling, and e-mails to customers and owners.

## Entry points

| Route | Code |
|---|---|
| `owner.notifications`, `.read-all`, `.read`, `.destroy` | `Owner/OwnerNotificationController` (Laravel `notifications` table) |
| `owner.dashboard.polling` | `Owner/OwnerDashboardController::pollingData` |

## What is sent, and from where

| Message | Channel | Sent by |
|---|---|---|
| `NewBookingOwnerNotification` | database + mail | `CreateBooking` (free bookings), Payment lifecycle `BookingGroupConsequences` (provider success) |
| `PaymentRefundOwedOwnerNotification` | database + mail | Payment lifecycle (late success or undeliverable group: refund owed) |
| `BookingStatusChangedOwnerNotification` | database + mail | `CancelBooking`, `UpdateBookingStatus`, `RescheduleBooking` |
| `TrialExpiringNotification` | mail | `CheckExpiredSubscriptions` |
| `BookingGroupInvoiceMail` | mail | `CreateBooking` (free), Payment lifecycle `BookingGroupConsequences` (provider success) |
| `BookingCancelledMail` / `BookingRescheduledMail` | mail | `CancelBooking` / `RescheduleBooking` |
| `BookingInvoiceMail` | mail | single-booking invoice |
| `BookingConfirmedMail`, `BookingPaidNotification` | — | **unused** (dead code) |

Sends happen after the main transaction and are wrapped in `try/catch`, so a mail failure does not undo the booking or payment. Nothing implements `ShouldQueue` except the unused `BookingPaidNotification`, so every e-mail is sent inside the HTTP request or webhook.

## Evidence

`tests/Feature/Owner/OwnerDashboardPollingTest.php`; mail assertions inside booking and payment tests.

## Status

Functional: Implemented · Verification: Needs Verification · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|
| Synchronous mail slows webhooks and checkout | BQ-013, BQ-050 |
| Dead mail/notification classes | BQ-037 |
| Production mail transport not configured (`.env.example` uses `log`) | BQ-021 |

Target decision: [ADR-0013](../adr/ADR-0013-background-work-on-shared-hosting.md) — queued after-commit mail with id-only jobs, run by a cron-driven short-lived worker (BQ-013 after BQ-020). Not implemented yet.
