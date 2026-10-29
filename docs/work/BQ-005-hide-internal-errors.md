---
id: BQ-005
title: Never show internal exception messages to customers
type: bug-fix
status: proposed
priority: P1
phase: 4
domain: booking
requirements: [NFR-SEC-004]
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: []
---

# BQ-005: Never show internal exception messages to customers

## Problem

`CreateBooking` returns `'Gagal memproses pembayaran. Error: ' . $e->getMessage()` to the customer (`app/Actions/Booking/CreateBooking.php:313`).

## Plan

Return a generic Indonesian message and keep the technical detail in the log with booking/payment context. Search for other `getMessage()` values that reach views or JSON responses.

## Evidence

_Fill in when done._
