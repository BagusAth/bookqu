# Architecture

How BookQu must be structured. This document is **normative**: it describes the target rules. The current state of each domain, including where it deviates, is in `docs/domains/`; work to close the gaps is in `docs/work/`. Significant decisions are explained in `docs/adr/`.

## 1. Technology baseline

| Layer | Choice |
|---|---|
| Runtime | PHP 8.3+ (`composer.json`); CI runs 8.3, local Laragon runs 8.5 — code must work on 8.3. Laravel 13 |
| Frontend | Blade, Alpine.js, Tailwind CSS v4, Vite |
| Database | MySQL in production; in-memory SQLite in the test suite |
| Session, cache, queue | Database drivers |
| Payments | SingaPay (default) and Midtrans behind one gateway interface (ADR-0001) |
| Hosting | HostData shared hosting, deployed by GitHub Actions on push to `main` (`docs/operations.md`) |
| Tests | PHPUnit 12 |

## 2. Principles

1. **Requirements drive architecture.** Structure serves accepted behaviour; it never adds product behaviour on its own.
2. **One responsibility per component.** A class that does unrelated things gets split.
3. **Every critical rule has exactly one home** (occupancy, availability, state transitions, payment handling, entitlement, token authorization).
4. **Tenant isolation is a boundary**, not a feature. Code that works but can leak across tenants is incomplete.
5. **Integrity over convenience.** Booking, availability, payment, refund and subscription changes are transactional.
6. **Infrastructure is replaceable.** Providers sit behind interfaces; business code never calls them directly.
7. **Abstractions need a reason.** A new folder, interface or service must name the responsibility it owns and the invariant it protects — no "architecture by folder".
8. **Evolve incrementally.** Understand → characterize → extract → test → replace → verify. No rewrites from scratch.

## 3. Layers and directories

```text
Presentation   app/Http/Controllers, app/Http/Requests, app/Http/Middleware, resources/views, routes/
      ↓
Application    app/Actions/<Area>/            one class per operation, owns the transaction
      ↓
Domain         app/Domain/<Area>/             pure business rules: states, transitions, calculations, policies
      ↓
Persistence    app/Models (+ Scopes), database/migrations
Infrastructure app/Infrastructure/<Area>, app/Contracts/<Area>   external providers behind interfaces
Support        app/Support, app/Traits, app/Services             cross-cutting helpers with a named responsibility
```

**Dependency rule:** dependencies point downward. Domain code does not use requests, sessions, views or providers. Controllers do not call infrastructure directly. Views never query.

| Component | Must | Must not |
|---|---|---|
| Controller | validate (Form Request), authorize, call one Action, build the response | hold business rules, multi-step workflows, provider calls, raw domain queries |
| Form Request | validate fields and formats, request-level authorization | decide business rules (occupied slot, quota) |
| Action | perform one meaningful operation; open the transaction; lock what it changes; call domain rules; trigger side effects after commit | be generic ("Manager", "Helper"); call external services inside the transaction |
| Domain rule | be the single source for a business rule; be unit-testable | depend on HTTP, views, providers |
| Model | relationships, casts, scopes, small helpers | contain workflows |
| Service (`app/Services`) | own a coherent reusable responsibility | be a "General/Common/Utility" container |
| Repository | exist only if it isolates real persistence complexity | be created per model by default |
| Route | declare method, URI, controller, middleware, name (`area.resource.action`) | contain logic in closures |
| Blade / Alpine | present data, manage UI state, prepare requests | query, mutate, authorize, compute prices or availability |

Validation is layered and every layer stays: client hints → Form Request → domain rule → database constraint.

## 4. Tenancy

- `TenantMiddleware` sets `TenantContext` from the route slug, the custom domain, or the authenticated owner. Nothing else sets it from request input.
- Tenant-owned models use `BelongsToTenant` (global `TenantScope`, fail-closed when the context is empty).
- Bypassing the scope (`withoutGlobalScope(s)`) requires a written reason, an explicit tenant/ownership filter, and a test when it guards data.
- Every new tenant-scoped feature answers: who owns the record, how the context is set, how authorization is checked, what happens with a foreign id, and which test proves isolation.
- Authorization target: Laravel Policies for owner resources, with `TenantScope` as defence in depth (decision pending, BQ-028).

## 5. Booking and availability

- The Slot bookability contract, transaction participation, tenant-first synchronization across relevant writers, closure policy and cache ownership are decided in [ADR-0005](adr/ADR-0005-slot-bookability-module.md). This is the accepted target; implementation follows BQ-023 → BQ-067 → BQ-012 → BQ-069 and the roadmap gates.
- **Occupancy** (`paid`, `completed`, or `pending` within 15 minutes) is defined only in `BookingRules`. **Availability** (status, past time, blocked date, inactive service, occupancy) is defined only in `AvailabilityRules` / `GetAvailableSchedules`. Every entry point — storefront, owner booking, both reschedule flows, calendar, reports, scheduler — uses them.
- A booking write is one transaction: lock tenant first → lock and validate owned schedules → request stale-pending group expiry through Payment lifecycle → re-check availability → write → commit → side effects. A rejected operation rolls back its mutations. The `unique_active_booking_slot` index is the final guard.
- Cancellation and rescheduling are operations (`CancelBooking`, `RescheduleBooking`), never raw status updates; state changes go through `BookingState`.
- Multi-slot reservations are validated and persisted as one unit with one payment.

## 6. Payments

- Business code depends on `PaymentGatewayInterface` through `PaymentGatewayManager`: `driver()` for new payments, `forPayment()` for existing ones. Provider SDKs, configuration and webhooks stay in `app/Infrastructure/Payments/` and webhook controllers. One configuration source: `config/payment.php`.
- Success comes only from trusted evidence (verified webhook or server-side status query). Webhooks verify signature, timestamp, amount and currency and fail closed.
- Provider events are processed idempotently inside a transaction with the payment row locked.
- Payment, booking and refund states are separate lifecycles. Expiry sets the payment to `gagal` and cancels its pending bookings. A success that arrives after `gagal` is reconciled with a refund and an alert (BR-PAY-001, Proposed).
- Adding a provider means implementing the interface and registering it in the manager; no business code changes.

## 7. Customer access

Management without an account follows [ADR-0006](adr/ADR-0006-management-link-module.md): one module issues and resolves explicit access grants and builds permitted links. Management deliberately bundles the named customer operations for one reservation; Checkout, Invoice and operation-specific profiles remain narrower. Opaque random secrets are stored as hashes, exchanged into grant-backed sessions through a canonical first-party bootstrap, and never reconstructed by models or views. TenantMiddleware consumes the verified customer-access context on canonical private routes.

Public identifiers never authorize access (FR-MANAGE-008). Finite expiry, revocation generations, recorded-recipient renewal, delivery intent, subject/membership checks and the prelaunch legacy cutover are defined once in ADR-0006. Authorization is revalidated inside tenant-first write transactions; booking/payment eligibility remains with its existing owners. BQ-001 implements this accepted target, with scope/share acceptance in BQ-053/BQ-052.

## 8. Subscription and release gating

- Feature access and limits come only from `EntitlementRules`; routes use the `subscription:<feature>` middleware, which fails closed.
- Release tiers (`docs/product.md` §8) must also be enforced server-side: a Tier 3 module is unreachable, not just unlinked (target, BQ-008).

## 9. Side effects, async work and the scheduler

- Mail, notifications and cache invalidation run after commit; their failure never undoes the main operation.
- Target: mail and notifications are queued (`ShouldQueue`, after commit) once a worker strategy exists for the hosting (BQ-013, BQ-020).
- Scheduled commands find records and delegate to Actions/domain rules; they never re-implement a rule.

## 10. Data, transactions and cache

- Every schema change is a new migration. Critical invariants also live in the database (FKs, unique indexes, NOT NULL).
- Transactions wrap multi-record changes and contain no external calls.
- Cache is derived state, never the source of truth for bookings, payments, authorization or tenant identity. Keys contain every isolating dimension (`tenant:{id}:service:{id}:…`); booking mutations clear affected availability keys; secrets are never cached.

## 11. Errors, logging and security

- Errors are classified (validation, authorization, not found, business rule, concurrency, provider, infrastructure, unexpected). Users see understandable messages, never internals.
- Log booking, payment, refund, webhook, subscription and security events with tenant/booking/payment context; never passwords, secrets, credentials or tokens.
- Third-party credentials stored in the database use encrypted casts. Login and public state-changing endpoints are rate limited.
- Uploads accept images only (`jpg, jpeg, png, webp, gif`, ≤10 MB), stored on the configured disk and tied to the tenant.

## 12. Testing

| Level | Purpose | Location |
|---|---|---|
| Domain rule tests | Prove each rule in isolation | `tests/Unit/Domain/` |
| Action / feature tests | Prove operations, authorization, isolation, HTTP flows | `tests/Feature/` |
| Integrity tests on MySQL | Prove locking and unique constraints under concurrency | target (BQ-023) |
| Architecture tests | Enforce this document (no provider calls outside Infrastructure, no queries in Blade, single occupancy rule) | target (BQ-025) |

Characterize poorly tested behaviour before refactoring it.

## 13. Naming

Canonical English terms (`docs/glossary.md`) for all new code. Existing tables and columns keep their legacy Indonesian names until a dedicated migration task; the strategy (rename before launch, gradual, or keep) is decided in BQ-028.

## 14. Extending BookQu

| Adding… | Do |
|---|---|
| A capability in an existing domain | Requirement → Action in `app/Actions/<Area>` → rules in `app/Domain/<Area>` → thin controller → tests → domain file |
| A new domain/module | Product change → requirements → ADR if it adds a boundary → `app/Actions/<New>`, `app/Domain/<New>`, `app/Http/Controllers/Owner/<New>Controller` → `docs/domains/<new>.md` → `.ai/rules` entry |
| A payment provider | Class implementing `PaymentGatewayInterface` in `app/Infrastructure/Payments/<Provider>/` + registration in `PaymentGatewayManager` + webhook controller + tests |
| A notification channel | New notification/mail class, queued, sent after commit |
| A staged feature | Ship behind its tier gate; promote per `docs/product.md` §8 |

Scalability triggers (documented, not built yet): move from shared hosting to a VPS when a persistent queue worker or long-running processes are needed; Redis for cache/queue/session under load; object storage for uploads when running more than one server.

## 15. Anti-patterns

God controller · god service · generic utility dump · provider calls in business code · business logic in Blade or JavaScript · client-side "security" · the same rule implemented in several places · architecture by folder · editing a test to make it green · hiding a product change inside a refactor.

## 16. Refactoring priority

When several problems compete: tenant/security integrity → booking → payment → schedule/availability → controller decomposition → Blade decomposition → subscription entitlement → naming → routes → general cleanup. A live security or data-integrity issue always goes first.
