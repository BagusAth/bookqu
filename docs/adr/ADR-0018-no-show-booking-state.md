# ADR-0018: No-show booking state

> **Status:** Accepted
> **Date:** 2026-11-04
> **Decider:** Tech Lead (accepted 2026-11-04) — Product Lead for the product scope (D-013)
> **Work item:** BQ-126 (specification); implementation item follows in epic E6

## Context

D-013 adds the final outcome "Tidak datang" for a paid booking whose customer did not come (FR-BOOKING-020).

Today the booking states are `pending`, `paid`, `cancelled`, `completed`, and only `BookingState::canTransition` may change them (INV-BOOK-01/02, `AGENTS.md` rule 4). Owners who meet a no-show currently mark it `completed`, so reports cannot tell the two apart.

## Options

1. **A flag on `completed` bookings (`completed` + `no_show = true`).** No new state, but every report and rule must remember the flag, and the meaning of "completed" becomes ambiguous.
2. **A new terminal state `no_show`.** Chosen.

## Decision

1. `BookingState` gains `STATUS_NO_SHOW = 'no_show'`, labelled "Tidak datang".
2. **The only new transition is `paid → no_show`.** `no_show` is terminal.
3. **The time condition lives in `BookingRules`**, not in the state machine: the session end has passed. The `MarkBookingNoShow` Action checks it under the tenant lock.
4. **`no_show` occupies the slot**, like `completed` (ADR-0017 decision 3). This keeps history consistent and keeps the seat index valid.
5. **Payment and balance do not change.** The payment stays `sukses`, the balance credit stays, and no refund is created.
6. **Multi-slot groups:** marking a no-show applies to every booking of the payment group, like completion.
7. **Who may mark it:** every role (FR-TEAM-002, ADR-0016).
8. **Visibility:** reports count `no_show` separately; the customer access page shows the label.

## Consequences

- **`AGENTS.md` rule 4** lists `no_show`.
- **Code:** the `bookings.status` column, every status list in code and the virtual columns of ADR-0017 change in one migration.
- **Tests:**
  - every transition into and out of `no_show`;
  - the time condition;
  - group behaviour;
  - that nothing in Payment changes.
- **Domain file:** `docs/domains/booking.md` changes with the implementation.
