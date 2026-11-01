---
id: BQ-099
title: Broken category filter link, service modal governance, and unsafe script interpolation
type: bug-fix
status: approved
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

# BQ-099: Broken category filter link, service modal governance, and unsafe script interpolation

Found during the owner UI deep audit on 2026-11-02 across `/owner/categories` and `/owner/programs`.

## Problem

### 1. Broken Category Filter Navigation
On the categories page (`/owner/categories`), each category card provides a "Lihat Layanan" link intended to view all services under that category:
- `resources/views/owner/categories.blade.php:160`:
  `href="{{ route('owner.programs', ['category_id' => $category->id]) }}"`
- However, `app/Http/Controllers/Owner/OwnerProgramController.php:42` and `resources/views/owner/programs.blade.php:87, 107` only read the `category` query parameter (`request('category')`).
- Because the parameter name is `category_id` instead of `category`, clicking "Lihat Layanan" lands on `/owner/programs?category_id=X`, the controller ignores it, and all tenant services are displayed unfiltered.

### 2. Unsafe JS Interpolation and Raw String Concatenation
- `resources/views/owner/programs.blade.php:240`: Blade interpolation `includes({{ $stf->id }})` injects raw values into Alpine attributes rather than using `@js()`.
- `resources/views/owner/categories.blade.php:357`: Form action is constructed via string concatenation in Alpine:
  `:action="'{{ url('/owner/categories') }}/' + (activeCategory ? activeCategory.id : '')"`
  instead of leveraging a consistent route template or standard REST action binding.
- Image size and format validations in `programs.blade.php` and `modal-add-program.blade.php` trigger browser-native `alert('Ukuran file maksimal 2MB')`, violating BQ-092 toast and notification consistency.

### 3. Inaccessible Hover-Only Action Controls (WCAG 2.4.7)
- In `resources/views/owner/programs.blade.php:229-236`, the service card action buttons ("Ubah" and "Hapus") are styled with `opacity-0 group-hover:opacity-100 transition-opacity`.
- On touch devices (tablets and mobile viewports 375px–768px without pointer hover) and for keyboard navigation using the Tab key, the buttons remain completely invisible even when focused, violating WCAG 2.4.7 (Focus Visible).

## Plan

1. Align the category filter parameter name across `categories.blade.php`, `OwnerProgramController.php`, and `programs.blade.php` to consistently accept `category` (supporting both category ID and slug).
2. Refactor raw Blade-to-JS interpolations to use `@js()` and remove client-side `alert()` dialogs in favor of BQ-092 toast notifications.
3. Ensure service card actions have persistent visibility on mobile/touch viewports and include `focus:opacity-100` / `focus-within:opacity-100` for keyboard accessibility.
4. Clean up form action URL construction in `categories.blade.php` edit modal using a clean data-bound route helper pattern.

## Invariants at risk

None. Service ownership (`TenantScope`) and schedule deletion protection remain enforced server-side.

## Tests

- Feature test: visiting `route('owner.programs', ['category' => $category->id])` filters services strictly to that category.
- Feature test: clicking "Lihat Layanan" URL from category card renders only matching services.
- Accessibility / browser check: Tab focus reveals service card action buttons without requiring mouse hover.

## Docs to update

`docs/domains/catalog.md` (Known gaps).

## Evidence (fill when done)

_Fill in when done._
