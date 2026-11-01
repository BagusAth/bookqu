---
id: BQ-106
title: Owner dialog and drawer accessibility - role dialog, focus trapping, and keyboard dismissal
type: improvement
status: approved
priority: P2
phase: 8
domain: insights
requirements: [NFR-UX-001, NFR-COMP-002, NFR-MAINT-003]
invariants: []
lane: light
approval: none
decision:
launch-blocker: false
depends-on: [BQ-092]
---

# BQ-106: Owner dialog and drawer accessibility - role dialog, focus trapping, and keyboard dismissal

Found during the owner UI deep audit on 2026-11-02 across owner modal dialogs and slide-over drawers.

## Problem

### 1. Missing Semantic Dialog Roles and ARIA Attributes (WCAG 4.1.2)
Across almost all owner modals and side drawers:
- `components/owner/modal-edit-program.blade.php`
- `components/owner/modal-add-bulk-slots.blade.php`
- `components/owner/modal-configure-availability.blade.php`
- `components/owner/modal-default-pricing.blade.php`
- `components/owner/modal-view-booking.blade.php`
- `components/owner/modal-complete-profile.blade.php`
- `resources/views/owner/categories.blade.php` (category modals)
- `resources/views/owner/reviews.blade.php` (reply modal)
- `resources/views/owner/partials/customers/detail-modal.blade.php` (customer drawer)
- `resources/views/owner/partials/calendar/detail-modal.blade.php` (calendar booking drawer)

None of these contain:
- `role="dialog"`
- `aria-modal="true"`
- `aria-labelledby="[modal-title-id]"`

Assistive technology and screen readers do not recognize these overlays as dialogs and continue announcing background page landmarks as active.

### 2. Lack of Focus Trap and Focus Restoration (WCAG 2.4.3)
- When a modal or drawer is active, pressing the `Tab` key moves keyboard focus out of the dialog and into hidden background elements (sidebar navigation, header search, background table actions).
- When a dialog or drawer is closed, focus drops to `document.body` instead of returning to the button or element that initially opened the modal.

### 3. Inconsistent Keyboard Dismissal (Escape Key) (WCAG 2.1.2)
While `modal-add-program.blade.php` includes `@keydown.escape.window="open = false"`, `modal-edit-program.blade.php`, the category add/edit modals, the review reply modal, and the customer drawer do not handle the Escape key. A keyboard-only user has no standard way to dismiss them without tabbing through the entire DOM to the close button.

## Plan

1. Provide an Alpine focus-trap helper or standard Alpine `x-trap.inert` (or lightweight vanilla focus-trapping listener) on all modal dialog wrappers.
2. Standardize modal accessibility markup:
   - Add `role="dialog"`, `aria-modal="true"`, and `aria-labelledby` referencing the dialog heading ID.
   - Attach `@keydown.escape.window="close()"` to every modal and drawer.
   - Record the triggering element before opening and restore focus (`$el.focus()`) on close.
3. Consolidate modal shells into reusable component wrappers extending `BQ-092` foundation components.

## Invariants at risk

None.

## Tests

- Accessibility test / keyboard audit: with modal open, tabbing remains trapped within modal focusable elements.
- Keyboard test: pressing Escape closes active modal and returns focus to trigger button.
- Screen reader audit: modal announcement identifies dialog role and title.

## Docs to update

`docs/domains/insights.md` (Known gaps).

## Evidence (fill when done)

_Fill in when done._
