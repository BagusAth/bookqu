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
| `BookingGroupInvoiceMail` | mail inside ID-only job | `DeliverManagementAccess`, requested after free booking or provider success |
| `BookingCancelledMail` | queued mail | `CancelBooking` |
| `BookingRescheduledMail` | mail inside ID-only job | `DeliverManagementAccess`, requested after reschedule |
| `BookingConfirmedMail` | mail | `DeliverManagementAccess` (management link for a booking without payment) |
| `OwnerPayoutStatusChangedNotification` | database | `ProcessOwnerPayout` (admin payout review, BQ-015) |

Sends happen after the main transaction and dispatch failures are wrapped in `try/catch`, so mail failure does not undo the booking or payment. The four owner/trial mail notifications implement `ShouldQueue` with `afterCommit()` (database channels on those notifications are queued too); database-only payout notifications stay synchronous. `BookingCancelledMail` is queued after commit.

Customer confirmation/invoice/reschedule flows persist delivery intent, then queue `SendManagementAccess` with only the delivery ID after commit. The worker validates the family, mints a fresh link and sends `BookingConfirmedMail`, `BookingGroupInvoiceMail` or `BookingRescheduledMail` synchronously inside the job; these credential-bearing mailables must not implement `ShouldQueue`. SMTP delivery therefore runs outside checkout and webhook requests when `QUEUE_CONNECTION=database` (§4 of operations).

## Evidence

`tests/Feature/Owner/OwnerDashboardPollingTest.php`; `SingaPayIntegrationTest::test_webhook_paid_status` proves queued owner notification and ID-only customer delivery without immediate mail; `CustomerAccessTest::test_queued_delivery_captures_recipient_and_rejects_stale_family_generations` exercises worker delivery and stale-intent refusal.

## Status

Functional: Implemented · Verification: Verified (partial; production worker/SMTP pending) · Architecture: Target

## Known gaps

| Gap | Item |
|---|---|
| Production scheduler/worker execution not yet verified | BQ-020 |
| Production mail transport not configured (`.env.example` uses `log`) | BQ-021 |

Implemented decision: [ADR-0013](../adr/ADR-0013-background-work-on-shared-hosting.md) — queued after-commit mail with id-only customer-access jobs, run by a cron-driven short-lived worker. Hosting setup remains a Tech Lead action (BQ-020/021), documented in [operations §4](../operations.md#4-scheduler-and-queue).
