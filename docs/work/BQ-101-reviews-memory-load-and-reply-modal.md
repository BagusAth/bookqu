---
id: BQ-101
title: Reviews uncapped memory load, unescaped search interpolation, and reply modal governance
type: bug-fix
status: approved
priority: P2
phase: 8
domain: customers-reviews
requirements: [FR-REVIEW-004, FR-REVIEW-005, FR-REVIEW-006, NFR-PERF-004, NFR-SEC-004]
invariants: []
lane: light
approval: none
decision:
launch-blocker: false
depends-on: [BQ-092]
---

# BQ-101: Reviews uncapped memory load, unescaped search interpolation, and reply modal governance

Found during the owner UI deep audit on 2026-11-02 in `/owner/reviews`.

## Problem

### 1. Unbounded Database Query and Missing Pagination (NFR-PERF-004)
In `app/Http/Controllers/Owner/OwnerReviewController.php:43, 46`:
```php
$reviews = $query->latest()->get();
$allReviews = $allTenantReviews->get();
```
Both the filtered results and tenant reviews collection are loaded entirely via `->get()` into server memory. The view (`resources/views/owner/reviews.blade.php`) contains no pagination links (`$reviews->links()`). For active businesses accumulating hundreds of reviews, this causes excessive memory footprint, slow rendering times, and an inflated HTML payload.

### 2. Unsafe JS Variable Interpolation in Filter Component
In `resources/views/owner/reviews.blade.php:7`:
```blade
search: '{{ addslashes(request('search', '')) }}',
ratingFilter: '{{ request('rating', '') }}',
```
Interpolating request inputs directly into single quotes in an Alpine component definition risks syntax breakage when queries contain quotes or special characters. Values must be initialized safely with `@js()`.

### 3. Reply Modal Governance and URL Hardcoding
In `resources/views/owner/reviews.blade.php:218-283`:
- The form action dynamically builds an un-named endpoint path via client string concatenation:
  `:action="'/owner/reviews/' + activeReview.id + '/reply'"`
- The reply dialog lacks dialog accessibility semantics (`role="dialog"`, `aria-modal="true"`, focus trapping, and return-focus).
- The reply submit button lacks a loading state or double-submit guard (`:disabled`).

## Plan

1. Paginate reviews in `OwnerReviewController.php` (e.g. `paginate(15)->withQueryString()`) and compute summary statistics using targeted database aggregations (`->avg('rating')`, `->count()`) rather than fetching entire collections into memory.
2. Render pagination navigation in `resources/views/owner/reviews.blade.php`.
3. Wrap filter Alpine state initializations in `@js()`.
4. Refactor reply modal form action to follow a clean named route template pattern, attach proper accessibility attributes, and add double-submission prevention.

## Invariants at risk

None. Tenant scope and review reply ownership stay enforced.

## Tests

- Feature test: reviews listing is paginated at 15 items per page and responds with pagination metadata and links.
- Feature test: rating filters and search keywords persist across pagination links.
- Security test: search strings containing quotes and angle brackets do not cause JavaScript syntax errors or unescaped output.

## Docs to update

`docs/domains/customers-reviews.md` (Known gaps).

## Evidence (fill when done)

_Fill in when done._
