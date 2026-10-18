# ADR-0002: Modular Release Staging and UI Gating Policy

> **Status:** Accepted  
> **Date:** 2026-10-18  
> **Authority:** Architectural Decision Record  
> **Context:** BookQu Multi-Tenant SaaS Platform  

---

## 1. Context and Problem Statement

BookQu has evolved a substantial set of extended operational and marketing capabilities:
- **Promotional Vouchers** (`/owner/vouchers`, `App\Models\Voucher`)
- **Additional Items / Add-ons** (`/owner/additional-items`, `App\Models\AdditionalItem`)
- **Staff & Resource Management** (`/owner/staff-resources`, `App\Models\Staff`, `App\Models\Resource`)
- **Appearance & Theme Customization** (`/owner/settings/appearance`, tenant branding fields)
- **Direct Tenant Payment Settings** (`/owner/settings/payments`, multi-merchant gateway credentials)
- **Media Asset Management** (`/owner/settings/assets`, `App\Models\Asset`)
- **Custom Landing Page Builder** (`/owner/landing-page`, Pro subscription custom sections)
- **Business Analytics Overview** (`/owner/analytics`, subscription-gated reporting)
- **Schedule Utilization Report** (`/owner/schedule-report`, occupancy metrics)
- **Third-Party Integrations** (`/owner/settings/integrations`, WhatsApp, Google Calendar)

All of these capabilities have complete or near-complete database migrations, Eloquent models, and owner-facing CRUD controllers. 

However, releasing these features prematurely into the production UI before their end-to-end customer journey, financial reconciliation, and operational governance are completely stabilized poses severe operational risks:
1. Exposing vouchers before checkout price calculation and atomic discount rules are certified could cause payment calculation mismatches.
2. Exposing additional items before customer-facing line item billing is persistent could break the 6-step checkout flow.
3. Exposing direct gateway credentials (custom Midtrans keys) without merchant onboarding verification would break the platform's unified escrow model (SingaPay & Midtrans).
4. Showing incomplete analytics or reporting pages without a clear development status could mislead business owners.

Therefore, an authoritative architectural and product decision was made to **stage features across distinct release tiers** and **intentionally gate / hide unreleased modules from the UI**.

---

## 2. Decision

BookQu adopts a **Three-Tier Modular Release Staging and UI Gating Policy**:

### Tier 1: Active Core Production Capabilities (Live in UI & Navigation)
These features are production-ready, fully tested, and exposed in all primary navigation menus:
- **Dashboard & Real-Time Polling** (`/owner/dashboard`, `/owner/dashboard/polling`, `/owner/notifications`)
- **Calendar & Operational Schedules** (`/owner/calendar`, `/owner/schedule`)
- **Service & Category Operations** (`/owner/services`, `/owner/categories`)
- **Booking Management** (`/owner/bookings`, status transitions, walk-in creation, rescheduling)
- **Customer CRM & Internal Notes** (`/owner/customers`, customer history)
- **Customer Reviews & Moderation** (`/owner/reviews`)
- **Business Profile & Account Settings** (`/owner/settings/business`)
- **Unified Payment Gateway** (SingaPay & Midtrans Platform Escrow with idempotent webhooks and automatic expiry)
- **Customer Self-Service Portal** (Tokenized reschedule, cancellation, and review submission)

### Tier 2: In-Development / Staged Capabilities (Locked in UI with Explanatory Notice)
These features have functional backend logic but their full UI workflows are currently under development. They are exposed in navigation with explicit locked indicators to inform owners of upcoming capabilities without allowing unintended state changes:
- **Business Analytics Overview** (`/owner/analytics`): Accessible via Dashboard "View Full Report [🔒 Terkunci]" and sidebar. Protected by `subscription:medium`. The page renders a locked hero card with an amber pulsating badge: `"Fitur Sedang Dikembangkan - Modul Analitik & Laporan Bisnis"` along with preview mockup metrics.
- **Schedule Report & Utilization** (`/owner/schedule-report`): Labeled in the sidebar with badge `"🔒 Terkunci"` and in the page header with badge `"Fitur Sedang Dikembangkan"`.
- **Third-Party Integrations** (`/owner/settings/integrations`): WhatsApp Notifications and Google Calendar Sync are marked with status `coming_soon` and badge `"Coming Soon - Fitur Tahap Lanjutan"`.
- **Platform Saldo & Payouts** (`/owner/settings/balance`): Saldo tracking is operational; self-service payout withdrawal is linked to payment settings which are currently held for the direct gateway phase.

### Tier 3: Kept / Hidden Capabilities (Intentionally Hidden from Navigation for Future Phases)
These seven modules have fully implemented database tables, models, and owner CRUD controllers, but their navigation entry points are **intentionally withheld from the Owner Sidebar, Topbar Dropdowns, and Settings Menus**:
1. **Vouchers** (`/owner/vouchers`): Backend CRUD complete. Customer checkout endpoint `BookingController::validateVoucher` intentionally returns HTTP 422: `"Fitur voucher saat ini belum aktif."` to ensure no untested discount deductions occur in live payments.
2. **Additional Items / Add-ons** (`/owner/additional-items`): Backend CRUD complete. Deferred from the customer 6-step booking flow to preserve checkout speed and simplicity.
3. **Staff & Resources** (`/owner/staff-resources`): Operational models exist; customer-facing staff selection is intentionally omitted to prevent schedule conflict complexities.
4. **Appearance & Branding** (`/owner/settings/appearance`): Custom palette/font form exists; dynamic CSS storefront overriding is deferred to a future premium tier.
5. **Direct Payment Gateway Settings** (`/owner/settings/payments`): Tenant-level direct Midtrans credentials are kept hidden because BookQu operates on a unified escrow model.
6. **Media Assets Gallery** (`/owner/settings/assets`): Media upload works; public storefront gallery presentation is staged for future portfolio-centric updates.
7. **Custom Landing Page Builder** (`/owner/landing-page`): Custom hero/benefit builder exists for Pro subscribers; public storefront renders a standardized clean booking journey.

---

## 3. Enforcement via Automated Tests

This release staging policy is **not accidental** and is strictly protected by regression tests in:
- `tests/Feature/Owner/OwnerSidebarNavigationTest.php`

The test suite enforces that:
```php
// Hidden features must NOT be rendered in the sidebar
$response->assertDontSee('id="nav-landing-page"', false);
$response->assertDontSee('id="nav-staff-resources"', false);
$response->assertDontSee('id="nav-additional-items"', false);
$response->assertDontSee('id="nav-vouchers"', false);
$response->assertDontSee('id="nav-appearance"', false);
$response->assertDontSee('id="nav-payments"', false);
$response->assertDontSee('id="nav-assets"', false);

// Analytics and Schedule Report must render locked development states
$resAnalytics->assertSee('Fitur Sedang Dikembangkan');
$resAnalytics->assertSee('Modul Analitik &amp; Laporan Bisnis', false);
$resSchedule->assertSee('Fitur Sedang Dikembangkan');
$resSchedule->assertSee('Laporan Okupansi &amp; Utilisasi Jadwal', false);
```

---

## 4. Consequences and Rules for Developers and AI Agents

1. **Do NOT unhide Tier 3 modules in navigation** without explicit instruction from the product owner and an accompanying release plan.
2. **Do NOT remove "Fitur Sedang Dikembangkan" or "Coming Soon" badges** from Tier 2 pages without first implementing the full underlying feature and updating the test assertions.
3. **Do NOT treat HTTP 422 in `validateVoucher` as a bug**: it is an intentional fail-safe guarding the customer checkout pipeline.
4. When new features are ready for release:
   - Complete end-to-end customer checkout and billing verification.
   - Update `OwnerSidebarNavigationTest` to assert presence rather than absence.
   - Update `docs/2-PRODUCT.md`, `docs/3-REQUIREMENT.md`, and `docs/6-TRACKER.md` to transition the module from Staged/Hidden to Active Core.
