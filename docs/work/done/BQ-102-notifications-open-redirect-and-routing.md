---
id: BQ-102
title: Validate the redirect target of notification mark-as-read, and use the shared confirm dialog
type: bug-fix
status: rejected
priority: P2
phase: 8
domain: notifications
requirements: [FR-NOTIFICATION-001, NFR-SEC-006]
invariants: []
lane: heavy
approval: tech-lead
decision:
launch-blocker: false
depends-on: [BQ-092]
---

# BQ-102: Validate the redirect target of notification mark-as-read, and use the shared confirm dialog

> **Ditutup 2026-11-04 (BQ-126 Fase 9): dilebur ke [BQ-145](../BQ-145-paket-tagihan-notifikasi-bantuan.md).** Semua temuan di item ini menjadi bagian dari lingkup BQ-145.


Found during the owner UI deep audit on 2026-11-02. Corrected by the Tech Lead review on 2026-11-02: severity lowered from P1 launch blocker to P2 hardening, and the "missing deep link" claim removed because it was wrong.

## Problem

### 1. Unvalidated redirect target (hardening)
`app/Http/Controllers/Owner/OwnerNotificationController.php:83-101` (`markAsRead`) ends with:
`$redirectUrl = $request->input('redirect') ?: back()->getTargetUrl(); return redirect($redirectUrl);`
Any absolute URL in `redirect` is followed. The route is `POST owner/notifications/{id}/read` and is CSRF-protected, and no owner view sends a `redirect` field today, so it cannot be triggered from another site. It is still an open-redirect pattern that becomes exploitable if the endpoint is ever reached by GET or a form starts passing user-controlled URLs.

### 2. Native confirm on notification delete
Deleting a notification uses `onsubmit="return confirm('Hapus notifikasi ini?')"` (`resources/views/owner/notifications.blade.php:234`) instead of the shared `confirm-modal` (BQ-092).

### Checked and not a problem
Booking notifications already link to the booking: the notification data stores `url` = `/owner/bookings?katakunci=<booking_code>` (`app/Notifications/NewBookingOwnerNotification.php:102`, `BookingStatusChangedOwnerNotification.php:154`) and the page uses it (`notifications.blade.php:106`).

## Plan

1. Accept only same-host relative paths in `redirect` (otherwise fall back to `route('owner.notifications')`), or drop the parameter because nothing uses it.
2. Replace the native `confirm()` with `confirm-modal`.

## Invariants at risk

None. Notifications stay scoped to the authenticated user.

## Tests

- `POST /owner/notifications/{id}/read` with `redirect=https://evil.example.com` redirects to an internal page; `redirect=/owner/bookings` is honoured (or ignored, if the parameter is removed).

## Docs to update

`docs/domains/notifications.md` (Known gaps).

## Evidence (fill when done)

_Fill in when done._
