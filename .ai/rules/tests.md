---
paths:
  - tests/**
---

# Tests

Context: `docs/workflow.md` §6.

## What to cover
Behaviour changes need tests for the happy path, validation, authorization, tenant isolation and state transitions; booking and payment changes also cover stale pending, concurrency, cross-booking tokens, duplicate webhooks and driver switching.

## Names describe behaviour
`test_customer_cannot_cancel_with_reschedule_token`, not `test_cancel`.

## Failing tests are evidence
Do not weaken an assertion to make a test pass; decide first whether code, requirement or test is wrong.

## Factories and fakes
Use model factories and their states; fake external services (`Http::fake`, `Mail::fake`, mocked gateway drivers). Never call real payment providers.

## Know the database
The suite runs on in-memory SQLite, where `lockForUpdate` does nothing. Do not claim concurrency is verified from SQLite results (BQ-023).
