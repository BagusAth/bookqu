## Summary

<!-- What changed and why. -->

Lane: <!-- light | heavy (docs/workflow.md §3) -->
Work item: <!-- BQ-NNN (heavy lane), or "none (light lane)" -->
Requirements: <!-- FR-/BR-/NFR- IDs, or "none (refactor/docs)" -->
Type: <!-- bug-fix | feature | refactor | improvement | product-change | docs | ops -->

## Checklist

- [ ] Decision recorded where `AGENTS.md` → "Who decides" requires it (Tech Lead / Product Lead)
- [ ] Tenant isolation and authorization checked
- [ ] Invariants listed in the work item still hold (heavy lane)
- [ ] Tests added/updated; `php artisan test --compact` passes
- [ ] `vendor/bin/pint --dirty --format agent` run
- [ ] Docs updated where content changed (`docs/workflow.md` §9)
- [ ] Branch synced with the latest `integration`
- [ ] No unrelated changes

## Verification

<!-- Tests run, manual checks (UI, payment sandbox, custom domain), screenshots. -->

## Follow-ups

<!-- New BQ items created for findings outside this scope. -->
