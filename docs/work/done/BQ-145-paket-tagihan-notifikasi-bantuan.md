---
id: BQ-145
title: Paket & Tagihan, notification centre and Bantuan
type: feature
status: done
priority: P2
phase: 4
domain: subscription
requirements: [FR-HELP-001]
invariants: []
lane: light
approval: none
decision: D-007, D-017
launch-blocker: true
depends-on: [BQ-129, BQ-130, BQ-131]
---

# BQ-145: Paket & Tagihan, notification centre and Bantuan

Epic **E4 · Trust and acquisition pages** of the owner portal redesign, planned in [BQ-126](../BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Subscription checkout has layout and terminology issues (BQ-104); mark-as-read redirects are not validated (BQ-102); there is no help page.

## Plan

1. Build `owner-portal.md` §8.15, §8.16, §8.17.
2. Carry every finding of the absorbed items.

## Scope

- **Acceptance criteria owned:** AC-PKT-03, AC-PKT-04, AC-NTF-01, AC-NTF-02, AC-BNT-01.
- **New requirements owned:** FR-HELP-001.
- **ADRs:** none.
- **Absorbs:** BQ-102, BQ-104 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

Notification redirects only to own-tenant targets.

## Tests

- AC-PKT-03, AC-PKT-04, AC-NTF-01, AC-NTF-02, AC-BNT-01.

## Docs to update

- `docs/domains/subscription.md`
- `docs/domains/notifications.md`

## Evidence

Closed 2026-11-12. Merged into `integration` in PR #45 (merge commit `cd41f24`, 2026-11-10) after the Tech Lead audit. Before the merge: full suite 897 tests on SQLite, the item tests also on MySQL 8, Pint and PHPStan clean. Commit `cdeb057`. Tests: `Owner/PaketNotifikasiBantuanTest` (AC-PKT-03, AC-PKT-04, AC-NTF-01, AC-NTF-02, AC-BNT-01), `Team/TenantPermissionTest`. Bantuan stays behind the hidden `help` module; set `SUPPORT_WHATSAPP` and `SUPPORT_EMAIL` before releasing it.
## Implementation notes

- **Wave:** built ahead of wave 4 under the ROADMAP rule. It depends on BQ-129, BQ-130 and BQ-131.
- **Paket & Tagihan (§8.15):** the page is rebuilt from `GetSubscriptionOverview`. It shows:
  - plan, status and end date;
  - usage of services, bookings this month and team accounts;
  - the plan comparison through `x-plan-features`;
  - billing history with invoice or "Bayar sekarang" links.
- **AC-PKT-03:** checkout and subscription routes are refused for Manajer and Kasir through `subscription.manage`, unchanged.
- **AC-PKT-04:** the invoice line now shows the paid amount. Before, it showed the plan's current price plus a derived "platform fee" row, so it changed when the plan price changed.
- **BQ-104:**
  - the checkout summary comes before the form on phones (`order-*`);
  - the invoice label reads "Nomor pesanan";
  - the Snap token is passed with `@js`;
  - print styles already existed.
- **Notifikasi (§8.16):** `GetNotificationsPage` provides the unread and type filters for what the role may see (AC-NTF-02 through `GetVisibleNotifications`).
  - Mark-read redirects to the stored target through `Support/InternalRedirect` (AC-NTF-01, BQ-102).
  - Delete uses the shared confirm dialog. The bell's JSON feed is unchanged.
- **Bantuan (§8.17):** `owner.help` is behind the redesign module `help`, hidden until released (ADR-0021). `GetHelpPage` holds the questions grouped by page. Contacts come from `config/support.php` (`SUPPORT_WHATSAPP`, `SUPPORT_EMAIL`, `SUPPORT_HOURS`, AC-BNT-01), and the page links to the new owner guide on Beranda.
- **Ops:** set `SUPPORT_WHATSAPP` and `SUPPORT_EMAIL` per environment before the module is released.
- **Test changed by the spec:** `AnalyticsAndSubscriptionTest` checks the §8.15 page title.
