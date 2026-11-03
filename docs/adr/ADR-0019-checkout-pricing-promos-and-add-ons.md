# ADR-0019: Checkout pricing with promos, add-ons and a frozen amount

> **Status:** Proposed
> **Date:** 2026-11-04
> **Decider:** Tech Lead — Product Lead for the product scope (D-014, D-015)
> **Work item:** BQ-126 (specification); implementation items follow in epic E5

## Context

D-015 puts promos and add-ons into the amount the customer pays, for Medium and Pro.

**ADR-0008 already decides:**
- the quote equals the charge (decision 4);
- vouchers stay out (decision 6).

**The code today:**
- `CreateBookingRequest` accepts `voucher_code` and `selected_addons`, and nothing uses them;
- the voucher endpoint answers 422.

**What else affects the price:**
- D-014: participants (FR-BOOKING-021);
- price rules (FR-SCHEDULE-012);
- the frozen booking amount (FR-BOOKING-023), so owner lists and reports show what was paid.

**Constraints:**
- the Outcome intake checks the exact provider amount against the payment (ADR-0007, INV-PAY-04);
- refunds use the paid amount (FR-REFUND-004).

## Options

1. **Compute discounts and add-ons in the controller or the view.** Rejected: it breaks "quote equals charge" and `AGENTS.md` rule 11.
2. **Store only the total on the payment.** Simple, but reports, invoices and partial refunds need the breakdown, and later price changes cannot be explained.
3. **One pricing rule in the domain, a stored price breakdown per booking group, and reservations for promo quota and add-on stock tied to the payment lifecycle.** Chosen.

## Decision

1. **One pricing rule.** `App\Domain\Booking\BookingPricing` computes the quote from the inputs below, in a fixed order:
   1. slot price per participant (base price with the price rules and any slot override, FR-SCHEDULE-012) × participants × slots;
   2. add-ons × quantity;
   3. the promo discount (percentage or fixed), capped by the maximum discount. The discount is applied to the slot subtotal of the services the promo applies to, after checking the minimum order.

   The total is never below zero, and is rounded to whole rupiah. The Checkout draft (ADR-0008) shows this quote, and `CreateBooking` recomputes it inside the transaction. A difference refuses the placement and shows the new quote (ADR-0008 decision 4).
2. **Frozen breakdown.** Placement stores the breakdown on the booking group: base subtotal, add-on lines (item, quantity, unit price), promo (code, discount), and total.
   - The payment amount equals the total.
   - Invoices, owner lists, reports and refunds read the stored breakdown.
   - Nothing recomputes an existing booking from current prices.
3. **Promo quota and add-on stock are reserved, then committed.**
   - At placement, under the tenant lock, the promo's uses and each add-on's stock are checked against pending reservations plus paid uses, and reserved for the payment group.
   - The booking-group adapter of the Payment lifecycle (ADR-0004 decision 3) commits the reservation on `sukses` (incrementing `used_count`, decreasing stock), and releases it on `gagal`.
   - A late success (BR-PAY-001) does not re-reserve.
   - This keeps the quota and the stock exact under concurrency without new locks: the tenant lock already serialises the writers.
4. **Eligibility is server-side.** Code validity, period, active state, applicable services, minimum order, and add-on stock and service link are checked by the domain rule. The validation endpoint returns only the quote.
5. **Entitlement.** Promos and add-ons require the plan feature through `EntitlementRules` (INV-SUB-01). A tenant without it gets no promo field and no add-on step, and placement ignores no input silently: it refuses unknown promo or add-on input.
6. **Walk-ins** use the same pricing rule (participants, add-ons; no promo code).
7. **Supersedes ADR-0008 decision 6** (vouchers stay out). The other decisions of ADR-0008 stand.

## Consequences

- **Exact amount check:** the Outcome intake compares the provider amount against the stored total, as today.
- **Tests:**
  - the pricing rule (every rule order, caps, rounding);
  - quote-equals-charge;
  - reservation commit and release across success, expiry and late success;
  - MySQL concurrency for the last promo use and the last add-on unit;
  - the plan gate.
- **Code cleanup:** `CreateBookingRequest` stops accepting fields that nothing uses until this lands. That removal is part of the first implementation item.
- **Docs:** `.ai/rules/payment.md` and `booking-availability.md` reference this ADR. `docs/domains/booking.md` and `payment.md` change with the implementation.
