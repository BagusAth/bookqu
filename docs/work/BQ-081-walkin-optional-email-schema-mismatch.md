---
id: BQ-081
title: Resolve optional email mismatch in walk-in creation
type: bug-fix
status: proposed
priority: P1
phase: 4
domain: booking
requirements: []
invariants: [INV-BOOK-01]
launch-blocker: false
needs-lead-approval: true
depends-on: []
---

## Finding

Sesi 4 (2026-11-01): HTTP POST owner.bookings.walkin with owned available slot, name, phone and cash method but no email passes controller validation (nullable email) and fails with NOT NULL constraint on bookings.email. CreateWalkInBooking writes null for the omitted email.

## Next step

Lead decides the email contract and approves its implementation. Fix requires app/Actions/Booking/CreateWalkInBooking.php and/or a schema decision, both excluded from this bulk session. BQ-057 UI was not shipped over this failing backend contract; its exploratory UI/request test was withdrawn together with that unshipped feature, rather than weakening it into a passing assertion.
