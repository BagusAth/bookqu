---
id: BQ-037
title: Remove dead code
type: refactor
status: approved
priority: P2
phase: 7
domain: notifications
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: []
---

# BQ-037: Remove dead code

## Problem

Unreferenced: `app/Mail/BookingConfirmedMail.php` (and its view `emails/booking-confirmed` if unused elsewhere), `app/Notifications/BookingPaidNotification.php`, `resources/views/customer/layouts/app.blade.php`. Found by graph analysis and confirmed with grep on 2026-10-30; the lead approved removal.

## Plan

Delete them, run the full suite, refresh the graph.

## Evidence

_Fill in when done._
