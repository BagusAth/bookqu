---
id: BQ-014
title: Record on-site payment when the owner completes a pending booking
type: product-change
status: proposed
priority: P2
phase: 4
domain: booking
requirements: [FR-BOOKING-009, BR-CORE-008]
invariants: [INV-BOOK-02, INV-PAY-01]
launch-blocker: false
needs-lead-approval: true
depends-on: []
---

# BQ-014: Record on-site payment when the owner completes a pending booking

## Problem

`BookingState` allows pending→completed (used when a customer pays on site). The booking becomes `completed` while its online payment stays `pending` and later expires to `gagal`, so financial records show a completed booking with a failed payment.

## Plan

Lead decides whether completing a pending booking must record a cash payment (e.g. mark the payment `sukses` with `metode = cash` and cancel the gateway link). Update requirements, then implement in `UpdateBookingStatus` with tests.

## Evidence

_Fill in when done._
