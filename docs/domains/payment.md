# Payment & refund

Owns money movement for booking and subscription payments through the unified gateway (SingaPay default, Midtrans alternative), status synchronization, expiry and refunds. Rationale: ADR-0001.

## Entry points

| Kind | Where |
|---|---|
| SingaPay webhook | `POST /api/webhooks/singapay/transaction` (`singapay.webhook.transaction`) → `Webhook/SingaPayWebhookController` → `Actions/Payment/ProcessSingaPayWebhook` |
| Midtrans webhook | `POST /midtrans/webhook` (`midtrans.webhook`) → `Webhook/MidtransWebhookController` → `Actions/Payment/ProcessPaymentWebhook` |
| Customer status poll / callback | `Customer/BookingController::checkPaymentStatus`, `handleCallback` → `MidtransPaymentService::verifyAndSync` → `CheckPaymentStatus` |
| Owner subscription status | `Owner/OwnerCheckoutController::checkPaymentStatus`, `handleCallback` |
| Expiry | `bookings:expire-payments` every 15 min (`Console/Commands/ExpireBookingPayments`) → `PaymentLifecycle` (`Expired`); also on page view when `expired_at` has passed |

CSRF is disabled only for the webhook paths (`bootstrap/app.php`).

## Operations

**Payment lifecycle module** (`Actions/Payment/PaymentLifecycle`, ADR-0004) is the only writer of `payments.status` and of refunds:

- `transition(payment, PaymentTransition, method)` → `PaymentTransitionResult` (`changed`, `already-final`, `rejected` + reason, `refund-owed`). Reasons: `ProviderSucceeded`, `ProviderFailed`, `Expired`, `CancelledByPayer`, `InitiationFailed`, `SettledByOwner`. One transaction (joins the caller's), tenant row locked first, then the payment; transitions validated by `PaymentState`; idempotent.
- `recordSettled(attributes)` for payments settled at creation (free `gratis`, walk-in).
- `refundCancelledBooking(booking)` — at most one refund per payment, only when the payment succeeded.
- Consequences go through an internal seam: `Lifecycle/BookingGroupConsequences` (whole group paid or cancelled, tokens, usage log, owner notification + invoice for provider success, cache) and `Lifecycle/SubscriptionConsequences` (activate the plan).
- A provider success for a payment already `gagal`, or for a group with a cancelled booking, records one refund owed, keeps the bookings cancelled, notifies the owner (`PaymentRefundOwedOwnerNotification`) and logs `critical`.
- Payer cancel, expiry and owner settlement request gateway cancellation after commit; providers do not implement it yet, so it is a logged no-op (BQ-002).

Callers: `CreateBookingPayment` (free / pending payment + gateway initialization), `CreateSubscriptionPayment`, `CheckPaymentStatus`, `SynchronizePaymentStatus` (maps a provider status to a lifecycle request), `ProcessSingaPayWebhook`, `ProcessPaymentWebhook`, `UpdateBookingStatus` (owner settlement), `CancelBooking` (refund), `EvictStalePendingBookings` (expiry).

## Infrastructure boundary

`App\Contracts\Payments\PaymentGatewayInterface` (`createBookingPayment`, `createSubscriptionPayment`, `checkTransactionStatus`) implemented by `Infrastructure/Payments/SingaPay/SingaPayPaymentGateway` (+ `SingaPayClient`, `SingaPayWebhookVerifier`: HMAC signature over the sorted payload + `X-TIMESTAMP` tolerance `SINGAPAY_WEBHOOK_TOLERANCE_SECONDS`) and `Infrastructure/Payments/Midtrans/MidtransPaymentGateway` (Snap, SHA-512 signature, gross-amount check). `PaymentGatewayManager::driver()` picks `config('payment.default')` (`PAYMENT_GATEWAY_DRIVER`); `forPayment()` picks the payment's own `provider`.

## Rules

`App\Domain\Payment\PaymentState` (states, transitions, provider status mapping) · `App\Domain\Payment\PaymentRules` (expiry, Midtrans signature, order id, subscription price).

## Data

`payments`: `idtenant`, `tipe` (`booking` / `subscription`), `idplan`, `idbooking` (deprecated), `jumlah`, `status`, `metode`, `provider`, `order_id`, `external_id`, `manage_token`, `snap_token`, `payment_url`, `expired_at`, payer fields. `refunds`: `booking_id` (nullable: a late subscription payment has no booking), `payment_id`, `jumlah`, `status`, `refund_reference`.

## Invariants

- **INV-PAY-01** — payment state is separate from booking state.
- **INV-PAY-02** — status ∈ `pending`, `sukses`, `gagal`; `sukses` and `gagal` are terminal; expiry ⇒ `gagal`.
- **INV-PAY-03** — duplicate provider events change nothing twice.
- **INV-PAY-04** — success only from a verified webhook or a server-side status query.
- **INV-PAY-05** — an existing payment always uses its own provider.
- **INV-PAY-06** — at most one refund per payment; refund state is separate.

## Evidence

`tests/Feature/Payment/PaymentLifecycleTest.php` (module interface), `tests/Feature/Actions/Payment/PaymentLifecycleLockingTest.php` (tenant-first and payment locks, MySQL/MariaDB), `tests/Feature/Payment/PaymentActionTest.php`, `PaymentGatewayDriverTest.php`, `SingaPayIntegrationTest.php` (65 tests), `Owner/CheckoutMidtransTest.php`, `Customer/PaymentGroupManagementTest.php`, `tests/Unit/Domain/PaymentStateTest.php`, `PaymentRulesTest.php`.

## Status

Functional: Implemented · Verification: Verified (partial) · Architecture: Partially aligned (lifecycle module done; outcome intake BQ-032 pending)

## Known gaps

| Gap | Item |
|---|---|
| Owner checkout callback would trust the browser's result (dead only by a `= null` default) | BQ-070 |
| SingaPay `checkTransactionStatus` reads the local DB only; the expiry command verifies Midtrans only | BQ-003 |
| Cancelling or expiring a payment does not cancel it at the gateway (the lifecycle requests it; providers log a no-op) | BQ-002, BQ-049 |
| An owner cancelling a pending booking leaves its payment `pending` with an open link; a later payment becomes a refund owed | BQ-002 |
| `MidtransPaymentService` is used for every provider (now a thin facade over `CheckPaymentStatus`, `SynchronizePaymentStatus` and the lifecycle); `BookingController` configures the Midtrans SDK; `config/midtrans.php` duplicates `config/payment.php` | BQ-032 |
| Webhook mail sent synchronously | BQ-013, BQ-050 |
| Production credentials and webhook URLs not yet verified | BQ-022 |

Target decisions: [ADR-0004](../adr/ADR-0004-payment-lifecycle-module.md) (one lifecycle module, implemented by BQ-067), [ADR-0007](../adr/ADR-0007-provider-outcome-intake.md) (provider outcomes through one intake, BQ-032; late success BQ-003; owner callback BQ-070; gateway cancel BQ-002/BQ-049) and [ADR-0009](../adr/ADR-0009-pending-payment-lifetime.md) (one payment deadline, provider-first sweep for booking and subscription payments, BQ-071). ADR-0004 is implemented; ADR-0007 and ADR-0009 are not yet, and the gaps above describe the current code.
