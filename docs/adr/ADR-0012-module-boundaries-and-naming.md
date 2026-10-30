# ADR-0012: Module boundaries, naming and architecture checks

> **Status:** Accepted
> **Date:** 2026-10-30
> **Decider:** agathan (architecture lead) — delegated to the agent's recommendation on 2026-10-30
> **Work item:** BQ-025 (architecture tests), BQ-036 (legacy naming), BQ-026, BQ-028 (Phase 2 package)

## Context

`docs/architecture.md` §3 defines layers, but nothing defines which module owns which state, so state columns are written from many places (12 payment-status writers, three availability definitions). ADR-0004…0009 introduce deep modules. Tables, columns, routes and views use Indonesian legacy names; `docs/glossary.md` maps them. Production deploys run migrations without a backup today (BQ-018).

## Options for naming

1. **Rename the schema before launch.** Touches every query, view and test at once on a host with no gated deploy; high risk, no user-visible value.
2. **Rename gradually per table.** Long dual-name period and compatibility code.
3. **Keep legacy storage names; canonical English everywhere else.** Chosen.

## Decision

1. **Areas follow the domain files.** Each `docs/domains/<area>.md` is an area with code in `app/Actions/<Area>` and `app/Domain/<Area>`. An area's state columns are written only by its owning module:

   | State | Only writer |
   |---|---|
   | `payments.status`, refunds | Payment lifecycle (ADR-0004) |
   | Slot claims, availability cache | Slot bookability (ADR-0005) |
   | Customer access grants | Management link (ADR-0006) |
   | Provider evidence | Outcome intake → Payment lifecycle (ADR-0007) |
   | `bookings.status` | Booking Actions through `BookingState` (and the lifecycle's booking-group adapter) |
   | `subscriptions.status` | Subscription Actions through `SubscriptionState` |

   Other areas call the owner's interface; they never update the columns themselves.
2. **Deep modules over folders.** A new folder, interface or service must name the state or rule it owns (architecture §2.7). `app/Services` gains no new general classes.
3. **Architecture tests enforce the rules** (BQ-025), as PHPUnit scans with a shrinking allowlist: single writers above; no provider SDK outside `app/Infrastructure`; no queries in Blade; occupancy only in `BookingRules`; plan checks only in `EntitlementRules`; scope bypasses only in ADR-0010's places; staged routes carry `module:` (ADR-0011). Larastan with a baseline is optional and decided at G3 (BQ-026).
4. **Naming.** Tables, columns, existing route URIs and view names keep their legacy names (stable public URLs included). All new classes, methods, variables, tests, docs and new tables/columns use the glossary's canonical English terms. New code may wrap legacy columns in well-named methods inside the owning model, without adding synonyms. Any schema rename is a post-launch (Phase 8) item with its own expand/contract migration plan (ADR-0015); BQ-036 is closed to "keep + glossary" until then.

## Consequences

- "Who may change this?" has one answer per state, checked by tests.
- No risky rename before launch; the glossary remains mandatory reading.
- BQ-025 lands in Phase 3 with the current violations as its allowlist; Phase 6 empties it.
