# ADR-0007: Provider outcome intake

> **Status:** Accepted
> **Date:** 2026-10-30
> **Decider:** agathan (architecture lead) — accepted all recommendations of grilling round 1 (Q1–Q8)
> **Work item:** BQ-032 (implementation), BQ-003 (late success), BQ-070 (owner callback), BQ-002 / BQ-049 (gateway cancel), BQ-028 (Phase 2 package)

## Context

ADR-0001 made the gateway seam real for opening a checkout only. What a provider *tells us* afterwards is handled per provider and has drifted:

- Two webhook Actions (`ProcessSingaPayWebhook`, `ProcessPaymentWebhook`) each repeat lookup, amount, currency and tenant checks with different tolerances (0.01 vs 0.001).
- `checkTransactionStatus()` means different things: Midtrans queries the provider, SingaPay reads the local database (`SingaPayPaymentGateway.php:272`). Expiry verifies with the provider only for Midtrans (`ExpireBookingPayments.php:65`).
- "Which provider owns this payment" is decided by six different rules; seven controller fast paths short-circuit on "already `sukses`".
- `MidtransPaymentService` and `CheckPaymentStatus` are pass-through modules; `OwnerCheckoutController::handleCallback` can fall back to the browser's payload (BQ-070).

ADR-0004 gives every payment status change to the Payment lifecycle module; ADR-0005 requires tenant-first locking; ADR-0006 defines who may ask for a status check. This ADR decides how provider evidence reaches the lifecycle.

## Options

1. **Patch each webhook and status path.** Fixes BQ-003 twice and keeps the drift.
2. **Generic event inbox table with replay.** Strong audit trail, but a new store and worker for two providers before launch.
3. **Adapters translate, one intake validates, lifecycle decides.** Chosen.

## Decision

1. **Neutral outcome at the seam.** `PaymentGatewayInterface` grows two translations: a verified notification → `ProviderOutcome`, and a server-side status query → `ProviderOutcome`. A `ProviderOutcome` carries the public payment reference, result (`sukses`, `gagal`, `pending`, `unknown`), amount, currency, provider transaction id and provider event time. Provider vocabulary (`settlement`, `capture`, `paid`, …) never leaves the adapter.
2. **Adapters authenticate; intake validates.** The adapter verifies signature/timestamp with the credentials of the payment's tenant (owner-mode Midtrans included) and fails closed. The Outcome intake module (`app/Actions/Payment/`) then applies the provider-agnostic checks once: payment exists; the reporting provider equals `payments.provider` (the only ownership rule — recorded at creation, legacy rows backfilled, then NOT NULL); amount equals the stored integer rupiah amount exactly; currency is IDR; tenant consistency. Any mismatch is rejected with a `critical` log and no state change.
3. **Intake hands over, never writes.** A valid `sukses`/`gagal` outcome is passed to the Payment lifecycle module (ADR-0004), which applies the transition under the tenant-first lock (ADR-0005) and handles idempotency, late success (refund owed + alert, BR-PAY-001) and fan-out. `pending`/`unknown` change nothing.
4. **A status check always asks the provider.** No adapter answers from the local database. If the provider cannot be reached the result is `unknown`, never `gagal`. If a provider offers no status API the adapter returns `unknown` and the payment relies on webhooks plus the late-success path; BQ-032 records which case applies to SingaPay.
5. **Who may trigger a status check.** Provider webhooks (push); the payer holding a Checkout grant (ADR-0006, rate-limited, with backoff — BQ-051); the owner checkout callback, which ignores the browser payload and only triggers a server query (BQ-070); and the expiry sweep, which asks the provider before expiring (ADR-0009). Controllers have no "already `sukses`" fast path of their own; they read the lifecycle result.
6. **Gateway cancel after commit.** Adapters expose a best-effort `cancel(payment)`, called after the lifecycle commits a payer cancel or expiry. Unsupported → no-op. Failure is logged and never blocks or reverses the local decision; a later success is a late success.
7. **Idempotency without an inbox.** Terminal-state idempotency in the lifecycle is the guarantee; provider event/transaction ids are logged (never secrets). An inbox table is a future option if audit needs grow.
8. **Delete pass-throughs.** `MidtransPaymentService`, `CheckPaymentStatus`, the duplicated webhook validation and the controller fast paths are removed; the two webhook Actions become thin per-provider entry points into the intake. `config/midtrans.php` folds into `config/payment.php`. Subscription payments use the same intake.

## Consequences

- BQ-003 is fixed once, for every provider. A new provider implements two translations and inherits all validation.
- Tests: a fake adapter at the seam (no curl, no process isolation) drives the intake suite — every rejection rule, `unknown` handling, late success, idempotent repeats. Each real adapter gets contract tests with recorded provider payloads and its signature tests. Old per-provider duplicates are deleted only after equivalent coverage exists.
- Order: BQ-023 → BQ-067 → BQ-032 (this ADR) → BQ-003 closes. BQ-070 and BQ-002's gateway cancel land with BQ-032.
- Builds on ADR-0001 (widens its interface); does not reopen ADR-0004…0006.
