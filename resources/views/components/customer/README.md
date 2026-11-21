# Customer components

Customer pages follow design A "Sampul Imersif" ([spec](../../../../docs/design/booking-pelanggan/desain-a-sampul-imersif.md), BQ-165). The booking shell writes the business's appearance as `--sf-*` variables on `.customer-shell`; components read them through the `sf-*` classes and utilities in `resources/css/app.css`. Views pass display data prepared by the server; components never decide availability, prices or access.

## Tokens

| Group | Utilities / classes | Source |
|---|---|---|
| Brand colours | `bg-sf-primary`, `text-sf-on-primary`, `text-sf-primary-text`, `bg-sf-tint`, `bg-sf-tint-strong`, `bg-sf-hero-faint`, `text-sf-hero-soft` | `StorefrontBranding::for()` from `theme_color` (§2.1) |
| Neutrals and status | `bg-sf-page`, `bg-sf-surface`, `text-sf-text`, `text-sf-muted`, `border-sf-line`, `border-sf-divider`, `bg-sf-*-bg` / `text-sf-*` for success, warning, danger, info | fixed (§2.2, §2.3) |
| Shape | `rounded-sf-control`, `-tile`, `-area`, `-chip`, `-card`, `-card-small` | `button_style` (§3.1) |
| Surface | `shadow-sf-card`, `shadow-sf-raised` | `card_style` (§3.2) |
| Font | `font-sf` (set on the shell) | `font_family` |

Classes: `sf-btn` (primary), `sf-btn-secondary`, `sf-btn-on-primary` (on the hero), sizes `sf-btn-lg` / `sf-btn-sm` / `sf-btn-icon`; `sf-chip` + `sf-chip-count`; `sf-nav-item`; `sf-field`, `sf-area`, `sf-field-group`, `sf-field-error`; `sf-tile` + `sf-tile-note` (dates, times, calendar cells, −/+); `sf-card`, `sf-card-raised`, `sf-card-small`; `sf-note[data-tone]`; `sf-scroll-row`, `sf-fade-end`; `sf-has-bottom-bar` on the content above a bottom bar; `sf-num` for amounts and times. A grid or flex item that holds a `sf-scroll-row` (date strip, chips, favourites carousel) needs `min-w-0`, or the row widens the page. Never write hex values, Tailwind palette colours or `!important` in customer views.

## Components

| Component | Props | Notes |
|---|---|---|
| `x-customer.hero` | `name`, `title` (default "Reservasi di {name}"), `description`, `logo`, `initials`, `banner`, `eyebrow`, `badges` (list), `area`, `address`, `mapsUrl`, `phoneLabel`, `phoneUrl`, `waUrl` | Storefront hero. Phone `h1` is the name; tablet/desktop show `title` and a location card. A banner replaces the brand colour under a scrim |
| `x-customer.hero-ornament` | attributes for size and position | Three rings and a disc; hidden with a banner |
| `x-customer.step-header` | `title`, `step` (1–3), `backUrl`, `backLabel`, `service`, `serviceMeta`, `serviceIcon`, `changeUrl`, `overlap`; slot `aside` | Booking step header with progress; `aside` replaces the service card from tablet up |
| `x-customer.bottom-bar` | `label`, `total`, `always`, `ariaLabel`; slots default (action), `summary`, `note` | Phone and tablet; hidden from 1024 px unless `always` |
| `x-customer.service-card` | `href`, `name`, `price`, `duration`, `category`, `description`, `image`, `icon`, `favorite`, `tone` | Favourite card: carousel on phone (give it `w-[290px] shrink-0`), three columns from tablet |
| `x-customer.service-row` | `href`, `name`, `price`, `duration`, `category`, `description`, `image`, `icon`, `tone` | List row on phone, two-column card from tablet |
| `x-customer.category-chips` | `categories` (`key`, `label`, `short`, `count`), `active`, `model` (Alpine property), `variant` (`chips` / `nav`), `label` | Chips scroll on phone and wrap from tablet; `nav` is the desktop side list |
| `x-customer.date-strip` | `dates` (`date`, `label`, `is_available`), `currentDate`, `label`, `heading`, `calendarSheet` | Snap strip; Alpine `selectedDate` / `selectDate()` |
| `x-customer.month-calendar` | `panel`, `headingId` | Alpine contract in the file header (`calendarDays`, `monthLabel`, …) |
| `x-customer.time-grid` | `items` (Alpine collection), `label` | 3 columns, 4 on desktop; `isSlotSelected()` / `selectSlot()` |
| `x-customer.sheet` | `id`, `title`, `description`; slot `footer` | Bottom sheet; needs `public/js/owner-ui.js` (`ownerDialog`) on the page |
| `x-customer.summary-panel` | `title`, `eyebrow`, `rows`, `total`, `totalLabel`, `sticky`; slots default, `action`, `note` | Desktop side summary |
| `x-customer.ticket` | `month`, `day`, `weekday`, `service`, `time`, `location`, `code` | Success page ticket with copy |
| `x-customer.status-note` | `tone` (`info`, `success`, `warning`, `danger`, `brand`), `title`, `icon` | Status colours never follow the brand |
| `x-customer.empty-state` | `title`, `description`, `icon`, `tone` (`brand`, `warning`, `danger`), `code`, `headingLevel`; slot `actions` | Empty search, bookings closed, error pages |
| `x-customer.icon` | `name`, `size`, `stroke` | Line icons, `aria-hidden` |

Still from BQ-164, replaced as each screen is rebuilt (BQ-165 sessions 2–6): `x-customer.page-header`, `x-customer.summary`, `x-customer.stepper`, `x-customer.status`, `x-customer.progress-header`, `x-customer.pending-checkout-banner`, `x-customer.booking-policy`, `x-customer.help-link`.
