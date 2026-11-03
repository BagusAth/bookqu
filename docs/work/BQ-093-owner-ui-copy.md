---
id: BQ-093
title: Owner UI copy in Indonesian with one vocabulary and no redundant text
type: improvement
status: rejected
priority: P2
phase: 8
domain: insights
requirements: [NFR-UX-002, NFR-MAINT-004]
invariants: []
lane: light
approval: none
decision:
launch-blocker: false
depends-on: [BQ-092]
---

# BQ-093: Owner UI copy in Indonesian with one vocabulary and no redundant text

> **Ditutup 2026-11-04 (BQ-126 Fase 9): dilebur ke [BQ-128](BQ-128-owner-app-shell-and-navigation.md).** Semua temuan di item ini menjadi bagian dari lingkup BQ-128.


Found in the owner UI audit of 2026-11-02. UI copy must be Indonesian (`.ai/rules/frontend.md`, `docs/workflow.md` §11).

## Problem

**Language mixed on the same screen.** Sidebar labels and section titles are English ("Dashboard, Notifications, Calendar, Schedule, Bookings, Services, Categories, Customers, Reviews, Overview, Schedule Report, Business, Integrations, Subscription") next to "Saldo & Withdraw", "Kelola Langganan", "Keluar Akun". The plan card says "Active Plan", "180 days left". The dashboard is English ("Welcome back", "Here's what's happening…", "Total Revenue", "Daily Trends", "vs yesterday", "View All", "No upcoming bookings"), while notifications and categories are Indonesian. Trial banner: "7-day Free Trial remaining. Upgrade now…" (`components/owner/trial-banner.blade.php:15`). Booking statuses English (BQ-092).

**One thing, many names.** The same page is called differently in the sidebar, breadcrumb, `<title>` and `<h1>`:

| Route | Sidebar | Breadcrumb | `<h1>` |
|---|---|---|---|
| `/owner/bookings` | Bookings | Bookings | Bookings Management |
| `/owner/services` | Services | Services / Services | Services Management |
| `/owner/categories` | Categories | Categories | Service Categories |
| `/owner/customers` | Customers | Customers CRM | Customers CRM |
| `/owner/reviews` | Reviews | Reviews | Customer Reviews & Feedback |
| `/owner/analytics` | Overview | Overview | Analytics |
| `/owner/schedule-report` | Schedule Report | Schedule Report | Schedule Report & Utilization |
| `/owner/settings/business` | Business | Business Profile | Settings |
| `/owner/settings/integrations` | Integrations | Integrations | Integrations & Third-Party Services |
| `/owner/settings/balance` | Saldo & Withdraw | Dashboard (bug) | Balance & Payouts |
| `/owner/subscription` | Subscription | Subscription | Subscription Management |
| `/owner/notifications` | Notifications | Account / Notifications | Pusat Notifikasi |
| `/owner/calendar` | Calendar | Booking / Calendar | Calendar & Jadwal Operasional |
| `/owner/schedule` | Schedule | Schedule | Schedule Management |

Legacy "program" still appears (dashboard table header "Program", "No programs found").

**Text that does nothing.** Subtitles that restate the title ("Manage your plan, usage, and billing."), a date badge on the dashboard, "Steady"/"Stable performance", "Latest client reservations", "Next confirmed client sessions" (it also lists pending), meta lines like "12 Booking • 30 Total Slot Scheduled", decorative "Booking Transaksi (3)" labels, "Tersimpan di browser".

## Plan

1. Write a short owner vocabulary (Booking, Layanan, Kategori, Jadwal, Kalender, Pelanggan, Ulasan, Notifikasi, Saldo, Pencairan, Langganan, Pengaturan, Lunas, Menunggu pembayaran, Selesai, Dibatalkan) and put it in `docs/glossary.md` (UI terms) so agents reuse it.
2. One name per page: sidebar label = breadcrumb = `<title>` = `<h1>` (served by BQ-091's navigation model).
3. Translate every owner string; sentence case; numbers and dates in `id` format; times as `HH:MM WIB`.
4. Delete subtitles and helper lines that repeat the title or state the obvious; keep a subtitle only when it tells the owner something they need.
5. Buttons say what happens ("Simpan perubahan", "Tambah layanan"); empty states say what to do next.

## Invariants at risk

None.

## Tests

- Existing view tests that assert English strings are updated together with the copy (intended change).
- Manual read-through of every owner page.

## Docs to update

`docs/glossary.md` (UI vocabulary).

## Evidence (fill when done)

_Fill in when done._
