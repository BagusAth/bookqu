# ADR-0010: Authorization and tenant scoping

> **Status:** Accepted
> **Date:** 2026-10-30
> **Decider:** agathan (architecture lead) — delegated to the agent's recommendation on 2026-10-30
> **Work item:** BQ-031 (policies), BQ-009 (scope bypasses), BQ-006, BQ-038, BQ-028 (Phase 2 package)

## Context

There is no `app/Policies`. Owner authorization relies on `TenantScope` plus hand-written checks per controller or Action; admin access is `RoleMiddleware`. `withoutGlobalScope(s)` appears 55 times in 29 files — in owner Actions that already run with a tenant context, in webhooks and console commands that have none, in models, and in the admin dashboard — with no inventory of why. Customer access is decided in ADR-0006.

## Options

1. **Keep TenantScope plus manual checks.** Every new endpoint must remember the check; nothing proves it did.
2. **A roles/permissions package.** New dependency for two roles and one owner per tenant.
3. **Laravel Policies on top of fail-closed TenantScope, with an explicit tenant-context runner for code outside requests.** Chosen.

## Decision

1. **Three actors, three mechanisms.** Owners: Policies. Platform admins: the `admin` role gate plus explicit admin queries. Customers: ADR-0006 grants. No actor's mechanism authorizes another actor.
2. **Owner Policies for every tenant-owned resource** (bookings, services, categories, schedules, blocked dates, customers, reviews, payouts, settings). A policy checks the role and that the record's tenant is the owner's tenant. Controllers call `authorize` before the Action; Actions that mutate re-check ownership inside their transaction (ADR-0005 decision 5). Route model binding resolves inside the tenant scope, so a foreign id is a 404 — never a 403 that confirms existence.
3. **TenantScope stays as defence in depth** and stays fail-closed. It is not the authorization decision; a passing scope never replaces a policy.
4. **Code without a request uses a tenant-context runner, not a bypass.** Jobs, webhooks, the Outcome intake and console commands resolve the tenant from a trusted source (the payment row, the job payload id), then run their work inside `TenantContext` set for that tenant (a small scoped helper; added with BQ-009), one tenant per transaction.
5. **Scope bypasses are allowed only in four places:** platform-admin read models, tenant resolution itself (finding which tenant owns a payment/webhook/domain), cross-tenant platform jobs before they enter the runner, and the customer-access resolver (ADR-0006). Each bypass carries an explicit tenant or ownership filter, a one-line `scope-bypass:` reason comment, and a test where it guards data. Bypasses inside owner/storefront requests (context already set) are removed. BQ-025 enforces the allowlist; it may only shrink.
6. **Admin scope is a product decision.** Admin is read-only today; refund and payout workflows (BQ-038) get their own requirements before any admin write endpoint exists.
7. **Fail closed everywhere.** Subscription middleware (BQ-006), missing tenant context, unknown policy ability and storage failures deny.

## Consequences

- One reviewable place per resource answers "who may do this"; isolation tests per resource prove foreign-id 404s.
- BQ-009 becomes a mechanical classification against decision 5, which suits a parallel review.
- Implementation: BQ-009 and BQ-006 in Phase 4 (security stream); BQ-031 Policies in Phase 6 per resource, earlier for any resource touched by a launch fix.
