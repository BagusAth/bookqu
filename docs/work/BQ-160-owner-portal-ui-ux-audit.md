---
id: BQ-160
title: Fix owner portal frontend defects and UX gaps found in the 2026-11-16 audit
type: improvement
status: approved           # Tech Lead 2026-11-16; D-024 approved by the Product Lead 2026-11-16
priority: P1
phase: 5
domain: tenancy            # cross-cutting: every owner page (docs/owner-portal.md)
requirements: [FR-BOOKING-010, FR-BOOKING-020, FR-VOUCHER-001, FR-VOUCHER-002, FR-TEAM-004, FR-TEAM-006, FR-UNIT-001, FR-UNIT-004]
invariants: [INV-TEN-01, INV-BOOK-01]
lane: heavy                # one bulk PR (Tech Lead exception, see "Bulk execution")
approval: tech-lead
decision: D-024            # the four new actions (UX-05, UX-90, UX-94, webhook test/redeliver)
launch-blocker: false      # the bulk PR should land before the BQ-158 regression pass
depends-on: []
---

## Problem

Audit of every owner page against its contract in `docs/owner-portal.md` §8–§9, done by reading the
Blade views, `public/js/owner-ui.js` and the controllers/actions that feed them (state of
`integration` at `bdfe50c`). The app was not run in the browser during this audit: the cloud session
could not install Composer dependencies (GitHub downloads failed through the proxy). Every finding
below cites code; findings marked *(verify)* should be reproduced in the browser first.

Severity: **A** = bug (wrong behaviour or data loss), **B** = backend capability not reachable from the
UI or contract not met, **C** = UX friction, **D** = consistency/cleanup.

### 1. Shell and shared blocks

| ID | Sev | Finding | Evidence |
|---|---|---|---|
| UX-01 | A | A validation error from **any** form on Beranda (for example the + Booking walk-in dialog) opens the "Lengkapi profil" modal, and that modal lists the walk-in errors. | `owner/dashboard.blade.php:188`, `components/owner/modal-complete-profile.blade.php:4,53` |
| UX-02 | A | Forms inside dialogs submit as full-page POST. On a validation error the page reloads with the dialog closed; only the first error shows as a 5-second toast. Affects the shell walk-in form, reschedule, booking panel actions, slot actions in Kalender, units, add-ons, promos. Only Layanan and Pembayaran reopen their dialog with `old()`. | `components/owner/toast.blade.php:11`, `components/owner/walkin-form.blade.php`, `layouts/owner-layout.blade.php` |
| UX-03 | A | Account menu "Pengaturan akun" links to `owner.settings#account-settings`; that anchor does not exist, so Pemilik/Manajer land on Profil Bisnis instead of Akun (`?tab=account`). | `components/owner/topbar.blade.php:154` |
| UX-04 | A | The walk-in slot list loads only once per page (`loaded` flag). Reopening + Booking later shows stale free slots. | `public/js/owner-ui.js:404` |
| UX-05 | B | Walk-in success only shows a toast; no link to the new booking (code, panel), so the cashier cannot immediately show or send it. | `OwnerBookingController::walkinStore` |
| UX-06 | C | Bottom navigation on phones does not show the "Booking" badge (bookings needing action) that the sidebar shows. | `components/owner/bottom-nav.blade.php` |
| UX-07 | C | Global search: listbox without arrow-key navigation (`role="combobox"` but no active descendant); Enter only opens the first result. | `components/owner/topbar.blade.php`, `ownerSearch` |
| UX-08 | D | Unused components: `modal-view-booking`, `stat-card`, `trial-banner`. | `resources/views/components/owner/` |

### 2. Beranda (§8.1)

| ID | Sev | Finding | Evidence |
|---|---|---|---|
| UX-10 | A | "Booking selesai yang belum ditandai" links to `?status=paid`. `paid` is not a tab key, so the tab bar falls back to highlighting "Perlu tindakan" while the list shows every paid booking (also future ones). Should link to `?tab=action`. | `app/Actions/Dashboard/GetBeranda.php:293`, `components/owner/tabs.blade.php:16` |
| UX-11 | A | "Permintaan refund menunggu keputusan" links to `/owner/bookings` (tab Semua), not to the refund requests (AC-BRD-04). | `GetBeranda.php:279` |
| UX-12 | B | Today's rows offer only "Tandai selesai"; the contract also asks for "Tidak datang" from the row. "Tandai selesai" appears only after the session ended, while the panel allows it once the session has started: two different rules on two entry points. | `owner/dashboard.blade.php:120`, `GetBeranda.php:340`, `GetOwnerBookingPanel.php:110` |
| UX-13 | C | "Tandai selesai" from the row submits immediately, without confirmation; an accidental tap at the counter cannot be undone. | `owner/dashboard.blade.php:120` |
| UX-14 | C | Quick action "+ Layanan"/"+ Promo" only navigate to the list; they should open the create dialog (`?buat=1` like Jam Buka). | `GetBeranda.php:405-409` |

### 3. Booking list and booking panel (§8.3, §8.4)

| ID | Sev | Finding | Evidence |
|---|---|---|---|
| UX-20 | A | Success message for "Tidak datang" reads "…ditandai sebagai no_show": the label `match` has no `no_show` arm. | `OwnerBookingController.php:190-196` |
| UX-21 | B | Tab "Perlu tindakan" lists bookings but not *why* each needs action (refund request, Kasir cancellation request, ended without outcome). The owner must open each panel to find out. | `owner/bookings.blade.php`, `ListOwnerBookings.php:60` |
| UX-22 | B | Kasir cancellation requests show `Booking #<internal id> · reason` only: no booking code, customer, session, requester; approve/reject submit without confirmation and without a rejection reason; no link to the panel. | `owner/bookings.blade.php:65` |
| UX-23 | B | Rows lack participants and unit (contract §8.3 Rows). | `owner/bookings.blade.php` |
| UX-24 | C | Panel footer renders a reason textarea for every action that needs one at the same time (Batalkan + Tolak refund ⇒ two textareas), above all buttons. The reason should appear after choosing the action. | `components/owner/booking-panel.blade.php` footer |
| UX-25 | C | "Tidak datang" and "Tandai selesai" have no confirmation; refund "Terima 100%/50%" confirmations do not show the amount ("Rp …"). | `GetOwnerBookingPanel.php:112-147` |
| UX-26 | C | When no action is offered (online payment still open, multi-slot booking that cannot be rescheduled) the panel shows nothing; it should say why ("Tindakan tersedia setelah pembayaran selesai"). | `GetOwnerBookingPanel.php:90,98` |
| UX-27 | C | Filters need "Terapkan"; there is no "Reset filter", and the whole row is not clickable (only the code). | `owner/bookings.blade.php` |

### 4. Reschedule dialog

| ID | Sev | Finding | Evidence |
|---|---|---|---|
| UX-30 | A | `minDate` uses `toISOString()` (UTC). Between 00:00 and 07:00 WIB the earliest date offered is yesterday. | `components/owner/modal-reschedule-booking.blade.php:15` |
| UX-31 | A | The reason is prefilled with "Permintaan langsung customer walk-in di tempat" for every booking, including online ones, and is stored in the booking history. | same file `:20` |
| UX-32 | C | Not built on `x-owner.dialog` (no focus trap, no `role="dialog"`, global Escape listener), legacy colours, "menu Schedule" copy. No summary of the price difference before submitting (AC-PNL-05 shows it only per slot card). | same file |

### 5. Kalender (§8.2)

| ID | Sev | Finding | Evidence |
|---|---|---|---|
| UX-40 | C | Slot states (free, booked, closed, past) differ only in text; no colour or legend. Month view shows only "N slot" per day, not bookings. | `owner/partials/calendar/slot.blade.php`, `range-view.blade.php` |
| UX-41 | C | "Tutup slot ini" / "Ubah harga slot ini" are full-page POSTs that close the dialog and reset scroll; walk-in in the same dialog is already async. Clearing the special price is not explained ("kosongkan untuk mengikuti aturan harga"). | `owner/partials/calendar/detail-modal.blade.php` |
| UX-42 | C | Filters need "Terapkan"; changing a select should apply it. | `owner/partials/calendar/controls.blade.php` |

### 6. Pelanggan (§8.5)

| ID | Sev | Finding | Evidence |
|---|---|---|---|
| UX-50 | C | Closing the customer panel discards an unsaved internal note without warning. | `components/owner/customer-panel.blade.php` |
| UX-51 | C | No sort (most bookings, highest spend, last visit); the quick-filter hints exist only as `title` tooltips (invisible on phones). | `owner/customers.blade.php` |

### 7. Layanan, Kategori, Add-on (§8.6)

| ID | Sev | Finding | Evidence |
|---|---|---|---|
| UX-60 | A | Add-on delete confirmation puts `{{ $item->name }}` inside a quoted JS string (forbidden by `.ai/rules/frontend.md`, cf. BQ-043): a name with `'` breaks the button, and the value is not JS-escaped. | `owner/additional-items.blade.php:181` |
| UX-61 | B | The service form has no "Linked add-ons" field (contract §8.6, Medium and up); links are only editable from the add-on side. | `owner/services.blade.php` |
| UX-62 | C | "Durasi (menit)" is always minutes, but "Satuan durasi" is free text and is printed next to the number: choosing "jam" for 60 minutes shows "60 jam" on the card and to customers. | `owner/services.blade.php:263-268`, card line `priceUnit · duration durationUnit` |
| UX-63 | C | Validation errors in the service/category dialog are reopened but not shown next to the field (no `@error`); the chosen gallery image is lost on reopen. | `owner/services.blade.php` init() |
| UX-64 | B | Add-on page is still the legacy page: English headings ("Additional Items", "Item Name", "Price", "Applicable Services", "Stock", "Actions", "Active/Inactive", "Unlimited"), own modals instead of `x-owner.dialog`, hard-coded colours (86 occurrences), header above the tabs, dialogs closed after a validation error, no "sold count" (contract). | `owner/additional-items.blade.php` |
| UX-65 | C | Add-on copy says stock decreases "setiap kali dipesan customer"; the rule is "only when a booking is paid" (§8.6). | `owner/additional-items.blade.php:297` |
| UX-66 | C | Deactivating a service with future bookings gives no warning about those bookings. | `owner/services.blade.php` toggle form |

### 8. Promo (§8.10)

| ID | Sev | Finding | Evidence |
|---|---|---|---|
| UX-70 | A | **Editing a promo erases its maximum discount and its service restriction.** The forms have no `max_discount_amount` and `applicable_services` fields, and `update()` writes `null` / `'all'` when they are absent. | `owner/vouchers.blade.php` forms, `OwnerVoucherController.php:121-150` |
| UX-71 | B | Creating a promo cannot set maximum discount or services, though the backend accepts both (contract §8.10 form fields). | `OwnerVoucherController.php:80-86` |
| UX-72 | A | Delete confirmation puts `{{ $v->code }}` inside a quoted JS string (same rule as UX-60). | `owner/vouchers.blade.php:140` |
| UX-73 | C | "Total N voucher promo" counts the current page only; the status pill is a hidden toggle button; mixed English ("% OFF", "Unlimited", "Min. Order") and three names (promo, voucher, kupon); copy toast says "disalin" even when the clipboard call fails; percentage value has no `max=100`. | `owner/vouchers.blade.php` |
| UX-74 | C | Cards do not show the state (Terjadwal/Berakhir), maximum discount, services or discount given per promo. | same |

### 9. Ulasan, Unit (§8.11, §8.7)

| ID | Sev | Finding | Evidence |
|---|---|---|---|
| UX-80 | C | The reply dialog does not show the review being answered (rating and comment). | `owner/reviews.blade.php` reply dialog |
| UX-81 | A | Unit form ignores `old()`: after a validation error the fields are empty, and `$errors->any()` always opens "Tambah unit", even when an edit failed. | `owner/partials/unit-form.blade.php:13-16`, `owner/units.blade.php:11` |
| UX-82 | C | "Hapus unit" deletes without the shared confirm dialog. | `owner/units.blade.php:30` |
| UX-83 | B | Unit list lacks "services using it" and "share used today"; conflict list shows internal ids (`#123`) without date, time or customer. | `owner/units.blade.php` |

### 10. Jam Buka & Harga (§8.8)

| ID | Sev | Finding | Evidence |
|---|---|---|---|
| UX-90 | B | Opening hours do not feed slot creation: the owner types start/end times again in "Buat slot sekaligus". A "Buat slot dari jam buka" prefill would remove the most error-prone step for new owners. | `owner/schedule/hours.blade.php`, `slots.blade.php` |
| UX-91 | C | No "Salin ke semua hari" for opening hours; a closed day is "no interval", not an explicit "Tutup" toggle. | `owner/schedule/hours.blade.php` |
| UX-92 | C | Deleting a peak rule, a special date and reopening a closed date submit without confirmation. | `owner/schedule/prices.blade.php`, `closed.blade.php` |
| UX-93 | C | Special-date services use `<select multiple>` (Ctrl-click, unusable on phones). The weekend rule does not say which days count as weekend. | `owner/schedule/prices.blade.php` |
| UX-94 | C | Price simulator shows only time and price, not which rule produced it (base, weekend, peak, special, override). | `owner/schedule/prices.blade.php`, `schedulePriceSimulator` |
| UX-95 | C | Slot tab: three forms per row, no multi-select close/delete, occupied slots show no link to their booking. Closing a date posts hidden weekend-price fields to the same endpoint, so an invalid weekend value blocks closing a date *(verify)*. | `owner/schedule/slots.blade.php`, `closed.blade.php:4-5` |

### 11. Halaman Booking, Pembayaran, Laporan (§8.9, §8.12, §8.13)

| ID | Sev | Finding | Evidence |
|---|---|---|---|
| UX-100 | C | DNS records (CNAME, A, TXT) have no copy buttons; the TXT value is long and easy to mistype. | `owner/booking-page.blade.php` DNS section |
| UX-101 | C | Business description is editable both in Tampilan and in Pengaturan → Profil Bisnis (one field, two homes, §3 rule 2). | `owner/booking-page.blade.php`, `owner/settings/profile.blade.php` |
| UX-102 | C | Landing "Pratinjau" opens the saved version, not the unsaved form state; switching section tabs drops unsaved Tampilan changes without warning. | `owner/booking-page.blade.php` |
| UX-103 | C | Transaksi tab: no search, status/date filter or export; booking code is not a link to the booking panel. | `owner/partials/payments/transactions.blade.php` |
| UX-104 | C | Ledger header "Jumlah mutasi" is ambiguous next to "Saldo tersedia". | `owner/payments.blade.php` |

### 12. Pengaturan, Tim, Integrasi, API (§8.14)

| ID | Sev | Finding | Evidence |
|---|---|---|---|
| UX-110 | B | Tim is a separate unstyled page (raw `indigo`/`red` classes, no page header), not the Tim tab of Pengaturan. Missing per contract: seat usage "x dari y", status, last sign-in. Revoke access and cancel invitation submit without confirmation. | `owner/team.blade.php` |
| UX-111 | A | Activity log shows raw identifiers and raw timestamps: "2026-11-16 10:12:00 · Siska (Kasir) · services.updated #5" (event names like `schedules.created`, `team.invited` have no translation). | `owner/team.blade.php:59`, `app/Support/TenantActor.php:53` |
| UX-112 | C | Settings tabs Profil, Kebijakan, Akun, Notifikasi have no inline `@error` messages; only the first error appears as a toast. | `owner/settings/*.blade.php` |
| UX-113 | C | Policy form: deadlines in hours without a human preview next to the field ("24 jam = 1 hari"); refund toggle does not explain consequences. | `owner/settings/policy.blade.php` |
| UX-114 | B | Integrasi and API are separate pages instead of the Integrasi tab; "Putuskan koneksi" and "Cabut" (API key) / "Hapus" (webhook) have no confirmation; the one-time API secret has no copy button; webhook deliveries show raw status values and offer no test/redeliver. | `owner/integrations.blade.php`, `owner/api.blade.php` |

### 13. Paket & Tagihan, checkout, Bantuan

| ID | Sev | Finding | Evidence |
|---|---|---|---|
| UX-120 | C | Checkout does not show the resulting end date before paying (D-002: renewal extends from end date, plan change starts on payment date). | `owner/checkout.blade.php` |
| UX-121 | D | Checkout pages use legacy styling and copy ("PRO TIER", "STARTER", "Kembali ke Subscription"); storefront link built with `url('/'.$slug)` instead of `CustomerBookingRoutes` (wrong for a custom domain). | `owner/checkout.blade.php:110,210-225,354` |
| UX-122 | C | Bantuan "Buka panduan awal di Beranda" points to a guide that is hidden for good once completed (AC-BRD-05). | `owner/help.blade.php` |


## Verification (2026-11-16, Tech Lead session)

The audit came from a cloud session that only read code. Before approving it, every severity-A finding was
checked against `integration` at `4b54a05` (only docs changed since `bdfe50c`):

| ID | Confirmed by |
|---|---|
| UX-70 | The create and edit forms of `owner/vouchers.blade.php` post no `max_discount_amount` and no `applicable_services`; `OwnerVoucherController::update()` writes `max_discount => null` and `applicable_services => 'all'` (`:151`, `:155`). **Editing a promo erases both.** |
| UX-01 | `owner/dashboard.blade.php:188` opens the profile modal on `$errors->any()`; the modal lists every error (`modal-complete-profile.blade.php:4,53-57`). |
| UX-03 | `components/owner/topbar.blade.php:154` links `#account-settings`. |
| UX-04 | `public/js/owner-ui.js:398-419`: `load()` runs only while `loaded` is false. |
| UX-10, UX-11 | `GetBeranda.php:293` (`['status' => 'paid']`) and `:279` (plain `owner.bookings`). |
| UX-20 | `OwnerBookingController.php:192-196`: the label `match` has no `no_show` arm. |
| UX-30, UX-31 | `modal-reschedule-booking.blade.php:15` (`toISOString()`), `:20` (walk-in reason prefilled). |
| UX-60, UX-72 | `additional-items.blade.php:181`, `vouchers.blade.php:140`; forbidden by `.ai/rules/frontend.md:19`. |
| UX-81 | `owner/partials/unit-form.blade.php` has no `old()`; `owner/units.blade.php:11` opens on any error. |
| UX-111 | `owner/team.blade.php:59` prints `__($entry->event)`, and `lang/` has no keys for events such as `services.updated`. |

Not yet verified one by one: severity B, C and D. Each PR re-checks its own items before changing code and
records a finding that does not hold as `skipped (not reproduced)` in its report.

Related contract facts found during verification:

- UX-12: `owner-portal.md` §8.4 says "Tandai selesai" is offered when the booking is `paid` and the session
  **has started**; the panel follows it (`GetOwnerBookingPanel.php:109`), Beranda requires the session to have
  **ended** (`GetBeranda.php:340`). The contract wins; the rule moves into `BookingRules` so both use one source.
- UX-90 is not D-021's "slots generated automatically from opening hours" (deferred): it only prefills the
  bulk-slot form, and the owner still reviews and saves. It is a new action, approved in D-024.

## Bulk execution (Tech Lead exception, 2026-11-16)

The Tech Lead approved BQ-160 and decided that **all of it ships in one branch and one PR**, as a one-time
exception to "one finished task per PR" (`docs/workflow.md` §6) and to the per-PR split below. The Product Lead
approved D-024 the same day, so PR 9 is no longer blocked.

- Branch: `agathan-bq-160-owner-portal-fixes` (already created from `integration` at `921a36e`).
- One PR into `integration`, reviewed and merged by the Tech Lead as a heavy-lane PR.
- The "PR" rows below become **sections of the same branch**, done in the listed order, with one or more
  commits per section so the Tech Lead can review section by section.
- What stays: the non-negotiable rules in `AGENTS.md` (tenant isolation, server-side authorization, booking
  states only through `BookingState::canTransition`, no business logic in Blade/JS, never edit a test to make
  it pass), the tests of each section, and the four green CI jobs before merge. They protect the result, not
  the process.
- What is waived: separate branches, separate PRs, separate approval per section, and stopping for a decision
  on the D-024 actions.

## Plan

Severity A first, then the shared form pattern that later PRs build on, then contract gaps and friction.
One PR per row, each from the latest `integration` on its own branch `agathan-bq-160-<suffix>`.
Recommended: PR 1 and PR 2 land before the BQ-158 regression pass; the others may follow before or after the
release (Tech Lead decides per PR).

| PR | Branch suffix | Items | Lane |
|---|---|---|---|
| 1 | `1-promo-fields` | UX-70, UX-71, UX-72, percentage limit from UX-73 | **Heavy** (promo data) |
| 2 | `2-bugs` | UX-01, UX-03, UX-04, UX-10, UX-11, UX-20, UX-30, UX-31, UX-60, UX-81, UX-111 | Light |
| 3 | `3-form-errors` | UX-02, UX-63, UX-112 (one shared pattern) | Light (a new building block in `owner-portal.md` §9 needs Tech Lead review) |
| 4 | `4-booking` | UX-12, UX-13, UX-21, UX-22, UX-23, UX-24, UX-25, UX-26, UX-27 | **Heavy** for UX-12 (rule in `BookingRules`), rest light |
| 5a | `5a-addon-page` | UX-64, UX-65 | Light |
| 5b | `5b-reschedule-dialog` | UX-32 | Light |
| 5c | `5c-settings-tabs` | UX-110, UX-114 without webhook test/redeliver | Light (old URLs redirect, `owner-portal.md` §10) |
| 5d | `5d-checkout` | UX-120, UX-121 | Light |
| 6 | `6-schedule` | UX-40, UX-41, UX-42, UX-91, UX-92, UX-93, UX-95 | Light |
| 7a | `7a-shell` | UX-06, UX-07, UX-14 | Light |
| 7b | `7b-catalog` | UX-50, UX-51, UX-61, UX-62, UX-66, rest of UX-73, UX-74, UX-80, UX-82, UX-83 | Light (UX-62: stop if the fix changes what customers see beyond the unit label) |
| 7c | `7c-pages` | UX-100, UX-101, UX-102, UX-103, UX-104, UX-113, UX-122 | Light |
| 8 | `8-cleanup` | UX-08, legacy colours left in add-on, promo, reschedule and checkout views | Light |
| 9 | `9-d024` | UX-05, UX-90, UX-94, webhook test/redeliver (UX-114) | Light; D-024 approved; contract in `owner-portal.md` §8 first (§14) |

PR 3 goes before PR 4–7, because those PRs reuse its pattern. After PR 3, PR 5a–5d, 6 and 7a–7c touch
different files and may run in parallel worktrees (`docs/workflow.md` §6).

## Invariants at risk

- INV-TEN-01: every new list, link or field (promo services, unit "services using it", cancellation-request
  details, transaction search) reads only the current tenant through the existing scoped queries; promo
  `applicable_services.*` stays validated against the tenant's services.
- INV-BOOK-01: UX-12 changes only *where* the "session has started" check lives; state changes still go
  through `BookingState::canTransition` and the existing actions. The server-side status endpoint applies the
  same rule as the UI.
- Promo quota locking in `OwnerVoucherController::update()` (`ClaimSlots::lockTenant`, held reservations)
  stays unchanged in PR 1.
- Async dialog submissions (if a PR uses `fetch`) keep server-side validation and authorization; JavaScript
  stays presentation only (`AGENTS.md` rule 11).

## Tests

- PR 1: updating a promo without touching max discount or services keeps both; create and update store both;
  a service of another tenant is rejected; a percentage above 100 is rejected; a code containing `'` renders
  safely in the delete confirmation.
- PR 2: a walk-in validation error on Beranda does not open the profile modal (UX-01); Beranda links target
  the right tab (UX-10, UX-11); the `no_show` success message (UX-20); the unit form keeps old input and
  reopens the edited unit (UX-81); the activity log shows a human label (UX-111).
- PR 4: "Tandai selesai" is offered on Beranda and in the panel for the same booking at the same time, and the
  status endpoint refuses it before the session starts; "Tidak datang" from a Beranda row.
- Every PR: related feature tests in `tests/Feature/Owner/`, full suite, `OwnerSidebarNavigationTest` and
  `OwnerUiAuditTest` green; browser check at 375 px and desktop for each page touched.

## Docs to update

- `docs/owner-portal.md`: §9 if PR 3 adds a shared building block; §8 contracts for PR 9 after D-024.
- `docs/domains/booking.md` for the `BookingRules` change in PR 4.
- This item: one evidence row per PR.

## Agent briefs

Bulk mode (current): paste only the **bulk brief** into one agent session. It points the agent to the shared
brief and the section briefs below, which stay the detailed checklist for each section.

### Bulk brief

```text
You are implementing all of BQ-160 in the BookQu repository, in ONE branch and ONE pull request.
The Tech Lead approved this bulk mode on 2026-11-16 and the Product Lead approved D-024 the same day, so you
do not split the work into several PRs and you do not stop for approval between sections.

Setup
1. git fetch origin; git switch agathan-bq-160-owner-portal-fixes; git merge origin/integration.
2. Read docs/work/BQ-160-owner-portal-ui-ux-audit.md completely: findings, Verification, Bulk execution,
   Plan, Invariants at risk, Tests, and every brief under "Agent briefs".
3. Follow the "Shared brief" for how to work and verify, with these bulk-mode differences:
   - stay on agathan-bq-160-owner-portal-fixes for everything (no new branches);
   - treat each Plan row ("PR 1" … "PR 9") as a section of this branch, in this order:
     1, 2, 3, 4, 5a, 5b, 5c, 5d, 6, 7a, 7b, 7c, 9, 8 (cleanup last);
   - commit at least once per section, message "type(owner): summary (BQ-160 §<section>)";
   - the "stop and report" cases in the briefs become: skip that one item, note why, continue with the rest;
   - ignore the shared brief's "do not push and do not open a PR": in bulk mode you push this branch and
     open the one PR at the end (see Finish).

Per section
- Read the docs/owner-portal.md parts and the .ai/rules files that cover the files you touch.
- Confirm each UX item in the code first; if it does not hold, mark it "skipped (not reproduced)".
- Write the failing test first where behaviour is testable, then fix, then run that test file.
- Section 9 (D-024): first add the four approved actions to the page contracts in docs/owner-portal.md §8,
  each with a new AC ID (§14), then build them.

Rules that stay in force (they protect the result)
- Tenant isolation: every query stays scoped to the current tenant; ids from the request are checked.
- Authorization on the server through TenantPermission and EntitlementRules; hiding a button is not enough.
- Booking states change only through BookingState::canTransition and the existing Actions.
- No queries or business decisions in Blade or JavaScript; PHP values into JS with @js() / Js::from().
- No new dependency and no database schema change. If an item truly needs one, skip it and report it.
- Never edit an existing test just to make it pass; a failing test is evidence.

Finish
- php artisan test --compact (full suite) passes.
- vendor/bin/pint --dirty --format agent; vendor/bin/phpstan analyse --memory-limit=1G with no new errors.
- npm run build.
- Browser check at 375 px and desktop of every owner page you touched, as Pemilik and as Kasir.
- Update docs/domains/booking.md (BookingRules change), docs/owner-portal.md (§8 for D-024, §9 if you added
  a shared building block), and fill the Evidence table of BQ-160: one row per section with items fixed,
  items skipped and why, tests added, and manual checks.
- Push the branch and open ONE PR into integration using .github/pull_request_template.md, titled
  "fix(owner): owner portal UI/UX fixes (BQ-160)". No Co-Authored-By or other attribution lines.
- Report to the developer: per section what was fixed and skipped, the commands you ran with their results,
  "Found, not fixed", and the PR link.
```

Per-PR mode (not used now): paste the **shared brief** and then the brief of one PR into a fresh agent
session. One PR per session.

### Shared brief

```text
You work in the BookQu repository (Laravel 13, Blade + Alpine + Tailwind). The repository rules win over
any personal habit.

Before coding:
1. Read AGENTS.md, docs/workflow.md §3–§8, docs/work/BQ-160-owner-portal-ui-ux-audit.md (findings,
   Verification, Plan, Invariants, Tests) and the docs/owner-portal.md sections of the pages you touch.
2. Read .ai/rules/index.md and every rule file whose globs match the files you will touch (at least
   frontend.md, http-layer.md and tests.md; booking-availability.md and actions-domain.md when you touch
   app/Domain or app/Actions; tenancy-security.md for owner controllers).
3. git fetch; create branch agathan-bq-160-<suffix> from origin/integration (suffix from the Plan table).

While coding:
- Do only the UX items of your PR. For each item, confirm it in the code first (and in the browser for items
  marked (verify)); if it does not hold, mark it "skipped (not reproduced)" and move on.
- Where behaviour is testable, write the failing feature test first, then fix.
- Pass PHP values into Alpine/JS with @js() or Js::from(), never {{ }} inside a quoted JS string.
- UI copy in Indonesian, consistent with the rest of the portal (say "promo", not "voucher" or "kupon").
- Reuse the existing owner components (x-owner.dialog, the shared confirm dialog, tabs, toast) and the bq-*
  colour tokens.
- No queries or business decisions in Blade or JavaScript; thin controllers (validate → Action → respond).
- Never edit a test just to make it pass. No new dependency, no schema change, no change to booking, payment
  or entitlement rules unless your PR brief says so. If an item needs one, stop and report.
- Anything else you notice goes into your report under "Found, not fixed"; do not fix it in passing.

Before finishing:
- php artisan test --compact (narrow test, related tests, then the full suite)
- vendor/bin/pint --dirty --format agent
- vendor/bin/phpstan analyse --memory-limit=1G (no new errors; never extend the baseline)
- npm run build when Blade classes, CSS or JS changed
- Browser check at 375 px and desktop for every page touched, as Pemilik and, where it matters, as Kasir.
- Commit with Conventional Commits that mention BQ-160, e.g. "fix(owner): keep promo limits on edit (BQ-160)".
  No Co-Authored-By or other attribution trailers. Do not push and do not open a PR unless the developer
  asks.
- Add your row to "Evidence" in BQ-160: PR number (or "local"), items fixed / skipped with reason, tests added,
  commands run and their result, manual checks.
- Report to the developer: the same summary, plus "Found, not fixed".
```

### PR 1 — Promo keeps its limits (heavy)

```text
Items: UX-70, UX-71, UX-72 and the percentage limit from UX-73. Requirements FR-VOUCHER-001, FR-VOUCHER-002.
Files: resources/views/owner/vouchers.blade.php, app/Http/Controllers/Owner/OwnerVoucherController.php,
the promo tests in tests/Feature/Owner.

1. Characterize first: a feature test that creates a promo with a max discount and two services, then sends
   the update request with only the fields today's edit form posts, and asserts both are kept. It must fail.
2. Add "Diskon maksimum" (percentage only) and "Berlaku untuk layanan" (all, or chosen services) to the create
   and edit forms, prefilled on edit from the data already prepared for activeVoucher. The service list comes
   from the controller (current tenant only), never from a Blade query.
3. The form always sends both fields, so a missing field never means "erase". Keep the transaction,
   lockTenant and the held-reservation check exactly as they are.
4. Server-side: a percentage discount_value is at most 100. Client-side max="100" when percentage is chosen.
5. UX-72: the delete confirmation uses @js($v->code).
Tests: the characterization test passes; create stores both fields; a service id of another tenant is
rejected; percentage 101 is rejected; a code with a quote renders safely. Update docs/domains/catalog.md only
if it describes promo fields.
```

### PR 2 — Severity-A bugs (light)

```text
Items: UX-01, UX-03, UX-04, UX-10, UX-11, UX-20, UX-30, UX-31, UX-60, UX-81, UX-111.

- UX-01: the profile modal opens only for its own form. Give the profile form a form key (hidden _form or a
  named error bag) and open the modal on that key, not on $errors->any(). Test: a walk-in validation error on
  Beranda does not render the profile modal open.
- UX-03: "Pengaturan akun" links to route('owner.settings', ['tab' => 'account']); hide it for roles that may
  not open that tab (TenantPermission decides, never a role-name comparison).
- UX-04: reload the walk-in slot list every time the dialog opens (keep a loading state; no double requests).
- UX-10: link to the "Perlu tindakan" tab (use the tab key ListOwnerBookings and the tabs component use).
  UX-11: link to where refund requests are listed. Test both links in BerandaPageTest.
- UX-20: add the no_show arm ("tidak datang") to the label match; test the flash message.
- UX-30: compute the earliest date on the server in the app timezone and pass it with @js(); no toISOString().
- UX-31: no prefilled reason; keep the placeholder.
- UX-60: @js($item->name) in the add-on delete confirmation.
- UX-81: the unit form uses old() and a form key, so a failed edit reopens that unit and a failed create
  reopens "Tambah unit". Test it.
- UX-111: the activity log shows a human label per event and a formatted local time ("16 Nov 2026 10.12").
  Event labels live in lang/ or in the code that already builds the log, not inline in Blade; an unknown event
  falls back to a generic label, never the raw key. Test one known and one unknown event.
```

### PR 3 — One pattern for form errors (light)

```text
Items: UX-02, UX-63, UX-112. Read owner-portal.md §9 (shared building blocks) first.

Pattern (server-rendered, no new JavaScript library):
- Every form inside a dialog posts a hidden form key (an edit form includes the record id).
- After a redirect with errors, the dialog whose key matches old('_form') reopens, filled from old().
- Every field shows its own @error message; the toast stays a summary, not the only place an error appears.
Apply it to: the shell walk-in form, reschedule, booking panel actions that take a reason, Kalender slot
actions, units (align with PR 2), add-ons, promos, the service/category dialog (keep the chosen gallery image
on reopen), and the Pengaturan tabs Profil, Kebijakan, Akun, Notifikasi.
If it becomes a reusable component, describe it in owner-portal.md §9 in one short paragraph and say so in
your report: that doc change needs Tech Lead review.
Tests: for two representative dialogs (one on a page, one in the shell) a validation error reopens the right
dialog with old input and an inline error.
```

### PR 4 — Booking list, panel and Beranda rows (heavy for UX-12)

```text
Items: UX-12, UX-13, UX-21, UX-22, UX-23, UX-24, UX-25, UX-26, UX-27. Requirements FR-BOOKING-020,
FR-TEAM-006. Read docs/domains/booking.md, owner-portal.md §8.1, §8.3, §8.4 and
.ai/rules/booking-availability.md first.

- UX-12: one "session has started" rule in App\Domain\Booking\BookingRules (next to sessionHasEnded), used by
  GetBeranda, GetOwnerBookingPanel and the server-side status change. Beranda rows offer "Tandai selesai"
  (session started) and "Tidak datang" (session ended), each only when TenantPermission allows it. State
  changes still go through BookingState::canTransition and the existing actions. Update docs/domains/booking.md.
- UX-13, UX-25: confirmation for "Tandai selesai" and "Tidak datang"; refund accept confirmations show the
  amount in rupiah, computed on the server.
- UX-21: each "Perlu tindakan" row says why (refund request, cancellation request from Kasir, ended without
  outcome), computed in the list Action, not in Blade.
- UX-22: cancellation requests show booking code, customer, session, requester and a link to the panel;
  approve and reject ask for confirmation. Add a rejection reason only if the backend already stores one;
  otherwise report it under "Found, not fixed" (it would be a schema change).
- UX-23: rows show participants, and the unit when the tenant uses units.
- UX-24: the reason field appears after choosing the action that needs it.
- UX-26: when no action is available, the panel says why.
- UX-27: filters apply on change, a "Reset filter" link, the whole row opens the panel (keyboard accessible).
Tests: the UX-12 rule (before start: no action; started: complete only; ended: complete and no-show), the
status endpoint refuses completion before the start, a Kasir sees only what TenantPermission allows, and
another tenant's booking panel stays 404.
```

### PR 5a — Add-on page on the shared design (light)

```text
Items: UX-64, UX-65. Rebuild resources/views/owner/additional-items.blade.php on the shared owner components
(page header, tabs, x-owner.dialog, shared confirm, bq-* tokens), with Indonesian copy, a sold count per
add-on (from the controller or Action) and errors per PR 3's pattern. Stock copy: stock decreases only when a
booking is paid (owner-portal.md §8.6). Create, update and delete behave as before; the existing add-on tests
stay green; add a test for the sold count.
```

### PR 5b — Reschedule dialog (light)

```text
Item: UX-32. Rebuild components/owner/modal-reschedule-booking.blade.php on x-owner.dialog (focus trap,
role="dialog", Escape scoped to the dialog), bq-* tokens, copy without "menu Schedule", and a summary of the
price difference before submitting, using the value the server already provides per slot (D-006). Reschedule
rules and the Action stay unchanged.
```

### PR 5c — Tim and Integrasi as Pengaturan tabs (light)

```text
Items: UX-110, UX-114 except webhook test/redeliver (PR 9). Read owner-portal.md §8.14 and §10.
Move Tim and Integrasi (with API) into Pengaturan tabs as the contract says; the old URLs redirect to the tab.
Tim: page header, seat usage "x dari y" (from EntitlementRules), status, last sign-in, confirmations for
revoking access and cancelling an invitation. Integrasi/API: confirmations for disconnect, revoke key and
delete webhook, a copy button for the one-time secret, human labels for delivery status. Authorization stays
in TenantPermission and the existing middleware; OwnerSidebarNavigationTest and the team, integration and API
tests stay green; add redirect tests for the old URLs.
```

### PR 5d — Checkout (light)

```text
Items: UX-120, UX-121. Show the resulting end date before paying (renewal extends from the end date, a plan
change starts on the payment date — D-002), computed on the server by the subscription code that already
decides it; do not re-implement the rule, and if no such method exists, stop and report. Indonesian copy and
shared styling; the storefront link uses CustomerBookingRoutes, so a custom domain is respected.
```

### PR 6 — Kalender and Jam Buka & Harga (light)

```text
Items: UX-40, UX-41, UX-42, UX-91, UX-92, UX-93, UX-95.
Start with UX-95 (verify): reproduce in the browser whether an invalid weekend value blocks closing a date;
if it does, write a failing test, then fix it by posting only the fields of the submitted form.
Then: slot state colours with a legend; bookings in month view (from the existing calendar data); slot close
and price change without losing the dialog and scroll; the hint "kosongkan untuk mengikuti aturan harga";
filters apply on change; "Salin ke semua hari" and an explicit "Tutup" toggle per day; confirmations for
deleting a peak rule or special date and reopening a closed date; a checkbox list instead of
<select multiple>; the weekend rule names its days.
Availability and pricing rules (BookingRules, pricing services) stay unchanged.
```

### PR 7a — Shell (light)

```text
Items: UX-06 (badge on the phone bottom nav, same count the sidebar uses), UX-07 (arrow-key navigation with
aria-activedescendant; Enter opens the highlighted result), UX-14 (quick actions open the create dialog with
?buat=1, like Jam Buka).
```

### PR 7b — Catalog, customers, reviews, units (light)

```text
Items: UX-50, UX-51, UX-61, UX-62, UX-66, rest of UX-73, UX-74, UX-80, UX-82, UX-83.
- UX-62: read the FR for service duration and price unit first. If the fix only changes how the unit is
  labelled, do it; if it changes what customers are shown or what is stored, stop and report.
- UX-61: linked add-ons in the service form (Medium and up, decided by EntitlementRules), tenant-scoped.
- UX-66: when deactivating a service, warn with the number of future bookings (counted in the controller or
  Action).
- UX-73, UX-74: promo count across all pages, a visible status toggle, one name "promo", Indonesian labels,
  the copy toast only on success; cards show state, max discount, services and discount given.
- UX-50 unsaved-note warning; UX-51 sort and visible quick-filter hints; UX-80 the review shown in the reply
  dialog; UX-82 shared confirm for "Hapus unit"; UX-83 services using a unit, share used today, and conflicts
  with date, time and customer instead of internal ids.
```

### PR 7c — Halaman Booking, Pembayaran, Pengaturan, Bantuan (light)

```text
Items: UX-100 (copy buttons for DNS records), UX-101 (the description is edited in one place only and the
other place links to it; owner-portal.md §7 entity homes decides which), UX-102 (preview of the unsaved state;
warning when switching tabs with unsaved changes), UX-103 (search, status and date filter, booking code links
to the panel; export only if the contract lists it), UX-104 (clearer ledger header), UX-113 (human preview of
hours, explanation of the refund toggle), UX-122 (the help link works after the guide is completed, or its
text changes).
```

### PR 8 — Cleanup (light)

```text
Items: UX-08 (remove modal-view-booking, stat-card and trial-banner after grepping that nothing renders them,
including dynamic component names), and replace the hard-coded legacy colours left in the add-on, promo,
reschedule and checkout views with bq-* tokens. No behaviour change; full suite and a browser pass of those
four pages.
```

### PR 9 — D-024 actions (approved 2026-11-16)

```text
D-024 was approved in full on 2026-11-16.
First add the approved actions to owner-portal.md §8 with new AC IDs (§14), then build: UX-05 link to the new
booking after a walk-in, UX-90 "Isi dari jam buka" in the bulk-slot form, UX-94 the rule name per price in
the simulator, webhook test and redeliver.
```

## Evidence (fill when done)

One row per section of the bulk branch.

| Section | Fixed | Skipped (why) | Tests added | Manual checks |
|---|---|---|---|---|
| §1 `fa3bdcf` | UX-70, UX-71, UX-72, percentage limit | none recorded | 1 test file | Edit form posts `max_discount_amount` and `applicable_services[]` (checked in the browser) |
| §2 `729f7e3` | UX-01, 03, 04, 10, 11, 20, 30, 31, 60, 81, 111 | none recorded | 3 test files | — |
| §3 `482975c` | UX-02, 63, 112 (`_form` key, inline errors; `owner-portal.md` §9) | none recorded | 2 test files | — |
| §4 `b579d12` | UX-12 (`BookingRules::sessionHasStarted` on Beranda, panel and status endpoint; `docs/domains/booking.md`), UX-13, 21–27 | none recorded | 3 test files | "Perlu tindakan" tab renders at desktop width |
| §5a `9063784` | UX-64, 65 | none recorded | 1 test file | Add-on page at 375 px |
| §5b `98e8857` | UX-32 | none recorded | — | — |
| §5c `cbfd1b4` | UX-110, UX-114 (without test/redeliver) | none recorded | 5 test files | Tim tab at 375 px; `/owner/settings/team`, `/integrations` and `/api` redirect to the tabs |
| §5d `137e52f` | UX-120, 121 | none recorded | 1 test file | — |
| §6 `48bae7e` | UX-40, 41, 42, 91, 92, 93, 95 | none recorded | 2 test files | Jam Buka & Harga renders at desktop width |
| §7a `1311a90` | UX-06, 07, 14 | none recorded | 1 test file | Bottom nav at 375 px |
| §7b `18747ae` | UX-50, 51, 61, 62, 66, 73, 74, 80, 82, 83 | none recorded | 2 test files | Promo list at 375 px |
| §7c `684862a` | UX-100–104, 113, 122 | none recorded | 3 test files | — |
| §9 `e69653f` | UX-05, 90, 94, webhook test/redeliver; `owner-portal.md` AC-BRD-08, AC-JBH-06/07, AC-SET-08 | none recorded | 3 test files | — |
| §8 `177df96` | UX-08; legacy colours in add-on, promo, reschedule and checkout views (0 left); PHPStan type fixes | — | — | — |
| tests `dfe0c62` | Four older tests aligned with intended changes (Tech Lead decision 2026-11-16): three complete a booking whose session had not started (now travel to the session), one expected the walk-in error on `error` instead of `idschedule` | — | — | — |

Run on 2026-11-16 at `dfe0c62`: `vendor/bin/phpunit` 969 tests, 966 pass; the 3 errors need the GD
extension, which the local PHP CLI lacks (`HalamanBookingPageTest` ×2, `LayananPageTest` ×1; CI decides).
PHPStan: no errors. Pint on changed files: passed. `npm run build`: done. Every owner page returned 200 as
Pemilik with no server error. `php artisan test` crashed the PHP process mid-suite on this machine;
`vendor/bin/phpunit` ran the whole suite.

The implementing agent ran out of context before writing its report, so per-item skips were not recorded;
the Tech Lead review checks each section against its items.

Found, not fixed (for the Tech Lead review): promo validity dates use English month abbreviations
("03 Oct – 01 Jan 2027"); the booking list still has a "Cari" button next to filters that UX-27 makes apply
on change (check that it is only the search submit).
