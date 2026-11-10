# Owner shared building blocks

Built in BQ-129 for `docs/owner-portal.md` §9. Every owner page uses these instead of its own copy.

| Block | Component | Notes |
|---|---|---|
| Page header | `<x-owner.page-header title purpose>` + `action` slot | One primary action per page |
| Tabs | `<x-owner.tabs :tabs="['key' => 'Label']" param="tab">` | Active tab in the URL; unknown value → first tab |
| Booking panel | `$dispatch('open-booking-panel', { url })` | Rendered once by the layout; JSON contract in `booking-panel.blade.php` (filled by BQ-135) |
| Customer panel | `$dispatch('open-customer-panel', { url: route('owner.customers.panel', ['identifier' => …]) })` | Rendered once by the layout; JSON contract in `customer-panel.blade.php` (`GetCustomerPanel`, BQ-137). "Buat booking" prefills the walk-in form with `walkin-prefill` |
| Walk-in form | `<x-owner.walkin-form :slots>` or `:fixed-slot` | Posts to `owner.bookings.walkin` |
| Status badge | `<x-owner.status-badge type status>` | Labels and tones from `App\Support\StatusBadge` |
| Upgrade prompt | `<x-owner.upgrade-prompt feature plan>` | The page decides access through `EntitlementRules` |
| Gallery picker | `<x-owner.gallery-picker name :images>` (`model` binds the choice to page state) | One gallery per business (BQ-142): Layanan and Halaman Booking → Tampilan pick from it |
| Empty state | `<x-owner.empty-state title description action-label action-href>` | What it is, how to start, one button |
| Dialog / drawer | `<x-owner.dialog id title>`, `<x-owner.drawer id title>` | `role="dialog"`, focus trap, Escape, focus returns to the trigger (`public/js/owner-ui.js`) |
| Confirm dialog | `$dispatch('open-confirm', { title, message, formId, tone })` | Never native `confirm()`/`alert()` (guardrail `native-dialogs`) |
| Toast | session flashes, or `window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }))` | The only place a flash message appears |
| Permission denied | `resources/views/errors/403.blade.php` | Never repeats the exception message |

## Restraint rules (proposed by BQ-092/BQ-129; for the Tech Lead to adopt into `.ai/rules/frontend.md`)

- Colours only from the `bq-*` tokens in `resources/css/app.css`; no hard-coded hex in new owner views.
- Text 12 px minimum, sentence case labels; times and amounts in tabular figures (`tabular-nums`), not monospace.
- No infinite animations, decorative gradients, hover lift on cards, emoji in UI, or uppercase micro-labels.
- One active-state signal per navigation item; motion only in answer to an action, and none under `prefers-reduced-motion`.
