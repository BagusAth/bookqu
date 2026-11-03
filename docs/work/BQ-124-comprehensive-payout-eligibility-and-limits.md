---
id: BQ-124
title: Comprehensive payout eligibility verification and withdrawal limits
type: feature
status: proposed
priority: P1
phase: 8
domain: balance-payout
requirements: [FR-PAYOUT-002, NFR-SEC-005, NFR-UX-005]
invariants: [INV-TEN-01]
lane: heavy
approval: tech-lead
decision:
launch-blocker: false
depends-on: [BQ-123]
---

# BQ-124: Comprehensive payout eligibility verification and withdrawal limits

Referenced product requirement: [Revisian BookQu — Alur Withdraw Balance Owner Bisnis (19/10/2026)](https://docs.google.com/document/d/1JfagfHkv2O0O0pboh6Zi05V6VF3EkU7fZqHZSrm36Xo/edit?tab=t.ufh8olyxuj8o) (Langkah 5–6).

## Problem

Currently, `RequestOwnerPayout` only verifies basic numerical conditions and available balance. It does not enforce business requirements on account standing (email verification, business profile completion, platform payment mode), does not limit the number of open in-flight requests, and lacks configuration hooks for per-request and daily withdrawal caps.

## Plan

1. **Eligibility Engine:**
   - Create `App\Domain\Payout\PayoutEligibility::reasons(Tenant $tenant, User $user): array<string>`, returning reasons in strict prioritized order:
     1. Email not verified: "Verifikasi e-mail Anda terlebih dahulu."
     2. Business profile incomplete (namabisnis, slug, jenisbisnis, nomorhp): "Lengkapi profil bisnis terlebih dahulu." (Align with `EnsureOwnerProfileComplete` logic).
     3. Payment mode is not `platform`: "Withdraw hanya tersedia untuk mode pembayaran platform."
     4. Available balance <= 0: "Saldo belum tersedia untuk ditarik."
     5. Open in-flight payouts (`pending` + `approved`) >= `config('payouts.max_open', 1)`: "Masih ada pengajuan withdraw yang sedang diproses. Tunggu sampai selesai sebelum mengajukan lagi."
     6. No saved payout accounts exist: "Tambahkan rekening penarikan terlebih dahulu."
2. **Form Request Validation:**
   - In `RequestPayoutRequest`: `jumlah` must be `required|integer|min:10000`, with custom Indonesian error messages:
     - "Nominal withdraw minimal Rp 10.000."
     - "Nominal withdraw harus berupa angka bulat."
3. **Domain Action Hardening:**
   - In `RequestOwnerPayout::execute(...)`, inside transaction under tenant row lock and after idempotent key check:
     - Run `PayoutEligibility::reasons($lockedTenant, $owner)`, and throw `ValidationException::withMessages(['jumlah' => $reasons[0]])` if not empty.
     - Enforce `config('payouts.max_amount_per_request')`: if set and amount exceeds limit, throw validation error "Nominal melebihi batas Rp X per pengajuan."
     - Enforce `config('payouts.max_requests_per_day')`: count payouts created today in `Asia/Jakarta`; if exceeded, throw validation error "Batas N pengajuan withdraw per hari sudah tercapai."
     - Check available balance: if amount > balance, throw validation error "Saldo tidak mencukupi."
4. **UI Integration:**
   - `OwnerBalanceController@index` calculates and passes `$eligibilityReasons` to the view.
   - When reasons exist, the withdrawal trigger is disabled with the first reason displayed prominently below it.
   - Client-side validation prevents submission when amount is non-integer, < 10,000, or > available balance.

## Invariants at risk

- `INV-TEN-01` (Tenant isolation and balance integrity): Financial withdrawals must be strictly bounded by verified business identities and active tenant balance, preventing overdrafts and concurrent double-claims.

## Tests

- Tests for each individual eligibility rejection reason in order (unverified email, incomplete profile, wrong payment mode, zero balance, open request already processing, no accounts).
- Rejection of decimal amounts and amounts below Rp 10.000.
- Rejection of a second payout request when an open request exists (`max_open = 1`).
- Enforcement of `max_amount_per_request` and `max_requests_per_day` (with boundary checks at midnight Asia/Jakarta).
- Balance remains unmodified on any validation failure.
- Concurrency test (`@group mysql`) confirming that two parallel requests with different keys under race conditions allow at most one to succeed.

## Docs to update

- `docs/domains/balance-payout.md`
- `docs/requirements.md` (FR-PAYOUT-002)

## Evidence (fill when done)

_Fill in when done._
