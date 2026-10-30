# ADR-0008: Booking checkout draft module

> **Status:** Accepted
> **Date:** 2026-10-30
> **Decider:** agathan (architecture lead) — delegated to the agent's recommendation on 2026-10-30
> **Work item:** BQ-029 (implementation), BQ-063 (stage-3 defects), BQ-028 (Phase 2 package)

## Context

The storefront journey (`Customer/BookingController`, 1,034 lines) keeps its state in an undocumented session array: `jam` may be a string, an array or null; four copies of a guard preamble re-check tenant and service; the controller re-implements quota, blocked-date, contiguity and price checks that belong to other modules. Tests build the session by hand 33 times. The price shown is computed separately from the price charged. ADR-0005 owns placement (claim), ADR-0006 says a checkout selection is never authorization, ADR-0004 owns payment state.

## Options

1. **Split the controller only (thin steps).** Smaller files, same hidden session contract.
2. **Client-side wizard with one final POST.** Moves state into JavaScript, which must not decide anything (architecture §3).
3. **One server-side draft module with a typed draft and step transitions; the session is only its store.** Chosen.

## Decision

1. **The Checkout draft module owns the journey.** Its interface: start a draft for a server-resolved tenant, apply a step (service, date, slots, customer details), read the current draft and the next allowed step, and place it. Callers never read or write the session array.
2. **The draft is data, not authority.** It holds identifiers and customer input bound to one tenant; every step re-resolves identifiers against the tenant and re-evaluates through Slot bookability (read use, ADR-0005). A draft for another tenant or a stale draft restarts the journey. Holding a draft grants nothing (FR-MANAGE-008).
3. **Placement goes through the owners.** Placing calls `CreateBooking`, whose outer transaction claims slots (ADR-0005), creates the payment (ADR-0004) and issues Checkout access (ADR-0006). The draft never duplicates quota, blocked-date, contiguity or occupancy rules.
4. **Quote equals charge.** One pricing rule in `app/Domain/Booking` (service price, `harga_override`, units, slot count) produces the quote shown in the draft and the amount charged at placement, recomputed inside the transaction. If they differ at placement (price changed), placement is refused and the draft shows the new quote.
5. **Store behind a seam.** A session adapter in production and an in-memory adapter in tests (two adapters — a real seam). The draft expires with the session idle timeout; it reserves nothing.
6. **Thin controllers.** One small controller action per step: validate (Form Request) → apply step → redirect/render. Vouchers stay out (Tier 3 hidden, endpoint 422).

## Consequences

- Step logic is testable without HTTP; the 33 hand-built sessions and the session-shape assertions become redundant and are replaced (not layered) once module tests cover them.
- `BookingController` shrinks to step wiring (BQ-029); BQ-025's allowlist loses the controller's duplicated rules.
- Depends on ADR-0005 (placement) and ADR-0004; implemented after BQ-012 in Phase 6 unless a stage-3 launch defect forces a piece forward.
