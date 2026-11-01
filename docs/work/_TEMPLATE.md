---
id: BQ-NNN
title: Short imperative title
type: bug-fix            # bug-fix | feature | refactor | improvement | product-change | docs | ops
status: proposed         # proposed | approved | in-progress | done | rejected
priority: P1             # P0 | P1 | P2
phase: 4                 # roadmap phase, see docs/work/ROADMAP.md
domain: booking          # file name in docs/domains/
requirements: []         # e.g. [FR-BOOKING-017, NFR-SEC-008]
invariants: []           # e.g. [INV-BOOK-04]
lane: heavy              # heavy (work items are for the heavy lane, docs/workflow.md §3)
approval: tech-lead      # none | tech-lead | product-lead
launch-blocker: false
depends-on: []           # e.g. [BQ-028]
---

## Problem

What is wrong or missing, with evidence (file:line, test, log, screenshot).

## Plan

1. …

## Invariants at risk

Which invariants this change could break and how they stay protected.

## Tests

Tests to add or update; manual checks needed.

## Docs to update

Only the sources whose content changes (see `docs/workflow.md` §9).

## Evidence (fill when done)

Commands run, test results, PR link, manual verification notes.
