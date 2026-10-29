---
id: BQ-007
title: Rate limit login, registration and public endpoints
type: improvement
status: proposed
priority: P0
phase: 4
domain: auth
requirements: [FR-AUTH-008, NFR-SEC-006]
invariants: []
launch-blocker: true
needs-lead-approval: true
depends-on: []
---

# BQ-007: Rate limit login, registration and public endpoints

## Problem

There is no throttling on `login.store`, `register.store`, `password.email`, storefront checkout, payment status polling or `/manage/*` (only e-mail verification is throttled). Password brute force and enumeration of `order_id` / `booking_code` are unrestricted.

## Plan

Define named rate limiters (per IP and per account/identifier) and apply them to auth, checkout, status polling and management routes; tests for 429 responses.

## Evidence

_Fill in when done._
