# Owner shared building blocks

Built in BQ-129 for `docs/owner-portal.md` §9. Every owner page uses these instead of its own copy.

| Block | Component | Notes |
|---|---|---|
| Page header | `<x-owner.page-header title purpose>` + `action` slot | One primary action per page |
| Tabs | `<x-owner.tabs :tabs="['key' => 'Label']" param="tab">` | Active tab in the URL; unknown value → first tab |
| Booking panel | `$dispatch('open-booking-panel', { url })` | Rendered once by the layout; JSON contract in `booking-panel.blade.php` (filled by BQ-135) |
| Customer panel | `$dispatch('open-customer-panel', { url: route('owner.customers.panel', ['identifier' => …]) })` | Rendered once by the layout; JSON contract in `customer-panel.blade.php` (`GetCustomerPanel`, BQ-137). "Catat booking" prefills the walk-in form with `walkin-prefill` |
| Catat booking | `<x-owner.walkin-form :slots-url :search-url>` | The shell dialog receives a slot through `open-dialog` or contact fields through `walkin-prefill` |
| Status badge | `<x-owner.status-badge type status>` | Labels and tones from `App\Support\StatusBadge` |
| Upgrade prompt | `<x-owner.upgrade-prompt feature plan>` | The page decides access through `EntitlementRules` |
| Gallery picker | `<x-owner.gallery-picker name :images>` (`model` binds the choice to page state) | One gallery per business (BQ-142): Layanan and Halaman Booking → Tampilan pick from it |
| Empty state | `<x-owner.empty-state title description action-label action-href>` | What it is, how to start, one button |
| Dialog / drawer | `<x-owner.dialog id title>`, `<x-owner.drawer id title>` | `role="dialog"`, focus trap, Escape, focus returns to the trigger (`public/js/owner-ui.js`) |
| Confirm dialog | `$dispatch('open-confirm', { title, message, formId, tone })` | Never native `confirm()`/`alert()` (guardrail `native-dialogs`) |
| Toast | session flashes, or `window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }))` | The only place a flash message appears |
| Permission denied | `resources/views/errors/403.blade.php` | Never repeats the exception message |

## Visual system (BQ-162)

Primitives live in `resources/views/components/ui/` and are styled by the `bq-*` component classes in `resources/css/app.css`.

| Component | Use | Class behind it |
|---|---|---|
| `<x-ui.button variant size icon href>` | Every action. `primary` once per view; `danger` (outlined) for destructive actions, `danger-solid` only inside a confirm | `bq-btn bq-btn-{variant}` |
| `<x-ui.field label for name hint required>` + `<x-ui.input>` / `<x-ui.select>` / `<x-ui.textarea>` | Form fields with inline errors and `aria-invalid` | `bq-label`, `bq-input`, `bq-hint` |
| `<x-ui.panel title description flush>` + `actions` slot | A group of rows; border, no shadow | `bq-panel` |
| `<x-ui.stat label value context hero :supporting>` | One headline figure and optional supporting figures keyed by label | `bq-panel`, `bq-num` |
| `<x-ui.badge tone>` | Tones other than booking/payment states (those use `x-owner.status-badge`) | `bq-badge` |
| `<x-ui.list-row href>` + `leading`/`trailing` slots | Rows inside a panel, never cards per row | — |
| `<x-ui.filter-bar :active>` + `search`/`actions` slots | List filters; on phones only search and "Filter (n)" show | `bq-count` |
| `<x-ui.avatar name>`, `<x-ui.skeleton lines label>` | Initials and labelled loading placeholders | — |
| `<x-ui.table :columns label selectable actions>` | Up to five data columns, with `note` and `selection` slots | `bq-table` |
| `<x-ui.table.row group muted two-lines>` + `<x-ui.table.cell phone title wrap align>` | Group headings, selection, and a visible row action. Phone cells use `main`, `subtitle` and `end` | `bq-table-row` |
| `<x-ui.table-note>` + `action` slot | One shared fact above the table | — |
| `<x-ui.status-dot tone>` | One status label with a dot. Pass the label from the status formatter | — |
| `<x-ui.kv :items>` | Definition list, keyed by label. Use the slot for custom `dt` and `dd` markup | — |
| `<x-ui.banner title description tone>` + `action` slot | A page-level message. Tones are `neutral`, `warning`, `danger` | — |
| `<x-ui.error-block title description>` + `action` slot | An inline alert with recovery actions | — |
| `<x-ui.menu id label>` | Row menu. Slot items are links or buttons with `role="menuitem"` and `tabindex="-1"` | — |
| `<x-ui.segmented label :options selected name>` | With `name`, native radios keyed by value. Without it, pass links as `['label' => …, 'href' => …]` keyed by value | — |
| `<x-ui.checkbox label hide-label>`, `<x-ui.switch label description>`, `<x-ui.radio-card label description>` | Pass native input attributes such as `name`, `value`, `checked` and `disabled` | — |
| `<x-ui.sheet id title description>` + `footer` slot | Bottom overlay with the shared dialog focus behavior and events | — |
| `<x-ui.form-section title description>` | A group of related controls with a fieldset legend | — |
| `<x-ui.mini-bar :value :total unit>`, `<x-ui.duration :minutes>` | A labelled proportion and a duration in Indonesian | — |
| `<x-ui.empty title description icon>` + `action` slot | A page or panel empty state | — |

### Daily schedule

Use `x-ui.day-rail` for chronological rows and `x-ui.day-grid` for the desktop calendar. `ArrangeDayGrid` prepares the visible columns and time offsets.

Give each row a cell for every data column. In selectable tables, use the `selection` slot in each row. In tables with actions use the `action` slot in each row, even if it is empty. To show a phone subtitle, use either a `phone="subtitle"` cell or the main cell's `subtitle` slot. Give truncated text a full `title`. Use `two-lines` for the two-line desktop row height, and `data-density="compact"` on the owner body for compact rows.

Markup that cannot use a component (Alpine templates, class variables) uses the same classes. Raw Tailwind palette colours and hex values do not appear in owner views; amounts and times use `bq-num`.

## Restraint rules (adopted into `.ai/rules/frontend.md` by BQ-162)

- Colours only from the `bq-*` tokens in `resources/css/app.css`; no hard-coded hex in new owner views.
- Use sentence case and at least 13 px for text. Badges, tab counts and phone navigation labels may use 12 px. Use tabular figures for times and amounts, and Barlow Semi Condensed for clock times and headline figures.
- No infinite animations, decorative gradients, hover lift on cards, emoji in UI, or uppercase micro-labels.
- One active-state signal per navigation item; motion only in answer to an action, and none under `prefers-reduced-motion`.
