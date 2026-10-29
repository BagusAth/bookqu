---
id: BQ-004
title: Encrypt tenant gateway credentials and stop caching full tenant rows
type: improvement
status: proposed
priority: P0
phase: 4
domain: tenancy
requirements: [NFR-SEC-010]
invariants: []
launch-blocker: true
needs-lead-approval: true
depends-on: []
---

# BQ-004: Encrypt tenant gateway credentials and stop caching full tenant rows

## Problem

`tenants.midtrans_*_server_key` and the client keys are stored in plain text (`app/Models/Tenant.php`, `app/Actions/Tenant/UpdatePaymentSettings.php:31`). `BookingController::resolveTenant` caches `getAttributes()` of the whole tenant row for one hour under `tenant:slug:{slug}` (database cache store), copying the secrets into the cache table.

## Plan

1. Add `encrypted` casts for the credential columns with a migration that encrypts existing values.
2. Cache only the public tenant fields the storefront needs.
3. Tests: values are encrypted at rest; the cache payload contains no credentials.

## Evidence

_Fill in when done._
