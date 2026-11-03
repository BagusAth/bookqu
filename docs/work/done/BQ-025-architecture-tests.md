---
id: BQ-025
title: Enforce architecture rules with tests
type: improvement
status: done
priority: P1
phase: 3
domain: —
requirements: []
invariants: [INV-BOOK-03, INV-PAY-05, INV-SUB-01]
launch-blocker: false
needs-lead-approval: false
depends-on: []
---

# BQ-025: Enforce architecture rules with tests

Rule list decided in [ADR-0012](../../adr/ADR-0012-module-boundaries-and-naming.md) (single writers, provider SDK, Blade queries, occupancy, entitlement, scope bypasses, `module:` gate) with a shrinking allowlist, 2026-10-30.

## Problem

The rules in `docs/architecture.md` are only written down; violations already exist (see "Known gaps" in `docs/domains/`).

## Plan

PHPUnit tests that scan the code with an allowlist of known violations that may only shrink: no `Midtrans\` outside Infrastructure, no `App\Models` or `DB::` in Blade, no `subMinutes(15)` / occupancy status lists outside `BookingRules`, no plan-name checks outside `EntitlementRules`, Tier 3 routes behind the gate.

## Evidence

- `tests/Guardrails/ArchitectureRulesTest.php` (new `Guardrails` suite in both PHPUnit configs; plain PHPUnit, no app boot, no new dependency) checks six rules:

  | Rule | Baseline violations | Removed by |
  |---|---|---|
  | Provider SDK (`Midtrans\…`) only in `app/Infrastructure` | 3 | BQ-032 |
  | No model/DB queries in Blade | 5 | BQ-035 |
  | Occupancy rule (15-minute window, active status lists) only in `app/Domain/Booking` | 9 | BQ-012 |
  | Plan-name checks only in `EntitlementRules` | 9 | BQ-033 |
  | Every `withoutGlobalScope(s)` has a `scope-bypass:` reason (ADR-0010) | 58 | BQ-009 |
  | Payment `sukses`/`gagal` written only by the Payment lifecycle | 39 | BQ-067 / BQ-032 |

- `tests/Guardrails/architecture-allowlist.php` counts violations per file. A higher count fails ("new violation"), and so does a lower one ("lower the entry"), so the list can only shrink.
- Probed: a new `use Midtrans\Snap;` file and a stale allowlist count each fail with a message naming the file. After restoring both, the suite passes.
- Deferred: "staged routes carry `module:`" needs the ADR-0011 registry, so it moves to BQ-008 step 4.
- The patterns are text scans, so they are heuristics. The first run counted adapter namespaces (`Payments\Midtrans\…`) and model constants in Blade; both patterns were narrowed before the baseline was taken.
- GitHub Actions run https://github.com/BagusAth/bookqu/actions/runs/41039402279 (PR #1, 2026-10-30): `Quality checks`, `Tests (SQLite)` and `Tests (MySQL)` passed.
