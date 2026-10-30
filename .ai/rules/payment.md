---
paths:
  - app/Actions/Payment/**
  - app/Domain/Payment/**
  - app/Infrastructure/Payments/**
  - app/Contracts/Payments/**
  - app/Http/Controllers/Webhook/**
  - app/Http/Controllers/Owner/OwnerCheckoutController.php
  - app/Console/Commands/ExpireBookingPayments.php
  - config/payment.php
---

# Payment

Context: `docs/domains/payment.md`, ADR-0001.

## Provider code stays in Infrastructure
Call providers only through `PaymentGatewayManager::driver()` (new payments) or `forPayment($payment)` (existing payments). Do not use `Midtrans\*` or SingaPay HTTP calls outside `app/Infrastructure/Payments/` (INV-PAY-05).

## Success needs trusted evidence
Mark a payment `sukses` only from a verified webhook (signature, timestamp, amount, currency) or a server-side status query; fail closed on missing data (INV-PAY-04).

## Idempotent and locked
Process provider events inside a transaction with the payment row locked; repeated events change nothing twice — state, refunds, notifications (INV-PAY-03).

## States are terminal
`pending → sukses | gagal` only; expiry sets `gagal`, never a new status (INV-PAY-02). A late success for a `gagal` payment must be reconciled, not ignored (BQ-003).

## Refunds
Create at most one `Refund` per payment and keep its status separate from payment and booking status (INV-PAY-06).

## Providers are unreliable
Handle timeouts, retries, duplicate and partial responses explicitly. A provider failure must never leave payment or booking state ambiguous; log it with payment context and keep the state recoverable.

## Webhook routes
Webhook paths are exempt from CSRF in `bootstrap/app.php`; keep that list exact and add a regression test for any new webhook.
