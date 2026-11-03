# Owner Portal

What each owner page is for, what it contains, who may use it, and which plan unlocks it. `product.md` says which capabilities BookQu offers; `requirements.md` says how they must behave; this document arranges them into pages so that no two pages do the same job. Each page contract (§8) is the acceptance criteria for the owner UI work that builds it.

> **Status: Draft for approval (2026-11-04).** Written in BQ-126 phase 3 from the owner portal redesign approved by the Product Lead on 2026-11-04 (`docs/work/BQ-126/rancangan-ulang-portal-owner.md`), the feature inventory (`docs/work/BQ-126/inventaris-fitur.md`) and the decisions in `product-decisions.md`. It becomes authoritative when the Product Lead approves D-007 Revisi 1 (gate G2 of BQ-126). Decisions D-001 to D-006 and D-009 to D-021 are approved; D-007 Revisi 1 (this page map) waits for the Product Lead.

## 1. How to use this document

- **Building a page:** implement its contract in §8, including every acceptance criterion (`AC-…`). A work item names the criteria it delivers; every criterion belongs to exactly one work item (BQ-126 phase 8).
- **Adding something:** a new page, tab, section or action is added here first, then built (§14). Owner UI not described here is out of scope (`AGENTS.md` rule 15).
- **Sources disagree:** `product-decisions.md` wins over this document, and this document wins over the prototype (`docs/work/BQ-126/prototype-portal-owner.html`), which shows structure and flow only, not the final visual design.
- **Server rules stay where they are:** booking states, occupancy, payment, entitlement and tenancy rules live in `requirements.md`, the ADRs and `app/Domain`. A contract says what the page shows and offers; it never re-defines those rules.

## 2. Owner jobs

| Frequency | Job | Page |
|---|---|---|
| Several times a day | Know what happens today and what needs me | Beranda |
| Several times a day | See who comes when; record a walk-in; finish a booking | Kalender |
| Daily | Find a booking and act on it (complete, no-show, reschedule, cancel, refund) | Booking |
| Weekly | Look up a customer and contact them | Pelanggan |
| Weekly | Answer reviews | Ulasan |
| Weekly | Open slots, block dates, adjust prices | Jam Buka & Harga |
| Monthly | Check money, withdraw it | Pembayaran |
| Monthly | See how the business performs | Laporan |
| Occasionally | Share the booking page, change its look | Halaman Booking |
| Occasionally | Run a promo | Promo |
| Rarely | Set up services, units, policy, team, integrations | Layanan, Unit, Pengaturan |
| Rarely | Manage the BookQu plan and invoices | Paket & Tagihan |

Daily jobs must work first on a phone (375 px), because owners and cashiers use them at the counter. Configuration pages are designed for desktop first and must remain usable on a phone.

## 3. Placement rules

These rules decide where anything new goes. Breaking one needs a Tech Lead decision.

1. **One job per page.** Every page answers one owner question (the "Question" line of its contract). Content that answers another question links to that page.
2. **One home per entity.** Each entity is created and edited on exactly one page (§7). Other pages show it read-only and open the shared panel.
3. **One component per entity view.** A booking is always shown by the booking panel, a customer by the customer panel (§9), wherever it is opened from.
4. **Now versus history.** Beranda shows today and what needs action, without charts. Trends, comparisons and exports live in Laporan.
5. **Configuration versus operation.** Pages that set rules (Layanan, Unit, Jam Buka & Harga, Pengaturan) do not list daily bookings; operational pages (Beranda, Kalender, Booking) do not change rules.
6. **Only working features are shown.** No teaser cards, decorative previews or controls without a backend. A feature above the viewer's plan shows the shared upgrade prompt (§9), never a page-specific banner.
7. **The server decides.** Hiding a menu or a button is presentation; every route checks role (§5), plan (§6) and module state on the server (`AGENTS.md` rule 3).
8. **The amount shown is the amount paid.** Every list, panel, invoice and report shows booking amounts from the payment record, never recomputed from current service or slot prices.
9. **Every action names its actor.** Each change records who made it (§5.3).

## 4. Navigation

### 4.1 Sidebar (desktop and tablet)

| Group | Menu | Path | Badge |
|---|---|---|---|
| — | Beranda | `/owner/dashboard` | — |
| — | Kalender | `/owner/calendar` | — |
| — | Booking | `/owner/bookings` | bookings needing action |
| — | Pelanggan | `/owner/customers` | — |
| Bisnis saya | Layanan | `/owner/services` (tabs Layanan, Kategori, Add-on) | — |
| Bisnis saya | Unit | `/owner/units` (new) | — |
| Bisnis saya | Jam Buka & Harga | `/owner/schedule` (tabs Jam Buka, Harga, Libur & Tutup, Slot) | runway warning |
| Pemasaran | Halaman Booking | `/owner/booking-page` (new) | — |
| Pemasaran | Promo | `/owner/promos` (new; replaces `/owner/vouchers`) | — |
| Pemasaran | Ulasan | `/owner/reviews` | unanswered reviews |
| Keuangan | Pembayaran | `/owner/payments` (new; tabs Ringkasan, Transaksi, Saldo & Pencairan, Rekening, Pengaturan) | — |
| Keuangan | Laporan | `/owner/analytics` (tabs Ringkasan, Pendapatan, Okupansi, Layanan, Pelanggan) | — |
| Lainnya | Pengaturan | `/owner/settings/business` (tabs Profil Bisnis, Kebijakan Booking, Notifikasi, Integrasi, Akun, Tim) | — |
| Lainnya | Paket & Tagihan | `/owner/subscription` | trial days left |
| Lainnya | Bantuan | `/owner/help` (new) | — |

- A menu the viewer's role may not open is not shown (§5).
- A menu above the plan is shown with a lock and opens the upgrade prompt, except Unit, which is hidden below Pro.
- Tabs keep their state in the URL (`?tab=…`), so links and reloads land on the same tab.
- The old paths of merged pages redirect permanently (§10.2).

### 4.2 Top bar (every page)

| Element | Behaviour | Roles |
|---|---|---|
| Bell (Notifikasi) | Unread count; dropdown with the latest items; "Lihat semua" opens `/owner/notifications` (§8.16) | all |
| **+ Booking** | Opens the walk-in form (§9) from any page | all; hidden when the subscription has ended (§12) |
| Search | Booking code, customer name or phone number; results open the booking or customer panel | all |
| Account menu | Name and role of the signed-in user, link to Pengaturan → Akun, sign out | all |

### 4.3 Bottom navigation (phone)

Below 768 px the sidebar is replaced by a bottom bar with **Beranda, Kalender, + Booking, Booking, Lainnya**. "Lainnya" opens a sheet with the remaining menus the role may open, in sidebar order.

## 5. Roles and access

Home of the rule: FR-TEAM (D-011). This section is the page view of that rule.

### 5.1 Roles

- **Pemilik:** the account that registered the business. One per business. Everything.
- **Manajer:** a team member who runs operations and configuration, without money, plan or team management.
- **Kasir:** a team member at the counter: today's bookings, walk-ins, customers, without revenue figures.

The number of team accounts per plan follows D-017: Small 0, Medium 2, Pro 10.

### 5.2 Matrix

| Page or action | Pemilik | Manajer | Kasir |
|---|---|---|---|
| Beranda, Kalender, Booking, Pelanggan | ✓ | ✓ | ✓ without revenue figures |
| Record walk-in, mark complete, mark no-show, reschedule | ✓ | ✓ | ✓ |
| Cancel a booking, decide a refund request | ✓ | ✓ | request cancellation only; Pemilik or Manajer decides |
| Layanan, Unit, Jam Buka & Harga | ✓ | ✓ | — |
| Halaman Booking, Promo, Ulasan | ✓ | ✓ | — |
| Laporan | ✓ | ✓ | — |
| Pembayaran (balance, payouts, accounts) | ✓ | — | — |
| Pengaturan: Profil Bisnis, Kebijakan Booking, Notifikasi, Integrasi | ✓ | ✓ | — |
| Pengaturan: Akun (own account) | ✓ | ✓ own account | ✓ own account |
| Pengaturan: Tim, delete business account | ✓ | — | — |
| Paket & Tagihan | ✓ | — | — |
| Bantuan, Notifikasi | ✓ | ✓ | ✓ |

- A role without access gets the menu hidden and the route refused on the server: `403` for an action, a "Tidak punya akses" page for a page.
- "Without revenue figures" means amounts are hidden in Beranda cards and lists for Kasir. The booking panel still shows the amount and payment status of a single booking, because the cashier needs them at the counter.

### 5.3 Activity attribution

Every create, change and state action records the user and role ("Walk-in dicatat oleh Siska (Kasir)"):

- the booking panel shows it in the booking history;
- the Pemilik sees all entries in Pengaturan → Tim → Aktivitas.

## 6. Plans

Home of the rule: `product.md` §7 (D-017). Entitlement is decided only by `EntitlementRules` (INV-SUB-01). This table lists where each gate shows.

| Feature | Small | Medium | Pro | Where |
|---|---|---|---|---|
| Core pages (Beranda, Kalender, Booking, Pelanggan, Ulasan, Jam Buka & Harga, Pembayaran, Pengaturan, Paket & Tagihan, Bantuan) | ✓ | ✓ | ✓ | — |
| Booking page look, gallery, link, QR, promotion materials | ✓ | ✓ | ✓ | Halaman Booking (D-009, D-010) |
| Services | 5 | unlimited | unlimited | Layanan, usage meter |
| Bookings per month | 300 | 500 | unlimited | Beranda banner, Paket & Tagihan |
| Laporan and export (Laporan, Booking list export) | blurred preview | ✓ | ✓ | Laporan, Booking |
| Promo and add-on | — | ✓ | ✓ | Promo, Layanan → Add-on, checkout (D-015) |
| Team accounts | 0 | 2 | 10 | Pengaturan → Tim (D-011) |
| Unit | — | — | ✓ | Unit, Layanan field, Kalender filter (D-012) |
| Landing page and custom domain | — | — | ✓ | Halaman Booking (D-005) |
| API and webhooks | — | — | ✓ | Pengaturan → Integrasi (D-019) |
| Other integrations | ✓ | ✓ | ✓ | Pengaturan → Integrasi (D-019) |

## 7. Entity homes

**Legend:**
- **C:** create
- **E:** edit
- **A:** state actions
- **V:** view
- **P:** opens the shared panel
- blank: not shown

| Entity | Home (C, E) | Shown elsewhere |
|---|---|---|
| Booking | Booking (A); walk-in from + Booking or Kalender (C) | Beranda V,P · Kalender V,P,A · Pelanggan V,P · Laporan aggregate |
| Refund request | Booking panel (decide); created by the customer | Beranda "Perlu tindakan" · Booking tab "Perlu tindakan" · Pembayaran transactions |
| Customer note | Pelanggan panel | Booking panel (read) |
| Service | Layanan → Layanan | Kalender filter · Booking filter · Unit (read) · Promo (applies to) · Laporan |
| Category | Layanan → Kategori | Layanan filter |
| Add-on | Layanan → Add-on | Booking panel (read) · checkout |
| Unit | Unit | Layanan field · Kalender filter · Laporan → Okupansi |
| Opening hours, prices, closed dates, slots | Jam Buka & Harga | Kalender (V; close or reopen a slot) |
| Booking page look, gallery, landing page, domain | Halaman Booking | Layanan and Unit image picker (gallery) |
| Promo | Promo | Booking panel (read) · checkout |
| Review reply, visibility | Ulasan | Beranda "Perlu tindakan" · Pelanggan panel (read) |
| Balance, payout, payout account | Pembayaran | Beranda (status) |
| Business profile | Pengaturan → Profil Bisnis | Halaman Booking preview |
| Booking policy | Pengaturan → Kebijakan Booking | Booking panel (read) · Pembayaran → Pengaturan (read) · checkout |
| Notification preferences | Pengaturan → Notifikasi | — |
| Integration | Pengaturan → Integrasi | — |
| Own account | Pengaturan → Akun | Account menu |
| Team member, activity log | Pengaturan → Tim | Booking history (actor) |
| Subscription, invoice | Paket & Tagihan | Beranda banner · upgrade prompt |
| Notification | created by the system | Bell, `/owner/notifications` |

## 8. Page contracts

**Fields of each contract:**
- **Question:** the owner question the page answers.
- **Path, Access, Plan.**
- **Contents:** the sections of the page, and the fields of each.
- **Actions:** each with the condition that allows it.
- **Not here:** what must not be added to this page.
- **Empty states.**
- **Source:** where the content comes from in `inventaris-fitur.md` §2.
- **Requirements.**
- **Acceptance criteria.**

**Booking status labels** follow `BookingState::formatLabel`:

| Status | Label |
|---|---|
| `pending` | Menunggu bayar |
| `paid` | Terkonfirmasi |
| `completed` | Selesai |
| `cancelled` | Dibatalkan |
| `no_show` | Tidak datang |

### 8.1 Beranda

- **Question:** "What must I know and do today?"
- **Path:** `/owner/dashboard`. **Access:** all roles (Kasir without revenue). **Plan:** all.
- **Contents:**
  1. **One status banner**, chosen by priority (only the first that applies is shown):
     1. subscription ended (§12);
     2. trial ends within 3 days;
     3. a service has no open slot in the next 7 days;
     4. no payout account saved (Pemilik only);
     5. monthly booking limit at 80% or more.
  2. **Today in four figures:**
     - bookings today (online and walk-in);
     - waiting for payment;
     - revenue today from payments (hidden for Kasir);
     - slot occupancy today.
  3. **Perlu tindakan.** A list with counts. Each item links to its place:
     - refund requests waiting for a decision (D-001);
     - cancellation requests from a Kasir (D-011);
     - paid bookings whose time has passed and that are not yet marked complete or no-show;
     - reviews of 3 stars or less without a reply;
     - payout status changes (Pemilik only).
  4. **Jadwal hari ini.** Bookings in time order: time, customer, service, participants, booking status and payment status. The booking happening now is marked "Sedang berlangsung". Below it, a summary of tomorrow (count, first booking).
  5. **Booking link card** with copy and share buttons. These are the same as Halaman Booking (§8.8).
  6. **Quick actions:** + Booking, + Layanan, + Promo (Medium and up), + Anggota tim (Pemilik, Medium and up).
  7. **New owner guide.** Shown instead of sections 2–4 until completed, then never again. Four steps, each ticked from real data:
     1. complete the business profile (`owner.profile.complete` fields: business name, business type, phone, address);
     2. add a service;
     3. set opening hours and open slots;
     4. share the booking link.
- **Live refresh:** while the page is visible, sections 2–4 refresh every 60 seconds. Values are rendered as text, never as HTML (BQ-089).
- **Actions:** open the booking panel; mark complete or no-show from a row; follow links.
- **Not here:** charts, trends, period toggles, service ranking, export (Laporan); lists beyond today and tomorrow (Booking).
- **Empty states:**
  - a new owner sees the guide;
  - a day without bookings shows "Belum ada booking hari ini" with the next upcoming booking.
- **Source:** inventory §2.1 (dashboard, polling, profile completion modal, trial banner).
- **Requirements:** FR-DASH-001 to FR-DASH-007, FR-NOTIFICATION-001, FR-SUB-004.
- **Acceptance criteria:**
  - **AC-BRD-01:** At most one banner shows, and it follows the priority order.
  - **AC-BRD-02:** Revenue in the figures equals the sum of successful booking payments today.
  - **AC-BRD-03:** A Kasir sees no amount on this page.
  - **AC-BRD-04:** Every "Perlu tindakan" item opens the exact place where it is resolved, and disappears once it is resolved.
  - **AC-BRD-05:** The guide shows until all four steps are true from data, and never again afterwards.
  - **AC-BRD-06:** Polling stops while the page is hidden, and the refreshed content is rendered as text.
  - **AC-BRD-07:** The page contains no chart.

### 8.2 Kalender

- **Question:** "When is it busy, when is it free, and who comes?"
- **Path:** `/owner/calendar`. **Access:** all roles. **Plan:** all.
- **Contents:**
  - **Day view (default on phones):** a grid of hours × services. On a phone it becomes a list per hour.
  - **Week view and month view.**
  - **"Now" line,** a "Hari ini" button and date navigation. Month navigation never overflows (BQ-117).
  - **Filters:** service, unit (Pro), booking status.
  - **Each slot shows** its state (free, booked, closed, past), price and, when capacity is above 1, the participant count against capacity ("3/10", D-014).
- **Actions:**
  - **Click a booking:** opens the booking panel. All booking actions live there.
  - **Click a free future slot:** offers "Catat walk-in" (shared walk-in form, prefilled with the slot), "Tutup slot ini" and "Ubah harga slot ini". Price and closing go through the same server actions as Jam Buka & Harga → Slot.
  - **Click a closed slot:** offers "Buka lagi".
  - **Link "Atur jam buka":** goes to Jam Buka & Harga.
- **Not here:** bulk slot creation, price rules, opening hours, booking policy.
- **Empty states:** no slots in the visible range shows "Belum ada slot di periode ini" with a button to Jam Buka & Harga.
- **Source:** inventory §2.2.
- **Requirements:** FR-CALENDAR-001 to FR-CALENDAR-006, FR-BOOKING-010, FR-UNIT (filter).
- **Acceptance criteria:**
  - **AC-KAL-01:** Day, week and month views show the same bookings for the same date range.
  - **AC-KAL-02:** A walk-in from a slot is created through the shared walk-in action and appears without a page reload.
  - **AC-KAL-03:** A slot with an active booking cannot be closed (INV-BOOK-03).
  - **AC-KAL-04:** On a 375 px screen, the day view is a list, and every action is reachable without horizontal scrolling.
  - **AC-KAL-05:** Capacity counts match the number of participants of active bookings.

### 8.3 Booking

- **Question:** "Which bookings exist, and which do I have to handle?"
- **Path:** `/owner/bookings`. **Access:** all roles. **Plan:** all; export for Medium and up.
- **Contents:**
  - **Tabs by task, each with a count:**
    - **Perlu tindakan:** refund requests, Kasir cancellation requests, paid bookings that have ended without an outcome;
    - **Mendatang;**
    - **Menunggu bayar;**
    - **Selesai:** includes no-show;
    - **Dibatalkan;**
    - **Semua.**
  - **Search:** booking code, customer name, phone, email.
  - **Filters:** date range, service, source (online or walk-in), sort.
  - **Rows:** date and time, customer, service, participants, unit (Pro), amount paid (rule 8), booking status, payment status. On a phone, the rows become cards.
- **Actions:**
  - open the booking panel;
  - + Booking;
  - **Ekspor CSV** of the filtered list (Medium and up). The export is formula-safe (BQ-111).
- **Not here:** charts and totals over time (Laporan), customer notes (Pelanggan), slot editing.
- **Empty states:**
  - no bookings yet: explains that bookings arrive from the booking page, and shows the link;
  - an empty tab: a short sentence per tab.
- **Source:** inventory §2.3 (list, filters `katakunci` and `status`, summary).
- **Requirements:** FR-BOOKING-008 to FR-BOOKING-016, FR-MULTIBOOK-005, FR-MULTIBOOK-006, FR-REFUND.
- **Acceptance criteria:**
  - **AC-BKG-01:** Each tab's count equals the number of rows it shows.
  - **AC-BKG-02:** The amount shown equals the paid amount on the payment record, even after the service or slot price changes.
  - **AC-BKG-03:** Search finds a booking by code, name, phone or email.
  - **AC-BKG-04:** Below Medium, the export is refused on the server, not only hidden.
  - **AC-BKG-05:** An exported cell that starts with `=`, `+`, `-` or `@` is neutralised.

### 8.4 Booking panel (shared)

Opened from Beranda, Kalender, Booking, Pelanggan, search and notifications. One component; no page has its own booking modal.

- **Contents:**
  - booking code, status and source;
  - **session:** service, date, time; every slot of a multi-slot booking; participants; unit (Pro);
  - **customer:** name, phone with a WhatsApp link, email, link to the customer panel;
  - **payment:**
    - status, method, amount paid;
    - breakdown of base price, add-ons and promo discount;
    - paid at;
    - deadline while pending;
    - refund status and amount;
  - the customer's note;
  - for walk-ins, the payment method (`cash`, `transfer`, `manual`, `qris`);
  - **history:** every event with actor (§5.3): created, paid, rescheduled from/to with price difference (D-006), refund requested/decided, cancelled with reason, completed, no-show.
- **Actions.** Only actions that `BookingState::canTransition` allows for the current state are shown. None is offered while an online payment is open (BQ-087).

  | Action | Shown when | Behaviour |
  |---|---|---|
  | Tandai selesai | `paid` and the session has started | → `completed` |
  | Tandai tidak datang | `paid` and the session has ended | → `no_show`; no refund |
  | Pindah jadwal | `paid`, session in the future | Slot picker of the same service. Shows the price difference of the new slot and records it. Owner may pick any slot (D-006) |
  | Batalkan | `paid` or walk-in, by Pemilik or Manajer | Reason required. For a booking paid online, the panel states "Pelanggan mendapat refund penuh Rp …" and creates a full refund for the admin to transfer (D-001 point 8) |
  | Ajukan pembatalan | Kasir, same states as Batalkan | Reason required; creates a request for Pemilik or Manajer |
  | Putuskan refund | A customer refund request is open | Three choices: Terima 100%, Terima 50%, Tolak (reason required). Accepting cancels the booking and frees the slot; rejecting keeps the booking (D-001 points 4–5) |
  | Kirim ulang link | Booking has a customer email | Re-sends the customer access link (ADR-0006) |

- **Not here:** editing customer data, editing prices, deleting a booking.
- **Source:** inventory §2.3 (`owner.bookings.status`, `owner.bookings.available-slots`, `owner.bookings.reschedule`). The former "Konfirmasi Lunas" for online payments stays removed (decision of 2026-11-01).
- **Requirements:** FR-BOOKING-008, FR-BOOKING-009, FR-BOOKING-011, FR-BOOKING-014, FR-PAYMENT-003, FR-PAYMENT-011, FR-REFUND, FR-TEAM.
- **Acceptance criteria:**
  - **AC-PNL-01:** An action that is not allowed for the current state or role is neither shown nor accepted by the server.
  - **AC-PNL-02:** Cancelling a booking paid online always creates a full refund for that payment, and never lets the owner choose a smaller amount.
  - **AC-PNL-03:** A refund decision of 50% creates a refund of exactly half of the amount paid, rounded down to whole rupiah.
  - **AC-PNL-04:** Every history entry shows the actor and the role.
  - **AC-PNL-05:** Rescheduling shows the price difference before confirming, and records it in the history.
  - **AC-PNL-06:** The same panel opens from every entry point listed above.

### 8.5 Pelanggan

- **Question:** "Who are my customers, and how do I reach them?"
- **Path:** `/owner/customers`. **Access:** all roles (Kasir without spending totals). **Plan:** all.
- **Contents:**
  - **Summary:** total customers, new this month, returning.
  - **Quick filters:** new, regular (3 or more bookings), not seen for 60 days.
  - **Search.**
  - **List:** name, phone, email, number of bookings, total paid, last booking, next booking.
  - **Customer panel:**
    - contact;
    - figures: bookings, total paid, cancellations, no-shows;
    - internal note;
    - booking history (each row opens the booking panel);
    - payment history.
- **Actions:** WhatsApp and email links; add or edit the internal note (`notes` up to 2000 characters); "Buat booking" opens the walk-in form prefilled with the customer.
- **Not here:** campaigns, bulk messages, editing a booking's customer data.
- **Empty states:** "Pelanggan muncul otomatis setelah booking pertama."
- **Source:** inventory §2.9.
- **Requirements:** FR-CUSTOMER-001 to FR-CUSTOMER-005.
- **Acceptance criteria:**
  - **AC-PLG-01:** One row per customer identity, matching the identity used by customer notes (`customer_identifier`).
  - **AC-PLG-02:** Totals use paid amounts (rule 8).
  - **AC-PLG-03:** A Kasir sees no spending totals.
  - **AC-PLG-04:** "Buat booking" prefills name, phone and email in the walk-in form.

### 8.6 Layanan

- **Question:** "What do I sell?"
- **Path:** `/owner/services`. **Access:** Pemilik, Manajer. **Plan:** all; number of services limited by plan; Add-on tab Medium and up.
- **Tab Layanan:**
  - **List:** image, name, category, base price and price unit, duration, capacity, units (Pro), active, popular.
  - **Usage meter:** for example "3 dari 5 layanan".
  - **Form fields:**

    | Field | Rule |
    |---|---|
    | Name | Required, up to 255 characters |
    | Category | Optional; tenant's own |
    | Base price | ≥ 0 |
    | Price unit | `satuan_harga`, shown to customers |
    | Duration | 5–480 |
    | Duration unit | `satuan_durasi`, default "menit" |
    | Capacity per slot | Default 1 |
    | Units used (Pro, D-012) | — |
    | Linked add-ons (Medium and up) | — |
    | Description | Up to 1000 characters |
    | Image | Picked from the gallery or uploaded into it |
    | Popular | Shown as a badge on the booking page |
    | Active | — |

  - **Price and duration warning:** changing them applies only to new bookings.
  - **Delete:** a service with booking history can only be deactivated (FR-SERVICE-006).
- **Tab Kategori:**
  - **List:** name, description, colour label, image, display order, service count, active.
  - **Actions:** create, edit, activate or deactivate, reorder, delete when empty. "Lihat layanan" filters the first tab.
- **Tab Add-on:**
  - **List:** name, description, price, stock (number or unlimited), linked services, active, sold count.
  - **Behaviour:** customers choose add-ons while booking. Stock decreases only when a booking is paid.
- **Not here:** slots, slot prices and opening hours (Jam Buka & Harga); staff as service providers (removed, D-011).
- **Empty states:** the first-service guide continues to Jam Buka & Harga after saving.
- **Source:**
  - inventory §2.5: the services and programs routes. `owner.programs.*` redirects to `owner.services.*`.
  - YATIM-2 (popular), YATIM-3 (units of price and duration).
  - §2.6 categories, including YATIM-4 (colour).
  - §2.7 add-ons, including `is_unlimited`.
  - `staff_ids[]` is removed; `resource_ids[]` becomes Units.
- **Requirements:** FR-SERVICE-001 to FR-SERVICE-007, FR-CATEGORY-001 to FR-CATEGORY-005, FR-ADDON-001 to FR-ADDON-005, FR-SUB-010, FR-UNIT.
- **Acceptance criteria:**
  - **AC-LYN-01:** Creating a service above the plan limit is refused on the server, and the upgrade prompt shows.
  - **AC-LYN-02:** A service with bookings cannot be deleted, only deactivated.
  - **AC-LYN-03:** Changing price or duration does not change existing bookings or their amounts.
  - **AC-LYN-04:** The Add-on tab and the add-on field are refused below Medium.
  - **AC-LYN-05:** An add-on with stock 0 cannot be chosen at checkout. An unlimited add-on never runs out.

### 8.7 Unit

- **Question:** "Which courts, rooms or tools do my services use?"
- **Path:** `/owner/units`. **Access:** Pemilik, Manajer. **Plan:** Pro.
- **Contents:**
  - **List:** name, type, capacity, location, services using it, share used today, active.
  - **Conflict list:** bookings that already overlap on the same unit, in existing data, with links to their booking panels.
- **Actions:** create, edit, activate or deactivate, delete (only without future bookings). Fields: name (required, up to 100), type (required, up to 100), capacity (1–9999), location (up to 255), services, image from the gallery.
- **Rule:** two active bookings that use the same unit cannot overlap in time, even for different services. The rule is defined once, in `BookingRules` (FR-UNIT, ADR-0017).
- **Not here:** bookings (Kalender, Booking).
- **Empty states:** explains units with the futsal example ("Paket Turnamen dan Sewa Lapangan A memakai lapangan yang sama").
- **Source:** inventory §2.8 resources routes, including YATIM-5 (location). Existing `resources` rows become units (inventory §5).
- **Requirements:** FR-UNIT.
- **Acceptance criteria:**
  - **AC-UNT-01:** A second booking on the same unit and time is refused at checkout, at walk-in and at reschedule.
  - **AC-UNT-02:** The conflict list shows every existing overlap.
  - **AC-UNT-03:** The page and its routes are refused below Pro.

### 8.8 Jam Buka & Harga

- **Question:** "When am I open, what does it cost, and when am I closed?"
- **Path:** `/owner/schedule`. **Access:** Pemilik, Manajer. **Plan:** all. When the subscription has ended, nothing new can be opened (§12).
- **Header:** the runway per service, for example "Slot terbuka sampai 14 Des". A warning shows when it is less than 7 days away.
- **Tab Jam Buka:**
  - weekly opening hours per day, with breaks;
  - how many days ahead slots may be opened;
  - service duration is read from Layanan.
- **Tab Harga:**
  - the base price comes from Layanan;
  - **weekend rule:** type `none`, `multiplier` or `fixed`, with a value;
  - **peak-hour rules:** name, days, from and to, extra amount, services;
  - **special-date prices:** date, price, services;
  - **price simulator:** for a chosen service and date, shows the price of each hour;
  - **"Terapkan harga dasar ke slot terbuka":** sets the base price on existing future free slots of a service. This replaces the old default-pricing action (YATIM-1).
- **Tab Libur & Tutup:**
  - closed dates with reason;
  - adding a date that has active bookings shows those bookings, and refuses until each one is rescheduled or cancelled (BQ-108).
- **Tab Slot:**
  - list per service and week with state and price;
  - close, reopen, or change the price of one slot;
  - **bulk creation:**
    - service;
    - `harian` (one date) or `rentang` (from–to, capped);
    - start time, end time;
    - interval of 5–480 minutes, defaulting to the service duration;
    - a preview of how many slots will be created and how many already exist, before saving (BQ-107).
  - Deleting a free slot is allowed. A slot with an active booking can be neither deleted nor closed (INV-BOOK-03).
- **Not here:** booking lists, booking actions, activity feed (Booking, booking panel history), booking policy (Pengaturan → Kebijakan Booking), the former bulk price adjustment panel (removed: no backend).
- **Empty states:** the "Buka slot pertama" guide.
- **Source:** inventory §2.4 (all six schedule routes); YATIM-1, YATIM-7.
- **Requirements:** FR-SCHEDULE-001 to FR-SCHEDULE-010.
- **Acceptance criteria:**
  - **AC-JBH-01:** Bulk creation shows the count before saving, and never creates a duplicate slot.
  - **AC-JBH-02:** The `multiplier` and `fixed` weekend rules both produce the price the price simulator shows.
  - **AC-JBH-03:** A closed date with active bookings is refused until they are handled.
  - **AC-JBH-04:** "Terapkan harga dasar" changes only future free slots.
  - **AC-JBH-05:** The runway warning appears when a service has no free slot in the next 7 days.

### 8.9 Halaman Booking

- **Question:** "How do customers find me, and what do they see?"
- **Path:** `/owner/booking-page`. **Access:** Pemilik, Manajer. **Plan:** all; landing page and domain Pro.
- **Section Link & QR:**
  - the booking link with copy, open and "Bagikan ke WhatsApp";
  - a QR code to download;
  - a printable poster with the QR code and business name.
- **Section Materi promosi:** ready text for an Instagram bio and a WhatsApp message, each with a copy button.
- **Section Tampilan:**
  - logo, cover image, theme colour;
  - button style (pill, rounded, square), card style (elevated, outline, soft), font;
  - description;
  - live phone-sized preview.
- **Section Galeri media:**
  - all images of the business: upload (image up to 10 MB), title, category (`logo`, `cover`, `service`, `gallery`, `other`), delete;
  - the same gallery opens as the image picker in Layanan, Unit and the landing page;
  - deleting an image that is in use asks for confirmation and clears the reference.
- **Section Landing page (Pro):**
  - template (Modern Business, Creative Studio, Minimalist Clinic);
  - page sections and headline;
  - banner from the gallery;
  - preview and publish.
- **Section Domain sendiri (Pro, D-005):**
  - domain field (unique);
  - DNS instructions;
  - an automatic check that shows connected or not connected.
- **Not here:** business profile data (Pengaturan → Profil Bisnis); asset URLs. "Salin URL" is not offered, because images are used through the picker.
- **Empty states:** a new business shows the BookQu default look, with a prompt to add a logo.
- **Source:** inventory §2.15 (appearance), §2.16 (assets), §2.17 (landing page). The logo upload accepted by the profile request moves here (inventory §7 point 3).
- **Requirements:** FR-PUBLIC-003, FR-APPEARANCE-001 to FR-APPEARANCE-004, FR-ASSET-001 to FR-ASSET-004, FR-TENANT-007, FR-PUBLIC-004.
- **Acceptance criteria:**
  - **AC-HBK-01:** A change saved in Tampilan appears on the public booking page.
  - **AC-HBK-02:** The QR code and poster encode the current booking link.
  - **AC-HBK-03:** Landing page and domain are refused below Pro.
  - **AC-HBK-04:** A domain is accepted only once across all businesses, and the check reports its real DNS state.
  - **AC-HBK-05:** Images are stored per business; no business can pick another business's image.

### 8.10 Promo

- **Question:** "How do I give a promotion, and does it work?"
- **Path:** `/owner/promos` (`/owner/vouchers` redirects). **Access:** Pemilik, Manajer. **Plan:** Medium and up.
- **Contents:**
  - **Figures:** active promos, times used, total discount given, revenue from bookings with a promo.
  - **Tabs:** Aktif, Terjadwal, Berakhir, Semua.
  - **Promo cards:** each shows quota used (`used_count` of `usage_limit`).
- **Form fields:**
  - code: unique per business, letters, numbers, dash and underscore, up to 50 characters;
  - type: percentage or fixed amount;
  - value (≥ 1);
  - minimum order;
  - maximum discount;
  - quota;
  - start and end date;
  - services it applies to;
  - active.
- **Rule:** the discount is applied before payment, and the payment amount includes it.
- **Not here:** promo usage by customer (Laporan → Pelanggan).
- **Source:** inventory §2.10; customer side in inventory §6 (`validate-voucher`, `voucher_code`).
- **Requirements:** FR-VOUCHER-001 to FR-VOUCHER-005.
- **Acceptance criteria:**
  - **AC-PRM-01:** A valid code at checkout reduces the payment amount by exactly the computed discount, capped by the maximum discount.
  - **AC-PRM-02:** The quota cannot be exceeded, even when two customers pay at the same time.
  - **AC-PRM-03:** An expired or scheduled promo is refused at checkout.
  - **AC-PRM-04:** The page and its routes are refused below Medium.

### 8.11 Ulasan

- **Question:** "What do customers say, and have I answered?"
- **Path:** `/owner/reviews`. **Access:** Pemilik, Manajer. **Plan:** all.
- **Contents:**
  - average rating and distribution;
  - **tabs:** Belum dibalas, Semua, Disembunyikan;
  - search;
  - each review links to its booking.
- **Actions:**
  - reply or edit the reply (up to 1000 characters), with three suggested sentences;
  - hide or show on the booking page.
- **Not here:** rating trends (Laporan).
- **Empty states:** explains that customers review through their access link after a completed booking.
- **Source:** inventory §2.11.
- **Requirements:** FR-REVIEW-001 to FR-REVIEW-007.
- **Acceptance criteria:**
  - **AC-ULS-01:** A hidden review does not appear on the booking page.
  - **AC-ULS-02:** "Belum dibalas" lists exactly the reviews without a reply.

### 8.12 Pembayaran

- **Question:** "How do I receive money, how much is mine, and when can I withdraw it?"
- **Path:** `/owner/payments`. `/owner/settings/balance` redirects to the Saldo & Pencairan tab. **Access:** Pemilik only. **Plan:** all, also when the subscription has ended (D-002).
- **Tab Ringkasan:**
  - available balance, in process, total withdrawn;
  - successful, waiting, failed and refunded payments;
  - latest transactions.
- **Tab Transaksi:** every customer payment with order id, time, customer, service, amount, method, status, and refund amount if any.
- **Tab Saldo & Pencairan:**
  - **balance ledger:** every movement (booking payment in, refund out, payout out), with date, booking code and amount;
  - **payout history:** paginated 15 per page; status as seen by the owner (Pending, Berhasil, Gagal) with stages diajukan → disetujui → ditransfer; rejection reason; transfer proof download;
  - **request dialog:**
    - steps: amount, then account, then confirmation;
    - amount: whole rupiah, at least `payouts.min_amount`, at most the available balance and the per-request cap;
    - idempotent through a request key;
    - eligibility reasons are shown before the dialog opens.
- **Tab Rekening (D-004):**
  - up to `payouts.max_accounts` (3) saved accounts, one of them primary;
  - add, edit and delete each require the current password; every change sends an email;
  - an account used by a payout in progress cannot be deleted.
- **Tab Pengaturan:** explains how payments work. This tab has nothing to configure:
  - payments are processed by BookQu (QRIS, virtual account, e-wallet);
  - platform fee 0%;
  - payment deadline;
  - the refund rules of the business (read from Kebijakan Booking).
- **Not here:** payment gateway selection or credentials (removed, D-016); revenue charts (Laporan).
- **Source:**
  - inventory §2.19: all seven balance and payout-account routes;
  - inventory §2.18: the transaction history of the hidden payment settings page;
  - the gateway form and the old withdrawal form are removed;
  - BQ-127 corrections apply.
- **Requirements:** FR-PAYOUT-001 to FR-PAYOUT-003, FR-PAYMENT-011, FR-REFUND.
- **Acceptance criteria:**
  - **AC-PAY-01:** Every route of this page is refused for Manajer and Kasir.
  - **AC-PAY-02:** The ledger balance equals `saldo_platform`.
  - **AC-PAY-03:** A repeated request key creates no second payout.
  - **AC-PAY-04:** The minimum and the number of accounts follow `config/payouts.php`.
  - **AC-PAY-05:** No gateway credential field exists anywhere in the owner portal.

### 8.13 Laporan

- **Question:** "How is my business performing, and what can I improve?"
- **Path:** `/owner/analytics`. `/owner/schedule-report` redirects to the Okupansi tab. **Access:** Pemilik, Manajer. **Plan:** Medium and up; Small sees a blurred preview with the upgrade prompt.
- **One period filter** shared by all tabs: this month, last month, 3, 6 or 12 months, or custom.
- **Tab Ringkasan:** revenue, bookings, new customers, cancellation rate, completion rate, no-show rate, average rating, top service, most used unit (Pro).
- **Tab Pendapatan:** revenue per period, online versus walk-in, per service, refunds, net. All from payments (rule 8).
- **Tab Okupansi:**
  - occupancy rate, total, booked and free slots;
  - heat map of busy and quiet hours and days;
  - utilisation per unit (Pro).
- **Tab Layanan:** ranking by bookings and revenue.
- **Tab Pelanggan:** new versus returning customers; promo usage (Medium and up).
- **Actions:** CSV export per tab. Formula-safe, refused below Medium, and computed without double counting (BQ-111).
- **Not here:** today's operations (Beranda), individual bookings (Booking).
- **Source:** inventory §2.12 (analytics, export), §2.13 (schedule report, export), and the charts removed from the dashboard (§2.1).
- **Requirements:** FR-ANALYTICS-001 to FR-ANALYTICS-006, FR-REPORT-001 to FR-REPORT-003.
- **Acceptance criteria:**
  - **AC-LAP-01:** Revenue for a period equals the sum of successful booking payments minus refunds in that period.
  - **AC-LAP-02:** A booking is counted once, even when it has several slots.
  - **AC-LAP-03:** Export and data routes are refused below Medium.
  - **AC-LAP-04:** The figures for one period are the same on every tab.

### 8.14 Pengaturan

- **Path:** `/owner/settings/business`. `/owner/settings` redirects here. One page with tabs; each tab saves separately. **Access:** per tab (§5).

| Tab | Question | Contents | Access, plan |
|---|---|---|---|
| **Profil Bisnis** | "How is my business described?" | Name, booking link (slug) with a warning that the old link, QR code and poster stop working when it changes (BQ-110), business type, address, map link, WhatsApp, email, time zone (WIB only for now), description | Pemilik, Manajer; all |
| **Kebijakan Booking** | "What may customers do themselves?" | Refund on or off (D-001); cancellation and refund-request deadline in hours (`cancel_before_hours`, default 24); reschedule deadline in hours (`reschedule_before_hours`); minimum hours before a session for a new booking, maximum days ahead, buffer between bookings; a preview of the policy text customers see before paying | Pemilik, Manajer; all |
| **Notifikasi** | "What am I told, and how?" | Per event (new booking, payment success, cancellation, reschedule, refund request, new review, payout status, plan status): in-app (always on) and email (on/off), plus the email address. Stored on the server. WhatsApp is not offered (D-021) | every user for their own preferences; all |
| **Integrasi** | "Which of my tools are connected?" | Google Calendar (bookings into the owner's calendar), Google Analytics and Meta Pixel (booking page), each with connect, test, disconnect; API keys and webhooks (Pro); payments and email shown as "Dikelola BookQu" | Pemilik, Manajer; API Pro |
| **Akun** | "How do I sign in?" | Name, email, phone, new password with confirmation; the current password is required for any change (BQ-098). Pemilik also sees "Hapus akun bisnis", which requires the account email and the current password and is refused while the balance is not zero or a payout is in progress (BQ-122) | every user for their own account; delete: Pemilik |
| **Tim** | "Who else may sign in?" | Members with role, status and last sign-in; invite by email with a role; change role; revoke access; seat usage against the plan; activity log of all members (§5.3) | Pemilik; Medium and up |

- **Not here:** subscription (Paket & Tagihan), balance and accounts (Pembayaran), booking page look (Halaman Booking), payment gateways (removed).
- **Source:**
  - inventory §2.14: all six settings routes, including YATIM-6;
  - the notification preferences that were not saved become real;
  - §2.20: the integrations route;
  - §2.8: the staff routes are replaced by Tim.
- **Requirements:** FR-TENANT-004, FR-TENANT-005, FR-TENANT-008, FR-BOOKING-012, FR-BOOKING-013, FR-BOOKING-022, FR-NOTIFICATION-001 to FR-NOTIFICATION-007, FR-AUTH-006, FR-INTEGRATION, FR-API, FR-TEAM, FR-REFUND.
- **Acceptance criteria:**
  - **AC-SET-01:** The cancellation and reschedule deadlines saved here are the ones `BookingRules` and the customer access page enforce.
  - **AC-SET-02:** Notification preferences survive a reload and another device.
  - **AC-SET-03:** Every account change without the correct current password is refused.
  - **AC-SET-04:** Changing the slug warns first, then the old link stops resolving.
  - **AC-SET-05:** Inviting beyond the plan's seats is refused on the server.
  - **AC-SET-06:** A revoked member's session ends at their next request.
  - **AC-SET-07:** Integration secrets are never shown again after saving and are never logged.

### 8.15 Paket & Tagihan

- **Question:** "Which plan am I on, what does it give me, and what have I paid?"
- **Path:** `/owner/subscription`, with the checkout pages `owner.checkout*`. **Access:** Pemilik only. **Plan:** all, including ended.
- **Contents:**
  - current plan, status and end date;
  - **usage:** services, bookings this month, team accounts;
  - an honest plan comparison generated from the plan list (§6, D-017);
  - billing history with invoices.
- **Actions:**
  - choose a plan, then checkout (payer name, email, phone, note), payment with countdown, invoice;
  - "Bayar Sekarang" re-opens the provider page while the payment is pending;
  - this is the only place to change plans.
  - **Renewal rules (D-002):** renewing the same plan extends from the current end date. Changing plans starts on the payment date. Buying during a trial ends the trial.
- **Not here:** feature marketing beyond the comparison.
- **Source:** inventory §2.21 (all seven subscription and checkout routes).
- **Requirements:** FR-SUB-001 to FR-SUB-011.
- **Acceptance criteria:**
  - **AC-PKT-01:** The comparison lists only features that exist for each plan.
  - **AC-PKT-02:** Renewing the same plan never shortens the remaining paid days.
  - **AC-PKT-03:** Manajer and Kasir are refused.
  - **AC-PKT-04:** The invoice amount equals the paid amount.

### 8.16 Notifikasi

- **Question:** "What happened that I should know about?"
- **Path:** bell, and `/owner/notifications`. **Access:** every user sees notifications meant for their role. **Plan:** all.
- **Contents:** the full list with filters (unread, type).
- **Actions:**
  - clicking an item marks it read and opens its target (booking panel, review, payout, invoice);
  - mark all read;
  - delete, with the shared confirm dialog.
- **Source:** inventory §2.22 (four notification routes).
- **Requirements:** FR-NOTIFICATION-001 to FR-NOTIFICATION-005.
- **Acceptance criteria:**
  - **AC-NTF-01:** Opening an item marks it read and lands on its target.
  - **AC-NTF-02:** A Kasir never receives payout or plan notifications.

### 8.17 Bantuan

- **Question:** "Where do I get help?"
- **Path:** `/owner/help`. **Access:** all roles. **Plan:** all.
- **Contents:**
  - frequently asked questions, grouped by page;
  - BookQu's WhatsApp contact and support email;
  - a link to the new owner guide (§8.1).
- **Requirements:** FR-HELP.
- **Acceptance criteria:**
  - **AC-BNT-01:** Contact details come from configuration, not from hardcoded copy.

## 9. Shared building blocks

Built once (epic E1) and used by every page above.

| Block | Used by | Rule |
|---|---|---|
| App shell: sidebar (§4.1), top bar (§4.2), bottom navigation (§4.3) | all pages | Menus filtered by role and plan on the server |
| Page header (title, one-line purpose, primary action) | all pages | One primary action per page |
| Tabs | Layanan, Jam Buka & Harga, Pembayaran, Laporan, Pengaturan, Booking | State in the URL |
| Booking panel (§8.4) | Beranda, Kalender, Booking, Pelanggan, search, notifications | Rule 3 |
| Customer panel (§8.5) | Pelanggan, booking panel, search | Rule 3 |
| Walk-in form | + Booking, Kalender, Pelanggan | One server action (FR-BOOKING-010). Fields: slot, name (required, up to 150), phone (required, up to 30), email, note (up to 500), payment method (`cash`, `transfer`, `manual`, `qris`), participants, add-ons |
| Status badges (booking, payment, payout, refund) | everywhere | One label and colour per state |
| Upgrade prompt | every plan-gated feature (§6) | Rule 6 |
| Gallery image picker | Layanan, Unit, Halaman Booking | One gallery per business |
| Empty state (what it is, how to start, one button) | every list | — |
| Confirm dialog, toast | every destructive or important action | No native `alert` or `confirm` |
| Permission denied page | every route refused by role | Does not reveal whether the record exists |

## 10. From the current portal to the new one

### 10.1 Page map

Every current page and route is placed. The route-level detail is in `inventaris-fitur.md` §2.

| Current | Target | Inventory |
|---|---|---|
| Dashboard | Beranda | §2.1 |
| Calendar | Kalender | §2.2 |
| Bookings | Booking + booking panel | §2.3 |
| Schedule | Jam Buka & Harga | §2.4 |
| Services, Programs | Layanan → Layanan | §2.5 |
| Categories | Layanan → Kategori | §2.6 |
| Additional Items | Layanan → Add-on | §2.7 |
| Staff & Resources | Staff → Pengaturan → Tim (new meaning); Resources → Unit | §2.8 |
| Customers | Pelanggan | §2.9 |
| Vouchers | Promo | §2.10 |
| Reviews | Ulasan | §2.11 |
| Analytics | Laporan | §2.12 |
| Schedule Report | Laporan → Okupansi | §2.13 |
| Settings | Pengaturan → Profil Bisnis, Akun | §2.14 |
| Appearance | Halaman Booking → Tampilan | §2.15 |
| Assets | Halaman Booking → Galeri media | §2.16 |
| Landing Page | Halaman Booking → Landing page, Domain sendiri | §2.17 |
| Payment settings | Pembayaran → Transaksi; gateway removed | §2.18 |
| Saldo & withdraw | Pembayaran | §2.19 |
| Integrations | Pengaturan → Integrasi | §2.20 |
| Subscription, checkout | Paket & Tagihan | §2.21 |
| Notifications | Bell + `/owner/notifications` | §2.22 |
| Top bar, sidebar | App shell | §2.23 |
| — | Unit, Halaman Booking (link, QR, materials), Promo statistics, Bantuan, + Booking, search, bottom navigation, Tim | new |

### 10.2 Paths that change

| Old path or route name | New | How |
|---|---|---|
| `owner.programs.*` (`/owner/programs`) | `owner.services.*` | Permanent redirect for GET; write routes removed |
| `/owner/vouchers` | `/owner/promos` | Redirect |
| `/owner/settings/balance` | `/owner/payments?tab=saldo` | Redirect |
| `/owner/settings/payments`, `/owner/settings/payment-setting`, `owner.settings.payment` | — | Removed (D-016); GET redirects to `/owner/payments` |
| `/owner/settings/appearance`, `/owner/settings/assets`, `/owner/landing-page` | `/owner/booking-page` sections | Redirect |
| `/owner/schedule-report` | `/owner/analytics?tab=okupansi` | Redirect |
| `/owner/staff-resources` | `/owner/units` or `/owner/settings/business?tab=tim` | Redirect to Unit (Pro) or Tim |
| `/owner/settings/integrations` | `/owner/settings/business?tab=integrasi` | Redirect |
| `/owner/settings` | `/owner/settings/business` | Redirect |

Every other path stays as it is.

## 11. Inventory decisions

### 11.1 Orphans (inventory §3)

| ID | Decision |
|---|---|
| YATIM-1 default price per service | Base price in Layanan, plus "Terapkan harga dasar ke slot terbuka" in Jam Buka & Harga → Harga |
| YATIM-2 `is_popular` | Kept: Layanan field "Populer", shown as a badge on the booking page |
| YATIM-3 `satuan_durasi`, `satuan_harga` | Kept as Layanan fields, shown to customers. The price computation itself follows D-014 |
| YATIM-4 category colour | Kept as a colour label in Kategori |
| YATIM-5 unit location | Kept in Unit |
| YATIM-6 cancellation and reschedule hours without a form | Pengaturan → Kebijakan Booking |
| YATIM-7 Recent Activity | Booking panel history and Tim → Aktivitas |

### 11.2 Current features missing from the prototype (inventory §4)

Each is kept and placed:

| Feature | Placed in |
|---|---|
| Bulk slots with start time, end time, interval, daily or range | §8.8 Slot |
| Weekend `multiplier` | §8.8 Harga |
| Button style, card style, font | §8.9 Tampilan |
| Phone and new-password confirmation in Akun | §8.14 Akun |
| Asset category and title | §8.9 Galeri media |
| Payer data at plan checkout | §8.15 |
| Payout minimum Rp 10.000 and up to 3 accounts | §8.12 |
| Refund on owner cancellation follows D-001 | §8.4 |
| Delete account confirmation | §8.14 Akun: email and current password |

The one feature deliberately dropped is "Salin URL" for assets, because images are used through the picker.

### 11.3 Removed

| Removed | Reason |
|---|---|
| Payment gateway selection and stored Midtrans credentials | D-016. The credentials are deleted from storage, not only hidden (inventory §5) |
| Staff as service providers: staff fields, shifts, `staff_ids[]` on services | D-011. Existing staff rows are archived, and owners invite team members again (inventory §5) |
| Bulk price adjustment panel on Schedule | No backend |
| Old withdrawal form on the payment settings page | Replaced by BQ-123 to BQ-125 |
| Duplicate routes (`owner.programs.*`, `owner.settings.payment-setting`) | One path per page (§10.2) |
| "Salin URL" in assets | §11.2 |

## 12. When the subscription has ended (D-002)

Applies to every page from the moment the trial or paid period ends, until a new payment succeeds:

| Area | Behaviour |
|---|---|
| Sign-in, Beranda, Kalender, Booking, Pelanggan, Ulasan, Pembayaran, Notifikasi, Bantuan | Available. Existing bookings can be completed, rescheduled, cancelled and refunded |
| Creating anything new: services, slots, units, promos, team members, walk-ins, + Booking | Refused on the server; buttons show the "Paket berakhir" prompt |
| Paket & Tagihan | Available; the main call to action on Beranda |
| Public booking page | Shown, without booking buttons: "Bisnis ini belum menerima booking online saat ini." |
| Paid bookings | Served as usual |
| Reminders | 3 days and 1 day before the end, and on the day it ends (in-app and email) |

Acceptance criteria:
- **AC-END-01:** Every create route listed above is refused for an ended subscription, while the listed available routes keep working.
- **AC-END-02:** The storefront refuses checkout for an ended subscription, and shows the neutral message.

## 13. Build order

The epics and their work items are defined in `docs/work/ROADMAP.md` (BQ-126 phase 8). The order follows dependency:

1. shell and shared blocks;
2. core loop pages;
3. approved decisions D-001, D-002, D-006;
4. trust and acquisition pages;
5. Medium value;
6. Pro value;
7. release.

A page is done when every acceptance criterion in its contract passes, and `OwnerSidebarNavigationTest` matches §4.

## 14. Changing this document

- A new page, tab, section or action starts here: add or change its contract, then build it.
- What a page offers is a product decision (Product Lead, through `product-decisions.md`). Where it sits and how it is built follows §3 (Tech Lead).
- Keep the contract fields of §8. Give every new acceptance criterion an ID (`AC-<PAGE>-NN`), and never reuse one.
