---
id: BQ-100
title: Customer CRM identifier in Alpine, unescaped search, mobile card view, and drawer form state
type: improvement
status: approved
priority: P2
phase: 8
domain: customers-reviews
requirements: [FR-CUSTOMER-001, FR-CUSTOMER-002, FR-CUSTOMER-003, FR-CUSTOMER-004, NFR-UX-001, NFR-SEC-004]
invariants: []
lane: light
approval: none
decision:
launch-blocker: false
depends-on: [BQ-092]
---

# BQ-100: Customer CRM identifier in Alpine, unescaped search, mobile card view, and drawer form state

Found during the owner UI deep audit on 2026-11-02 in `/owner/customers`.

## Problem

### 1. Customer Identifier Interpolation in Alpine Handlers
In `resources/views/owner/partials/customers/table.blade.php:79`:
```blade
@click="openCustomer('{{ addslashes($c->identifier) }}')"
```
Interpolating customer identifiers (phone numbers, emails, or names containing special characters or quotes) directly inside single-quoted HTML attributes violates `.ai/rules/frontend.md` (`@js` for values in JavaScript) and can break the Alpine expression for identifiers with backslashes or unusual characters. (Corrected by the Tech Lead review on 2026-11-02: the search-field interpolation quoted here is in `reviews.blade.php:7`, tracked in BQ-101, not in `customers.blade.php`.)

### 2. Missing Mobile Card Representation (375px / 768px Viewports)
Unlike the bookings page which cleanly renders responsive `mobile-cards.blade.php` below `md:`, the customers directory (`/owner/customers`) only provides an 8-column wide table inside `overflow-x-auto`. On mobile screens (375px–768px), owners are forced to scroll horizontally back and forth across large empty cell paddings to inspect customer metrics, contact info, and the drawer trigger button.

### 3. CRM Note Form State and Concurrent Submissions
In the customer drawer partial (`resources/views/owner/partials/customers/detail-modal.blade.php:108-118`):
- The `saveNote()` form action submits notes via fetch to `/owner/customers/note`.
- The submit button ("Simpan Catatan") lacks a loading indicator and does not bind `:disabled="saving"`. Rapid tapping on mobile devices or network delays submit duplicate asynchronous note writes.
- The drawer lacks standard keyboard accessibility: no `@keydown.escape.window` listener, no focus trap, and no restoration of focus to the triggering customer row button when closed.

## Plan

1. Migrate all customer identifier and search string bindings to use `@js()` per frontend security standards.
2. Implement responsive mobile customer cards (`resources/views/owner/partials/customers/mobile-cards.blade.php`) similar to `partials/bookings/mobile-cards.blade.php`, hiding the wide table on screens below `md:`.
3. Add `savingNote` reactive boolean to the customer drawer component to disable the button and show a spinner while the asynchronous note save request is pending.
4. Integrate dialog accessibility attributes (`role="dialog"`, `aria-modal="true"`, `@keydown.escape.window="closeCustomer()"`, and return focus).

## Invariants at risk

None. Tenant customer query isolation (`TenantScope` via `OwnerCustomerController`) remains fully preserved.

## Tests

- Feature test: search queries with single and double quotes render without breaking Alpine state.
- Component test: verify mobile cards partial renders on small viewports and correctly passes customer identifier to drawer opener.
- UI test / manual: verify `Simpan Catatan` is disabled while saving and duplicate requests are prevented.

## Docs to update

`docs/domains/customers-reviews.md` (Known gaps).

## Evidence (fill when done)

_Fill in when done._
