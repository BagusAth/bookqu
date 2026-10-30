# ADR-0014: Testing strategy

> **Status:** Accepted
> **Date:** 2026-10-30
> **Decider:** agathan (architecture lead) — delegated to the agent's recommendation on 2026-10-30
> **Work item:** BQ-023 (MySQL suite), BQ-025, BQ-026, BQ-028 (Phase 2 package)

## Context

413 tests run on in-memory SQLite, where `lockForUpdate` is a no-op, so concurrency and locking are unproven. Many tests assert the same invariant through several entry points (about 15 for payment alone) and build internal state by hand (33 hand-built checkout sessions). ADR-0004…0013 define deep modules whose interfaces are meant to be the test surface.

## Decision

1. **The module interface is the main test surface.** Each deep module (ADR-0004…0009) has one suite at its interface covering its rules and failure modes. HTTP tests prove routing, validation, authorization, isolation and wiring — not the rule matrix again.
2. **Domain rules keep unit tests** (`tests/Unit/Domain/`); pure and fast.
3. **Two databases, one suite.** SQLite in memory remains the fast default. A `mysql` group (`#[Group('mysql')]`) proves locks, unique indexes and concurrency (competing claims, claim vs closure, sweep vs claim, revocation vs action) on MySQL 8 in CI (BQ-023). A lock-dependent claim is not "verified" until the MySQL group proves it.
4. **Provider tests never touch the network.** A fake adapter at the gateway seam drives payment tests; each real adapter has contract tests with recorded payloads and signature tests (ADR-0007).
5. **Time and tenancy are explicit.** Deadline tests freeze time (`travelTo`) in the business timezone (Asia/Jakarta); every tenant-owned feature has a foreign-tenant test (404 for owners, generic 404 for customers).
6. **Refactor safely.** Characterize before changing; replace duplicated tests only after equivalent coverage exists at the module interface (replace, don't layer); never edit a test just to make it pass (AGENTS.md rule 14).
7. **Machine checks.** Architecture tests (ADR-0012) run in the normal suite; Pint and `composer audit` in CI (BQ-024); Larastan optional at G3 (BQ-026). No coverage percentage target — each ADR lists the cases that must exist.
8. **CI runs on PHP 8.3** (the minimum in `composer.json`); local 8.5 does not count as evidence for 8.3.

## Consequences

- Fewer, deeper tests per invariant; failures point at one module.
- The deploy gate (ADR-0015) depends on the SQLite and MySQL jobs both passing.
