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

## Visual system (BQ-161)

Primitives live in `resources/views/components/ui/` and are styled by the `bq-*` component classes in `resources/css/app.css`.

| Component | Use | Class behind it |
|---|---|---|
| `<x-ui.button variant size icon href>` | Every action. `primary` once per view; `danger` (outlined) for destructive actions, `danger-solid` only inside a confirm | `bq-btn bq-btn-{variant}` |
| `<x-ui.field label for name hint required>` + `<x-ui.input>` / `<x-ui.select>` / `<x-ui.textarea>` | Form fields with inline errors and `aria-invalid` | `bq-label`, `bq-input`, `bq-hint` |
| `<x-ui.panel title description flush>` + `actions` slot | A group of rows; border, no shadow | `bq-panel` |
| `<x-ui.stat label value context hero>` | One figure; at most one `hero` per page | `bq-panel`, `bq-num` |
| `<x-ui.badge tone>` | Tones other than booking/payment states (those use `x-owner.status-badge`) | `bq-badge` |
| `<x-ui.list-row href>` + `leading`/`trailing` slots | Rows inside a panel, never cards per row | — |
| `<x-ui.filter-bar :active>` + `search`/`actions` slots | List filters; on phones only search and "Filter (n)" show | `bq-count` |
| `<x-ui.avatar name>`, `<x-ui.skeleton lines>` | Initials; loading placeholders | — |

Markup that cannot use a component (Alpine templates, class variables) uses the same classes. Raw Tailwind palette colours and hex values do not appear in owner views; amounts and times use `bq-num`.

## Restraint rules (proposed by BQ-092/BQ-129; for the Tech Lead to adopt into `.ai/rules/frontend.md`)

- Colours only from the `bq-*` tokens in `resources/css/app.css`; no hard-coded hex in new owner views.
- Text 12 px minimum, sentence case labels; times and amounts in tabular figures (`tabular-nums`), not monospace.
- No infinite animations, decorative gradients, hover lift on cards, emoji in UI, or uppercase micro-labels.
- One active-state signal per navigation item; motion only in answer to an action, and none under `prefers-reduced-motion`.
