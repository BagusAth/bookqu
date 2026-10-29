# Payment & refund

Owns money movement for booking and subscription payments through the unified gateway (SingaPay default, Midtrans alternative), status synchronization, expiry and refunds. Rationale: ADR-0001.

## Entry points

| Kind | Where |
|---|---|
| SingaPay webhook | `POST /api/webhooks/singapay/transaction` (`singapay.webhook.transaction`) → `Webhook/SingaPayWebhookController` → `Actions/Payment/ProcessSingaPayWebhook` |
| Midtrans webhook | `POST /midtrans/webhook` (`midtrans.webhook`) → `Webhook/MidtransWebhookController` → `Actions/Payment/ProcessPaymentWebhook` |
| Customer status poll / callback | `Customer/BookingController::checkPaymentStatus`, `handleCallback` → `MidtransPaymentService::verifyAndSync` → `CheckPaymentStatus` |
| Owner subscription status | `Owner/OwnerCheckoutController::checkPaymentStatus`, `handleCallback` |
| Expiry | `bookings:expire-payments` every 15 min (`Console/Commands/ExpireBookingPayments`) → `ExpirePayment`; also on page view when `expired_at` has passed |

CSRF is disabled only for the webhook paths (`bootstrap/app.php`).

## Operations

`CreateBookingPayment` (free / pending payment + gateway initialization), `CreateSubscriptionPayment`, `CheckPaymentStatus`, `SynchronizePaymentStatus` (`processSuccess` / `processFailed`, row-locked, idempotent), `ProcessSingaPayWebhook`, `ProcessPaymentWebhook`, `ExpirePayment`.

## Infrastructure boundary

`App\Contracts\Payments\PaymentGatewayInterface` (`createBookingPayment`, `createSubscriptionPayment`, `checkTransactionStatus`) implemented by `Infrastructure/Payments/SingaPay/SingaPayPaymentGateway` (+ `SingaPayClient`, `SingaPayWebhookVerifier`: HMAC signature over the sorted payload + `X-TIMESTAMP` tolerance `SINGAPAY_WEBHOOK_TOLERANCE_SECONDS`) and `Infrastructure/Payments/Midtrans/MidtransPaymentGateway` (Snap, SHA-512 signature, gross-amount check). `PaymentGatewayManager::driver()` picks `config('payment.default')` (`PAYMENT_GATEWAY_DRIVER`); `forPayment()` picks the payment's own `provider`.

## Rules

`App\Domain\Payment\PaymentState` (states, transitions, provider status mapping) · `App\Domain\Payment\PaymentRules` (expiry, Midtrans signature, order id, subscription price).

## Data

`payments`: `idtenant`, `tipe` (`booking` / `subscription`), `idplan`, `idbooking` (deprecated), `jumlah`, `status`, `metode`, `provider`, `order_id`, `external_id`, `manage_token`, `snap_token`, `payment_url`, `expired_at`, payer fields. `refunds`: `booking_id`, `payment_id`, `jumlah`, `status`, `refund_reference`.

## Invariants

- **INV-PAY-01** — payment state is separate from booking state.
- **INV-PAY-02** — status ∈ `pending`, `sukses`, `gagal`; `sukses` and `gagal` are terminal; expiry ⇒ `gagal`.
- **INV-PAY-03** — duplicate provider events change nothing twice.
- **INV-PAY-04** — success only from a verified webhook or a server-side status query.
- **INV-PAY-05** — an existing payment always uses its own provider.
- **INV-PAY-06** — at most one refund per payment; refund state is separate.

## Evidence

`tests/Feature/Payment/PaymentActionTest.php`, `PaymentGatewayDriverTest.php`, `SingaPayIntegrationTest.php` (65 tests), `Owner/CheckoutMidtransTest.php`, `Customer/PaymentGroupManagementTest.php`, `tests/Unit/Domain/PaymentStateTest.php`, `PaymentRulesTest.php`.

## Status

Functional: Implemented · Verification: Verified (partial) · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|
| Terminal status written at 12 sites in 10 modules; only `SynchronizePaymentStatus` locks and guards; `PaymentState` transitions unused in production. Target: one Payment lifecycle module (ADR-0004) | BQ-067 |
| Owner marking one slot of a group paid splits the payment group | BQ-068 |
| Owner checkout callback would trust the browser's result (dead only by a `= null` default) | BQ-070 |
| A success event that arrives after the payment became `gagal` is only logged: no refund, no alert (`ProcessSingaPayWebhook.php:201`) | BQ-003 |
| SingaPay `checkTransactionStatus` reads the local DB only; the expiry command verifies Midtrans only | BQ-003 |
| Cancelling or expiring a payment does not cancel it at the gateway | BQ-002, BQ-049 |
| `MidtransPaymentService` is used for every provider; `BookingController` configures the Midtrans SDK; `config/midtrans.php` duplicates `config/payment.php` | BQ-032 |
| Webhook mail sent synchronously | BQ-013, BQ-050 |
| Production credentials and webhook URLs not yet verified | BQ-022 |
