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
| Hosting | HostData shared hosting, deployed by GitHub Actions on push to `main` (`docs/operations.md`); target environments, gated deploy and release: ADR-0015 |
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
- Authorization follows [ADR-0010](adr/ADR-0010-authorization-and-tenant-scoping.md): owners through Laravel Policies (foreign ids resolve to 404), admins through the `admin` gate, customers through ADR-0006 grants; `TenantScope` stays as fail-closed defence in depth.
- Code without a request (jobs, webhooks, commands) runs inside a tenant-context runner for a trusted tenant, one tenant per transaction. Scope bypasses are allowed only in the four places ADR-0010 lists, each with an explicit filter, a `scope-bypass:` reason and a test.

## 5. Booking and availability

- The Slot bookability contract, transaction participation, tenant-first synchronization across relevant writers, closure policy and cache ownership are decided in [ADR-0005](adr/ADR-0005-slot-bookability-module.md). This is the accepted target; implementation follows BQ-023 → BQ-067 → BQ-012 → BQ-069 and the roadmap gates.
- **Occupancy** (`paid`, `completed`, or `pending` while its payment is within its deadline plus the settlement margin — ADR-0009; today still 15 minutes from creation) is defined only in `BookingRules`. **Availability** is decided only by the Slot bookability module (ADR-0005), which every entry point — storefront, owner booking, both reschedule flows, walk-in, calendar, reports — uses; today it is spread over `AvailabilityRules` / `GetAvailableSchedules` and caller queries (BQ-012).
- The storefront journey is a server-side Checkout draft (ADR-0008): a typed draft with step transitions, stored in the session, holding no authority; the quote shown equals the amount charged.
- A booking write is one transaction: lock tenant first → lock and validate owned schedules → request stale-pending group expiry through Payment lifecycle → re-check availability → write → commit → side effects. A rejected operation rolls back its mutations. The `unique_active_booking_slot` index is the final guard.
- Cancellation and rescheduling are operations (`CancelBooking`, `RescheduleBooking`), never raw status updates; state changes go through `BookingState`.
- Multi-slot reservations are validated and persisted as one unit with one payment.

## 6. Payments

- Business code depends on `PaymentGatewayInterface` through `PaymentGatewayManager`: `driver()` for new payments, `forPayment()` for existing ones (owner = `payments.provider`). Provider SDKs, configuration and webhooks stay in `app/Infrastructure/Payments/` and webhook controllers. One configuration source: `config/payment.php`.
- Only the Payment lifecycle module changes a payment's status (ADR-0004), under the tenant-first lock (ADR-0005), idempotently.
- Provider evidence enters through the Outcome intake (ADR-0007): adapters authenticate and translate webhooks and status queries into one neutral outcome; the intake checks payment, provider, exact amount, currency and tenant once, then hands over to the lifecycle. A status check always asks the provider; unreachable means `unknown`, never `gagal`.
- Each payment has one deadline (`expired_at`, ADR-0009); slot hold, sweep, links and countdowns derive from it. Reclaim asks the provider first, then expires through the lifecycle and requests gateway cancel after commit.
- Payment, booking and refund states are separate lifecycles. A success that arrives after `gagal` creates a refund owed and an owner alert (BR-PAY-001).
- Adding a provider means implementing the interface and registering it in the manager; no business code changes.

## 7. Customer access

Management without an account follows [ADR-0006](adr/ADR-0006-management-link-module.md): one module issues and resolves explicit access grants and builds permitted links. Management deliberately bundles the named customer operations for one reservation; Checkout, Invoice and operation-specific profiles remain narrower. Opaque random secrets are stored as hashes, exchanged into grant-backed sessions through a canonical first-party bootstrap, and never reconstructed by models or views. TenantMiddleware consumes the verified customer-access context on canonical private routes.

Public identifiers never authorize access (FR-MANAGE-008). Finite expiry, revocation generations, recorded-recipient renewal, delivery intent, subject/membership checks and the prelaunch legacy cutover are defined once in ADR-0006. Authorization is revalidated inside tenant-first write transactions; booking/payment eligibility remains with its existing owners. BQ-001 implements this accepted target, with scope/share acceptance in BQ-053/BQ-052.

## 8. Subscription and release gating

- Feature access and limits come only from `EntitlementRules`; routes use the `subscription:<feature>` middleware, which fails closed.
- Release tiers (`docs/product.md` §8) are enforced server-side through the module registry `config/modules.php` and the `module:<name>` middleware (ADR-0011): `hidden` → 404, `locked` → lock page only; navigation reads the same registry. Target, BQ-008.

## 9. Side effects, async work and the scheduler

- Mail, notifications and cache invalidation run after commit; their failure never undoes the main operation.
- Mail and notifications are queued (`ShouldQueue`, after commit) and jobs carry ids, never tokens (target, BQ-013).
- On shared hosting (ADR-0013): cron runs `schedule:run` and a short `queue:work --stop-when-empty --max-time=50` every minute; scheduled work runs in-process (`Schedule::call()`/`job()`), because `proc_open` is disabled. Jobs and sweeps are idempotent and process one tenant per transaction.
- Scheduled work finds records and delegates to Actions/domain rules; it never re-implements a rule.

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
| Module-interface tests | Main test surface of each deep module (ADR-0004…0009) | `tests/Feature/<Area>/` |
| Integrity tests on MySQL | CI runs the whole suite on MySQL 8 (`phpunit.mysql.xml`); tests that need real row locks carry `#[Group('mysql')]` and are skipped on SQLite | `tests/Feature/**` (group `mysql`) |
| Provider contract tests | Recorded payloads per real adapter; fake adapter elsewhere | target (BQ-032) |
| Architecture tests | Enforce ADR-0012 by scanning sources; known violations are counted per file in a shrinking allowlist | `tests/Guardrails/` |

Strategy: [ADR-0014](adr/ADR-0014-testing-strategy.md). Characterize poorly tested behaviour before refactoring it; replace duplicated tests, don't layer them.

## 13. Naming

Canonical English terms (`docs/glossary.md`) for all new code, tables and columns. Existing tables, columns, route URIs and views keep their legacy names; any schema rename is a post-launch item with an expand/contract plan ([ADR-0012](adr/ADR-0012-module-boundaries-and-naming.md)).

Each state has one writer (ADR-0012): `payments.status` and refunds — Payment lifecycle; slot claims and availability cache — Slot bookability; customer grants — Management link; `bookings.status` — booking Actions via `BookingState`; `subscriptions.status` — subscription Actions via `SubscriptionState`.

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
