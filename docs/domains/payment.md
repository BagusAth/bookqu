# Payment & refund

Owns money movement for booking and subscription payments through the unified gateway (SingaPay default, Midtrans alternative), status synchronization, expiry and refunds. Rationale: ADR-0001.

## Entry points

| Kind | Where |
|---|---|
| SingaPay webhook | `POST /api/webhooks/singapay/transaction` (`singapay.webhook.transaction`) → `Webhook/SingaPayWebhookController` → `Actions/Payment/ProcessSingaPayWebhook` (timestamp + HMAC) → `PaymentOutcomeIntake::receive` |
| Midtrans webhook | `POST /midtrans/webhook` (`midtrans.webhook`) → `Webhook/MidtransWebhookController` → `Actions/Payment/ProcessPaymentWebhook` (SHA-512 signature with the payment's key) → `PaymentOutcomeIntake::receive` |
| Customer status poll / callback | `Customer/BookingController::checkPaymentStatus`, `handleCallback` → `PaymentOutcomeIntake::check` |
| Owner subscription status | `Owner/OwnerCheckoutController::checkPaymentStatus`, `handleCallback` → `PaymentOutcomeIntake::check`; the browser's payload is ignored (BQ-070) |
| Expiry | `bookings:expire-payments` every minute, booking and subscription payments past `expired_at` + margin (`Console/Commands/ExpireBookingPayments`) → `PaymentOutcomeIntake::check` (provider first, then `Expired`); also on page view when `expired_at` has passed |

CSRF is disabled only for the webhook paths (`bootstrap/app.php`).

## Operations

**Payment lifecycle module** (`Actions/Payment/PaymentLifecycle`, ADR-0004) is the only writer of `payments.status` and of refunds:

- `transition(payment, PaymentTransition, method)` → `PaymentTransitionResult` (`changed`, `already-final`, `rejected` + reason, `refund-owed`). Reasons: `ProviderSucceeded`, `ProviderFailed`, `Expired`, `CancelledByPayer`, `InitiationFailed`, `SettledByOwner`. One transaction (joins the caller's), tenant row locked first, then the payment; transitions validated by `PaymentState`; idempotent.
- `recordSettled(attributes)` for payments settled at creation (free `gratis`, walk-in).
- `refundCancelledBooking(booking)` — at most one refund per payment, only when the payment succeeded.
- Consequences go through an internal seam: `Lifecycle/BookingGroupConsequences` (whole group paid or cancelled, tokens, usage log, owner notification + invoice for provider success, cache) and `Lifecycle/SubscriptionConsequences` (activate the plan).
- A provider success for a payment already `gagal`, or for a group with a cancelled booking, records one refund owed, keeps the bookings cancelled, notifies the owner (`PaymentRefundOwedOwnerNotification`) and logs `critical`.
- Payer cancel, expiry and owner settlement request gateway cancellation after commit (`PaymentGatewayInterface::cancel`). Midtrans expires the Snap transaction; SingaPay has no cancel call and does nothing. A failure is logged and never reverses the local decision.

Callers: `PaymentOutcomeIntake` (provider outcomes), `CreateBookingPayment` (free / pending payment + gateway initialization), `CreateSubscriptionPayment`, `UpdateBookingStatus` (owner settlement), `CancelBooking` (refund), `EvictStalePendingBookings` and the payment pages (expiry, payer cancel).

**Outcome intake** (`Actions/Payment/PaymentOutcomeIntake`, ADR-0007) validates every provider outcome once and hands `sukses`/`gagal` to the lifecycle. It never writes state.

- `receive(ProviderOutcome)` checks, in order:
  - the payment is found by its references (order id, then provider id);
  - the reporting provider equals `PaymentRules::providerOf()`, the only ownership rule;
  - the tenant exists;
  - a success carries an amount and a currency;
  - the currency is IDR;
  - the amount equals the stored amount to the rupiah (`PaymentRules::amountMatches`).

  A rejection logs `critical` and changes nothing. `pending` and `unknown` change nothing.
- `refresh(payment)` asks the payment's own provider (`queryOutcome`) and applies the answer. Successful and manual payments are not sent to a provider.
- `check(payment)` runs `refresh`, then expiry if the payment is still pending past `expired_at`.
- Webhook responses come from `WebhookResponse`; a payment of another provider answers like a missing one.

## Infrastructure boundary

`App\Contracts\Payments\PaymentGatewayInterface` (`createBookingPayment`, `createSubscriptionPayment`, `queryOutcome` → `ProviderOutcome`, `cancel`) implemented by `Infrastructure/Payments/SingaPay/SingaPayPaymentGateway` (+ `SingaPayClient`, `SingaPayWebhookVerifier`: HMAC signature over the sorted payload + `X-TIMESTAMP` tolerance `SINGAPAY_WEBHOOK_TOLERANCE_SECONDS`) and `Infrastructure/Payments/Midtrans/MidtransPaymentGateway` (Snap, SHA-512 signature, status API, `expire` for cancel).

- Each adapter translates its notifications with `outcomeFromNotification()`, so provider status words stay in the adapters.
- SingaPay's client has no status endpoint. Its `queryOutcome` answers `unknown`, so SingaPay payments rely on webhooks plus the late-success path.
- All provider settings live in `config/payment.php`; `config/midtrans.php` was removed.

`PaymentGatewayManager::driver()` picks `config('payment.default')` (`PAYMENT_GATEWAY_DRIVER`); `forPayment()` picks the payment's own `provider`.

## Rules

`App\Domain\Payment\PaymentState` (states, transitions, provider status mapping) · `App\Domain\Payment\PaymentRules` (one payment deadline per purpose and settlement margin — `deadlineFor`, `isReclaimable` — provider ownership, exact amount, expiry, Midtrans signature, order id, subscription price) · `App\Domain\Payment\ProviderOutcome`.

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

Functional: Implemented · Verification: Verified (partial) · Architecture: Partially aligned (lifecycle module, outcome intake and one payment deadline done; Slot bookability BQ-012 pending)

## Known gaps

| Gap | Item |
|---|---|
| SingaPay cannot be asked for a payment's status: the client has no status endpoint and the vendor API is not confirmed. A missed SingaPay webhook is only recovered when SingaPay retries it | BQ-074 |
| `payments.provider` is backfilled but still nullable | BQ-074 |
| SingaPay payment links are not cancelled at the gateway (no cancel call in the client) | BQ-002, BQ-049 |
| An owner cancelling a pending booking leaves its payment `pending` with an open link; a later payment becomes a refund owed | BQ-002 |
| Webhook mail sent synchronously | BQ-013, BQ-050 |
| Production credentials and webhook URLs not yet verified | BQ-022 |

Target decisions: [ADR-0004](../adr/ADR-0004-payment-lifecycle-module.md) (one lifecycle module, implemented by BQ-067), [ADR-0007](../adr/ADR-0007-provider-outcome-intake.md) (provider outcomes through one intake, BQ-032; late success BQ-003; owner callback BQ-070; gateway cancel BQ-002/BQ-049) and [ADR-0009](../adr/ADR-0009-pending-payment-lifetime.md) (one payment deadline, provider-first sweep for booking and subscription payments, BQ-071). ADR-0004, ADR-0007 and ADR-0009 are implemented (BQ-067, BQ-032, BQ-071).
