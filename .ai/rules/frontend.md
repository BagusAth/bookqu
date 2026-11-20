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

## Owner views are built from components first
Owner pages use the `x-ui.*` primitives and `x-owner.*` blocks listed in `resources/views/components/owner/README.md`. Markup that cannot use a component (Alpine templates, class variables) uses the same `bq-*` classes. A new pattern becomes a component before a second page copies it. The reference look is the Papan Jadwal prototype (`docs/design/papan-jadwal/`, BQ-162).

## Owner design rules
- Colours only from the `bq-*` tokens in `resources/css/app.css`; no raw Tailwind palette colours or hex values in owner views. Marigold `bq-accent` marks only "now" and "needs you".
- Sentence case, text at least 13 px (badges, tab counts and phone navigation labels may use 12 px). Amounts and times use `bq-num`.
- Lists are table rows inside one panel, not a card per row. One primary action per view; destructive actions open the shared confirm dialog.
- Every error state offers recovery and keeps the user's input; truncated text carries its full `title`.
- No infinite animation, decorative gradient, hover lift, emoji or uppercase micro-label; motion only in answer to an action and none under `prefers-reduced-motion`.

## Owner pages follow their contract
An owner view implements its page contract in `docs/owner-portal.md` §8 (contents, actions, empty states, acceptance criteria) and uses the shared blocks of §9; owner UI not described there is out of scope.

## Release tiers
Do not unhide Tier 3 navigation or remove Tier 2 locks; `OwnerSidebarNavigationTest` guards the sidebar (ADR-0002). Navigation is filtered by module, plan and role on the server (ADR-0021); views never decide visibility.

## User text
User-facing copy is Indonesian; keep status labels consistent with `BookingState::formatLabel` / `PaymentState::formatLabel`.

## Customer views

- Inherit the shared customer booking shell and build with the customer components and `x-ui.*` primitives. The shell shows business identity, one contextual back link when applicable, WhatsApp help and the BookQu footer. Use the customer Vite entry for Alpine.
- Use the `bq-*` colour tokens. `StorefrontBranding` supplies the tenant primary, hover, light and border colours plus text with a WCAG contrast ratio of at least 4.5:1 on the primary. Keep raw palette colours, hex values and class overrides out of customer views.
- Apply the tenant font and sentence case. Text is at least 13 px, with 12 px allowed for badges, step numbers and phone navigation. Format amounts through `Rupiah` and session ranges through `BookingTimeRange`. Use Indonesian dates and `bq-num` for times and amounts.
- Group related content in panels. Keep one primary action, in a bottom bar on a phone, and use the shared confirmation pattern for destructive actions.
- Keep inputs and give the customer a next action when an error occurs. Explain empty states and avoid claims about security, taxes or actions that the system does not guarantee. Use customer terms such as “sesi”, “ubah jadwal”, “tambahan” and “nomor pesanan”.
- Name every control, keep visible focus and use at least 44 px for touch actions. Use `aria-pressed` for selected time buttons and one polite live region for status updates. Motion responds to an action and respects `prefers-reduced-motion`. Avoid continuous animation, decorative gradients, hover lift and emoji.
