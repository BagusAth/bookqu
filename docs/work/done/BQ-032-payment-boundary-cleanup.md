---
id: BQ-032
title: Implement the Provider outcome intake (ADR-0007)
type: refactor
status: done
priority: P0
phase: 4
domain: payment
requirements: [NFR-EXT-001, BR-PAY-001]
invariants: [INV-PAY-04, INV-PAY-05]
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-067]
---

# BQ-032: Implement the Provider outcome intake (ADR-0007)

Rescoped and approved as the implementation of [ADR-0007](../../adr/ADR-0007-provider-outcome-intake.md) (Provider outcome intake), 2026-10-30: neutral `ProviderOutcome`, one intake, provider-first status checks, gateway cancel, removal of `MidtransPaymentService`/`CheckPaymentStatus`/fast paths, `config/midtrans.php` folded into `config/payment.php`. Moved to Phase 4 because BQ-003 depends on it. Also confirm whether SingaPay offers a server-side status API.

## Problem

`BookingController` configures `Midtrans\Config` in its constructor; `MidtransPaymentService` is used as a generic status facade for every provider; `config/midtrans.php` duplicates `config/payment.php` (read in `BookingController`, `OwnerCheckoutController`, `OwnerIntegrationController`).

## Plan

Remove provider SDK use outside Infrastructure; replace `MidtransPaymentService` with a provider-agnostic service; read all provider settings from `config/payment.php`; tests for both drivers.

## Evidence

Built on branch `bq-032-outcome-intake` (2026-10-30).

**New pieces:**
- `App\Domain\Payment\ProviderOutcome` and `App\Actions\Payment\PaymentOutcomeIntake` with three entry points:
  - `receive()` validates once: payment, provider ownership, tenant, amount and currency present for a success, IDR, exact amount to the rupiah. A rejection logs `critical` and changes nothing.
  - `refresh()` asks the payment's own provider.
  - `check()` runs `refresh` and then expiry.
- `OutcomeIntakeResult` and `WebhookResponse` (a payment of another provider answers 404, like a missing one).

**Gateway contract:** `checkTransactionStatus` is replaced by `queryOutcome(): ProviderOutcome` (never read from the local database; an unreachable provider gives `unknown`) and `cancel()`.
- Midtrans: status API → outcome; `cancel` calls Snap `expire`.
- SingaPay: the client has no status endpoint, so `unknown`; there is no cancel call, so `cancel` does nothing (follow-up BQ-074).
- Each adapter has `outcomeFromNotification()`; provider status words stay in the adapters.

**Webhooks:** both actions are thin entry points: authenticate, translate, hand to the intake. Validation is no longer duplicated, and the amount tolerance is now exact for both providers.

**Ownership:** `PaymentRules::providerOf()` is the only rule. Migration `2026_10_31_000001_backfill_payment_provider` backfills legacy rows; NOT NULL follows in BQ-074.

**Callers:**
- The customer and owner status endpoints and callbacks call `PaymentOutcomeIntake::check()`. Their "already `sukses`" fast paths are gone, and the owner callback ignores the browser payload (BQ-070).
- `bookings:expire-payments` asks every provider first. Before, it asked Midtrans only.
- The lifecycle calls the adapter's `cancel()` after commit; a failure is logged.

**Removed:** `MidtransPaymentService`, `CheckPaymentStatus`, `SynchronizePaymentStatus`, `config/midtrans.php` (readers now use `config/payment.php`), and the Midtrans SDK configuration in `Customer/BookingController`. The `provider-sdk` allowlist is now empty.

**Tests:**
- `tests/Feature/Payment/PaymentOutcomeIntakeTest.php` (18 tests, fake adapter at the seam). It covers every rejection rule (including one rupiah and one sen off), `pending`/`unknown`, idempotent repeats, late success, refresh, an unreachable provider never failing a payment, manual payments not asked, provider-first expiry, gateway cancel after commit, cancel failure not reversing the decision, and subscription payments.
- `CheckoutMidtransTest::test_owner_callback_ignores_the_browser_payload`.
- Tests that drove the deleted classes now use the intake or lifecycle entry points. Their assertions are unchanged, except the message check in `PaymentGroupManagementTest::test_idempotent_duplicate_settlement`, which now checks the `already-final` outcome.
- `SingaPayIntegrationTest` passes unchanged.

**Local runs:** SQLite suite 464 tests pass; Pint and Larastan clean (baseline lost 6 entries). GitHub Actions run https://github.com/BagusAth/bookqu/actions/runs/41089021605 (PR #5): Quality checks, SQLite, MySQL and MariaDB passed.
