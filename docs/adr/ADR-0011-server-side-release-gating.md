# ADR-0011: Server-side release and entitlement gating

> **Status:** Accepted
> **Date:** 2026-10-30
> **Decider:** agathan (architecture lead) — delegated to the agent's recommendation on 2026-10-30
> **Work item:** BQ-008 (implementation), BQ-033, BQ-006, BQ-028 (Phase 2 package)

## Context

ADR-0002 hides Tier 3 modules from navigation, but every endpoint still works by URL, including `POST /owner/settings/payment`, which stores gateway keys and can switch `payment_mode`. Entitlement (`subscription:<feature>`, `EntitlementRules`) is a separate question — what a plan allows — and still has a plan-name check in the sidebar composer (BQ-033).

## Options

1. **Remove Tier 3 routes from the route files.** Promotion becomes a code edit per module, and tests cannot exercise hidden modules.
2. **Laravel Pennant.** New dependency; per-user/per-tenant flag storage is not needed — release tiers are platform-wide.
3. **A config-driven module registry plus one route middleware.** Chosen.

## Decision

1. **Module registry.** `config/modules.php` lists each staged module with its release state: `released`, `locked` (Tier 2: visible, explains the lock, performs nothing), or `hidden` (Tier 3). An environment may override a state (for example staging enabling a hidden module for testing); production defaults come from the file. A module missing from the registry is `hidden`.
2. **One middleware, `module:<name>`, on every route group of a staged module.** `hidden` returns 404 for every method; `locked` allows only the GET lock page and returns 403 for writes. It runs before `subscription:<feature>`.
3. **Navigation reads the same registry.** The sidebar and links never decide visibility on their own; `OwnerSidebarNavigationTest` keeps proving what is shown.
4. **Entitlement stays separate.** A released module can still require a plan feature; that decision belongs only to `EntitlementRules` (INV-SUB-01). Plan-name checks elsewhere are removed (BQ-033). Both gates fail closed.
5. **Promotion is a reviewed config change** following ADR-0002's tier process and lead approval; no code changes are needed to promote or demote.

## Consequences

- Tier 3 endpoints become unreachable in production, including the payment-settings write.
- Tests can enable a module through config to keep exercising its code.
- BQ-025 adds a check that every staged route group carries `module:`.
- Implementation: BQ-008 in Phase 4 (security stream); BQ-033 with it.
