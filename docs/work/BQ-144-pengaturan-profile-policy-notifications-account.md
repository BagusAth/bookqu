---
id: BQ-144
title: Pengaturan: Profil Bisnis, Kebijakan Booking, Notifikasi and Akun
type: feature
status: proposed
priority: P1
phase: 4
domain: tenancy
requirements: [FR-TENANT-008, FR-BOOKING-022, FR-NOTIFICATION-006]
invariants: [INV-BOOK-03]
lane: heavy
approval: tech-lead
decision: D-001, D-020, D-007
launch-blocker: true
depends-on: [BQ-129, BQ-131, BQ-098]
---

# BQ-144: Pengaturan: Profil Bisnis, Kebijakan Booking, Notifikasi and Akun

Epic **E4 · Trust and acquisition pages** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Cancellation and reschedule deadlines have no form (YATIM-6); notification preferences are not saved; renaming changes the slug silently (BQ-110); checkout does not show the real policy (BQ-116).

## Plan

1. Build `owner-portal.md` §8.14 tabs Profil Bisnis, Kebijakan Booking, Notifikasi, Akun.
2. Booking policy with refund toggle, deadlines and booking window rules (FR-BOOKING-022 in `BookingRules`); checkout shows the policy summary.
3. Notification preferences stored per user and event.
4. Slug change with warning (carry BQ-110); current password for account changes (BQ-098).

## Scope

- **Acceptance criteria owned:** AC-SET-01, AC-SET-02, AC-SET-03, AC-SET-04.
- **New requirements owned:** FR-TENANT-008, FR-BOOKING-022, FR-NOTIFICATION-006.
- **ADRs:** none.
- **Absorbs:** BQ-110, BQ-116 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

INV-BOOK-03: booking window rules live in `BookingRules`.

## Tests

- AC-SET-01 to AC-SET-04.
- Booking window rules refuse on the server.

## Docs to update

- `docs/domains/tenancy.md`
- `docs/domains/notifications.md`
- `docs/domains/booking.md`

## Evidence (fill when done)
