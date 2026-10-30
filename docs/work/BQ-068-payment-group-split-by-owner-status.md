---
id: BQ-068
title: Owner marking one slot paid splits a multi-slot payment group
type: bug-fix
status: in-progress
priority: P0
phase: 4
domain: booking
requirements: [FR-MULTIBOOK-003, FR-PAYMENT-009]
invariants: [INV-BOOK-06, INV-PAY-03]
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-067]
---

# BQ-068: Owner marking one slot paid splits a multi-slot payment group

## Problem

`UpdateBookingStatus` (lines 53–55) sets the shared payment `sukses` when the owner marks **one** booking of a multi-slot group `paid` or `completed`, without a lock and even from `gagal`. When the provider webhook arrives, `SynchronizePaymentStatus::processSuccess` sees `sukses` and returns early (line 106), so the sibling bookings stay `pending` and are later evicted as stale although the customer paid. Found by the 2026-10-30 architecture review (booking explorer).

## Plan

Fixed by construction in BQ-067 (ADR-0004 decision 6: owner settlement applies to the whole payment group, recorded `sukses` with `metode = cash`). Add a regression test: owner marks one slot of a 2-slot group paid → both bookings `paid`, payment `sukses`/`cash`, later webhook changes nothing.

## Evidence

Fixed in BQ-067: `UpdateBookingStatus` requests `PaymentTransition::SettledByOwner`, which settles the whole group. `PaymentLifecycleTest::test_owner_marking_one_slot_paid_settles_the_whole_group` marks one slot of a 2-slot group paid. It then checks:
- the payment is `sukses` with `metode` `cash`;
- the sibling booking is `paid`;
- a later provider webhook returns `already-final`, changes nothing and creates no refund.
