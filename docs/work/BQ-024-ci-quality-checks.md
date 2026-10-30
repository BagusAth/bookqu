---
id: BQ-024
title: Add formatting, dependency and documentation checks to CI
type: improvement
status: in-progress
priority: P1
phase: 3
domain: —
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: []
---

# BQ-024: Add formatting, dependency and documentation checks to CI

## Problem

CI only builds and runs tests. Formatting, dependency advisories and documentation integrity are unchecked.

## Plan

Add `vendor/bin/pint --test`, `composer audit`, `npm audit --audit-level=high` (non-blocking at first) and a small script that checks doc links and ID uniqueness (`BQ-`, `INV-`, `FR-`).

As built: a `quality` job in `tests.yml`, plus a documentation test instead of a separate script (no new folder, runs locally with the suite).

## Evidence

- **Pint** checks only PHP files changed by the push or PR (`--diff` against the PR base or the previous push). 192 files predate enforcement; reformatting them all at once would conflict with open branches, so it is left to a planned clean-up (Phase 6/7). Files this branch touched were formatted (`ProductionLogicSpecificationTest.php` is a style-only diff).
- **`composer audit`** is blocking; clean on 2026-10-30.
- **`npm audit --audit-level=high`** reports only (`continue-on-error`). It currently finds 6 advisories in build tooling, recorded as BQ-072.
- **`tests/Guardrails/DocumentationIntegrityTest.php`** checks that relative Markdown links in `AGENTS.md`, `README.md`, `docs/` and `.ai/rules/` resolve; that `FR-`/`BR-`/`NFR-` (headings in `docs/requirements.md`) and `INV-` (table in `docs/domains/README.md`) are defined once and every reference exists; and that `BQ-` numbers are unique, match their front matter and every reference exists. Probed with a broken link and three made-up IDs: all four checks fail and name the file.
- GitHub Actions run: pending until the branch is pushed.
