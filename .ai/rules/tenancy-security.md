---
paths:
  - app/Http/Middleware/**
  - app/Support/**
  - app/Traits/**
  - app/Models/Scopes/**
  - app/Http/Controllers/Owner/**
  - app/Http/Controllers/Auth/**
  - app/Http/Controllers/Customer/BookingManageController.php
  - app/Console/Commands/**
  - bootstrap/app.php
  - routes/**
---

# Tenancy and security

Context: `docs/domains/tenancy.md`, `docs/domains/customer-access.md`, `docs/domains/auth.md`.

## Tenant context only from the middleware
Use `TenantContext` / `ResolvesOwnerTenant::resolveTenant()`; never accept a tenant id from the request (INV-TEN-02). New tenant-owned models use `BelongsToTenant`.

## Scope bypasses need a reason
Every `withoutGlobalScope(s)` gets a comment with the reason, an explicit `where('idtenant', …)` or ownership check, and a test when it guards data access (INV-TEN-01).

## Resolve ids inside the tenant
Owner routes with `{id}` must load the record through the tenant scope or an explicit tenant filter and return 404 otherwise. Prove isolation with an "owner A cannot reach tenant B" test.

## Public identifiers are not credentials
`order_id` and `booking_code` never authorize anything alone. Customer endpoints require the scoped token and compare it with `hash_equals` (INV-TOK-01).

## Fail closed
Middleware that gates by tenant or subscription denies when the context is missing. Commands that touch customer data refuse to run outside `local`/`testing` unless they are production jobs.

## No secrets or internals to users
Return generic messages to users; log technical details without passwords, tokens or credentials. Third-party credentials stored in the database must use encrypted casts.
