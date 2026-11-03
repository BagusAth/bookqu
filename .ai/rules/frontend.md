---
paths:
  - resources/views/**
  - resources/js/**
  - resources/css/**
  - public/js/**
  - public/css/**
---

# Blade, Alpine and assets

## Presentation only
No queries (`App\Models\…`, `DB::`), mutations, authorization or business calculations in Blade. Pass prepared data from the controller or a view composer.

## JavaScript is not the authority
Alpine/JS may manage UI state and prepare requests, but availability, prices, payment status, authorization, tenant identity and entitlement are decided by the server.

## Safe output into JavaScript
Pass PHP values into Alpine/JS with `@js(...)` or `Js::from(...)`, never by placing `{{ }}` inside a quoted JS string (audit defects BQ-043, BQ-058).

## Reuse components
Search `resources/views/components/` before creating one; when editing a large view, extract repeated UI into components without redesigning unrelated parts.

## Owner pages follow their contract
An owner view implements its page contract in `docs/owner-portal.md` §8 (contents, actions, empty states, acceptance criteria) and uses the shared blocks of §9; owner UI not described there is out of scope.

## Release tiers
Do not unhide Tier 3 navigation or remove Tier 2 locks; `OwnerSidebarNavigationTest` guards the sidebar (ADR-0002). Navigation is filtered by module, plan and role on the server (ADR-0021); views never decide visibility.

## User text
User-facing copy is Indonesian; keep status labels consistent with `BookingState::formatLabel` / `PaymentState::formatLabel`.
