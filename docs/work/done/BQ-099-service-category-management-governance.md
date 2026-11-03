---
id: BQ-099
title: Broken category filter link, hidden service card actions, and native alerts in service modals
type: bug-fix
status: rejected
priority: P1
phase: 8
domain: catalog
requirements: [FR-SERVICE-001, FR-SERVICE-002, FR-SERVICE-003, FR-CATEGORY-001, FR-CATEGORY-002, FR-CATEGORY-003, NFR-UX-005, NFR-SEC-004]
invariants: []
lane: light
approval: none
decision:
launch-blocker: false
depends-on: [BQ-092]
---

# BQ-099: Broken category filter link, hidden service card actions, and native alerts in service modals

> **Ditutup 2026-11-04 (BQ-126 Fase 9): dilebur ke [BQ-132](../BQ-132-layanan-page.md).** Semua temuan di item ini menjadi bagian dari lingkup BQ-132.


Found during the owner UI deep audit on 2026-11-02 across `/owner/categories` and `/owner/services`. Corrected by the Tech Lead review on 2026-11-02 (quotes and line numbers re-checked against the code).

## Problem

### 1. Broken category filter navigation
Each category card has a "Lihat Layanan" link (`resources/views/owner/categories.blade.php:160`):
`href="{{ route('owner.services', ['category_id' => $cat->id]) }}"`.
`app/Http/Controllers/Owner/OwnerProgramController.php:42` reads only `category` (`$request->input('category', '')`), so the link opens `/owner/services?category_id=X`, the parameter is ignored and every service is listed unfiltered.

### 2. Service card actions hidden without hover
The edit/delete buttons on service cards use `opacity-100 sm:opacity-0 sm:group-hover:opacity-100` (`resources/views/owner/programs.blade.php:179`). They are visible on phones (< 640 px), but from 640 px up they appear only on mouse hover: invisible on touch tablets and when reached with the Tab key (WCAG 2.4.7).

### 3. Native alerts in service modals
Image size validation uses the browser's `alert('Ukuran gambar maksimal adalah 10MB.')` in `components/owner/modal-add-program.blade.php:24` and `components/owner/modal-edit-program.blade.php:12`, instead of the shared toast (BQ-092).

### 4. Edit-category form action built by string concatenation
`categories.blade.php:357`: `:action="'{{ url('owner/categories') }}/' + activeCategory.id"`. It works, but the URL is assembled in JavaScript instead of from a named route template (same pattern as BQ-105 §2).

## Plan

1. Use one parameter name: the category card links with `category` (or the controller accepts `category_id`); add the filter state to the services page header.
2. Service card actions: visible at all widths on touch devices, and `focus-within:opacity-100` for keyboard users.
3. Replace `alert()` in both service modals with the shared toast.
4. Build the edit-category action from a named route template passed with `@js`.

## Invariants at risk

None. Service ownership (`TenantScope`) stays enforced server-side.

## Tests

- Feature: the "Lihat Layanan" URL of a category card lists only that category's services.
- Manual: at 768 px on a touch device and with Tab only, card actions are reachable.

## Docs to update

`docs/domains/catalog.md` (Known gaps).

## Evidence (fill when done)

_Fill in when done._
