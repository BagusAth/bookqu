---
id: BQ-125
title: Multi-step withdraw dialog, unified owner statuses, admin completion, and notifications
type: feature
status: proposed
priority: P1
phase: 8
domain: balance-payout
requirements: [FR-PAYOUT-002, FR-PAYOUT-003, FR-NOTIFICATION-001, NFR-UX-005, NFR-ACC-001]
invariants: [INV-TEN-01]
lane: heavy
approval: tech-lead
decision:
launch-blocker: false
depends-on: [BQ-124]
---

# BQ-125: Multi-step withdraw dialog, unified owner statuses, admin completion, and notifications

Referenced product requirement: [Revisian BookQu — Alur Withdraw Balance Owner Bisnis (19/10/2026)](https://docs.google.com/document/d/1JfagfHkv2O0O0pboh6Zi05V6VF3EkU7fZqHZSrm36Xo/edit?tab=t.ufh8olyxuj8o) (Langkah 2, 4, 9–15).

## Problem

The current withdrawal experience has several operational and UX deficits:
1. Payout status labels exposed to business owners reflect technical administrative stages (`pending`, `approved`, `success`, `failed`, `rejected`), causing confusion.
2. The withdrawal form on the balance page is always open and lacks a clear confirmation step, summary breakdown, and WCAG dialog accessibility.
3. Admin payout completion allows finishing payouts without either a transfer reference or proof file, and fails to clean up uploaded proof files if the database transition rolls back.
4. Notifications are incomplete: owners only receive database notifications without email updates for outcomes (`success`, `failed`, `rejected`), and platform admins receive no notifications when new withdrawal requests are filed.
5. The owner balance overview displays an obsolete payment-based "Pending Settlement" metric instead of actual in-flight payouts ("Sedang Diproses").

## Plan

1. **Database Schema:**
   - Migration `add_approval_tracking_to_owner_payouts`: add `approved_at` (timestamp nullable) and `approved_by` (unsignedBigInteger nullable).
2. **Domain Status Model:**
   - In `App\Domain\Payout\PayoutState`:
     - Add `ownerLabel(string $status): string` mapping `pending` and `approved` to "Pending", `success` to "Berhasil", and `rejected`/`failed` to "Gagal".
     - Add `ownerNote(string $status): ?string` returning "Disetujui, transfer diproses" for `approved`.
     - Add `ownerTone(string $status): string` returning `warning`, `success`, or `danger` for status badge styling.
     - Retain `label()` for admin view.
3. **Admin Payout Processing & Validation:**
   - In `ProcessOwnerPayout`:
     - When moving to `approved`, record `approved_at` and `approved_by`.
     - `processed_at` and `processed_by` are recorded exclusively on terminal states (`success`, `failed`, `rejected`).
     - Store proof file prior to transaction; if transaction/execution returns false, delete the uploaded file from disk.
   - In `AdminPayoutController@complete`:
     - Validate: `transfer_reference` (`nullable|string|max:150|required_without:proof`), `proof` (`nullable|file|mimes:jpg,jpeg,png,pdf|max:5120|required_without:transfer_reference`) with message "Isi referensi transfer atau unggah bukti transfer."
4. **Notifications:**
   - `OwnerPayoutStatusChangedNotification`:
     - Implement `ShouldQueue` and `afterCommit()`.
     - `via()` sends `database` always; adds `mail` channel when status is `success`, `rejected`, or `failed`.
     - Subject: "Withdraw Berhasil — Rp X" or "Withdraw Gagal — Rp X". Content includes formatted amount, masked account, owner status label, rejection reason (if failed), transfer reference (if success), and CTA to the balance page. Database payload uses `ownerLabel`.
   - Create `App\Notifications\NewPayoutRequestNotification`:
     - Database notification sent to all `User` records with `role = admin` upon new payout submission in `RequestOwnerPayout` (in `try/catch` with `Log::warning`). Payload includes tenant, amount, and deep link to `/admin/payouts?status=pending`.
5. **Owner Balance Page Redesign:**
   - Header: Indonesian title "Saldo" and subtitle "Kelola saldo hasil booking dan tarik ke rekening Anda." Replace all English labels.
   - Metric cards: "Saldo Tersedia" (with primary Withdraw button), "Sedang Diproses" (sum of `jumlah` for payouts with status `pending` or `approved`), and "Total Pendapatan".
   - Accessible Alpine Modal Dialog Withdraw (WCAG compliant with `role="dialog"`, `aria-modal="true"`, focus trapping, Esc/backdrop dismiss except when submitting):
     - Step 1 (Isi Data): amount input with real-time thousand separator formatting (sending clean integer), helper text "Saldo tersedia Rp X · Minimal Rp 10.000", "Tarik semua" button, radio cards for saved accounts with default pre-selected, "+ Tambah rekening" action opening the account dialog. Client-side validation before advancing.
     - Step 2 (Konfirmasi): summary of amount, bank, full account number, account holder name, and disclaimer notice. "Kembali" and "Konfirmasi Withdraw" actions (`:disabled="submitting"`, indicator "Memproses…").
     - Server validation recovery: dialog automatically re-opens at Step 1 preserving old input values via `@js(old(...))` and errors.
     - Success feedback: dialog closes with toast/flash message "Pengajuan withdraw dikirim dan menunggu review admin."
     - Remove obsolete inline form.
   - Payout History:
     - Columns: Tanggal (`d M Y, H:i` WIB), Nominal, Rekening (bank + masked number), Status (badge with `ownerLabel` + `ownerTone`), Keterangan (`ownerNote`, red rejection reason, transfer ref, proof download link).
     - Responsive stacked card layout on mobile screens (< 640px).
6. **Admin Interface Enhancements:**
   - In `resources/views/admin/payouts.blade.php`: show owner email and tenant's current balance; clarify completion requirements in modal.
   - Admin dashboard: widget showing count of `pending` payouts linking to `/admin/payouts?status=pending`.

## Invariants at risk

- Payout lifecycle integrity (`INV-BOOK-01/02`, `PayoutState`): Transitions must adhere strictly to valid state diagrams.
- Resource cleanup: Failed storage uploads or aborted transitions must not leave orphaned proof files on disk.

## Tests

- Unit tests for `PayoutState::ownerLabel`, `ownerNote`, and `ownerTone`.
- Admin completion fails if neither reference nor proof is provided; succeeds if either or both are provided.
- Approval sets `approved_at` and `approved_by` without altering `processed_*`.
- Stored proof file is cleaned up if the transition fails.
- Notification tests with `Notification::fake()`:
  - Owner receives database notification on all transitions, and queued mail only on success/rejected/failed.
  - Platform admins receive database notifications on new payout submissions.
  - Notification dispatch failure does not abort payout creation.
- Owner balance view tests verifying dialog structure, accessibility attributes, and `request_key` propagation.

## Docs to update

- `docs/domains/balance-payout.md`
- `docs/domains/notifications.md`
- `docs/requirements.md` (FR-PAYOUT-002, FR-PAYOUT-003)
- `docs/owner-portal.md` §6.9
- `docs/work/ROADMAP.md`

## Evidence (fill when done)

_Fill in when done._
