# ADR-0009: Pending-payment lifetime

> **Status:** Accepted
> **Date:** 2026-10-30
> **Decider:** agathan (architecture lead) — delegated to the agent's recommendation on 2026-10-30
> **Work item:** BQ-071 (implementation), BQ-003, BQ-012, BQ-028 (Phase 2 package)

## Context

Six deadlines govern one pending payment:

- the booking payment link (`payment.drivers.<provider>.expiry_minutes`, default 15, read for Midtrans too);
- the subscription link (hard-coded 1 hour, twice);
- the slot hold (hard-coded 15 minutes from `bookings.created_at` in `BookingRules` and `EvictStalePendingBookings`);
- the expiry sweep, every 15 minutes;
- subscription payments, which are never swept.

Raising the link expiry makes eviction cancel bookings whose links are still payable, which guarantees late successes. ADR-0005 (decision 10) and ADR-0006 (Checkout expiry) deferred this unification here.

## Options

1. **Keep separate constants and align their values.** Drifts again on the next change.
2. **Extend the slot hold past the link expiry by a fixed margin.** Still two clocks.
3. **The payment owns one deadline; everything else derives from it.** Chosen.

## Decision

1. **One deadline per payment.** `payments.expired_at` is set at creation from one configuration per purpose in `config/payment.php` (booking default 15 minutes, subscription default 60 minutes). The provider link is created with the same instant. Nothing else hard-codes a payment duration.
2. **The slot hold derives from the payment.** A pending booking occupies its slot while its payment is `pending` and `now < expired_at + settlement margin` (default 2 minutes, configured once). `BookingRules` remains the single home of this rule (INV-BOOK-03) and evaluates it from the payment deadline instead of `created_at`. A pending booking without a pending payment is an anomaly (ADR-0005 decision 6).
3. **One reclaim rule, provider first.** A payment past its deadline plus margin is reclaimed — by the sweep or by claim-side stale cleanup — only through the Payment lifecycle (ADR-0004), after a provider status query through the Outcome intake (ADR-0007): provider `sukses` → success; `gagal`, `pending`, or `unknown` → expire (`gagal`), request gateway cancel after commit. A success reported later is a late success (refund owed, BR-PAY-001). Claim-side cleanup does not call the provider inside the transaction; it expires on the deadline and relies on the late-success path.
4. **Sweep both purposes, frequently.** The sweep covers booking and subscription payments, runs every minute (ADR-0013) and processes one tenant per transaction (ADR-0005).
5. **Customer-facing countdowns read `expired_at`.** Checkout pages and Checkout access (ADR-0006) use the same instant; the browser never decides expiry.

## Consequences

- Changing the payment window is one configuration value; slot hold, sweep and links follow.
- The guaranteed-late-success failure mode disappears; late successes remain possible only through provider delays and are handled by BR-PAY-001.
- Tests freeze time around `expired_at`, the margin boundary and the provider-first sweep with the fake adapter; MySQL tests cover sweep versus claim.
- Order: after BQ-067 and BQ-032, together with or after BQ-012.
