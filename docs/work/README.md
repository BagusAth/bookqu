# Work

The backlog: every planned, active and finished piece of work, one file per item. The process is in `docs/workflow.md` §3–§4; the phases are in `ROADMAP.md`.

## Files

| File | Purpose |
|---|---|
| `ROADMAP.md` | Phases, gates and which items belong to each phase |
| `_TEMPLATE.md` | Template for a new item |
| `BQ-NNN-slug.md` | One work item (front matter + problem, plan, evidence) |
| `done/` | Finished items, moved here after merge (empty until the first item is done; holds a `.gitkeep`) |

## Finding work

```bash
grep -l "status: proposed" docs/work/BQ-*.md          # waiting for a decision
grep -l "launch-blocker: true" docs/work/BQ-*.md      # must be done before launch
grep -l "phase: 4" docs/work/BQ-*.md                  # items of one phase
grep -l "domain: payment" docs/work/BQ-*.md           # items of one domain
```

## Rules

- New item: copy `_TEMPLATE.md`, take the next free number (`ls docs/work docs/work/done | sort`), never reuse a number.
- `status` changes only as described in `docs/workflow.md` §4; the lead approves items marked `needs-lead-approval: true`.
- Evidence (tests, commands, PR, manual checks) is written into the item before it is set to `done`.
- Items BQ-040 … BQ-066 were migrated verbatim from the 2026-10 defect audit (removed from `docs/`; available in Git at `a68ffe8`); their status in the current code must be verified first (BQ-039).
