# Owner Portal

What each owner page is for, what it contains, and where every owner capability lives. `product.md` says *which* capabilities exist and which tier they are in; this document says *how they are arranged into pages* so that no two pages do the same job. Required behaviour stays in `requirements.md`.

> **Status: Draft (2026-11-02).** Waits for the Product Lead on [D-007](product-decisions.md) (page map) and [D-008](product-decisions.md) (plan contents). Until both are approved, `product.md` §8 and the current code stay authoritative for releases; this draft is the agreed direction for planning only. After approval: `product.md` §5 links here, the page contracts in §6 become the acceptance criteria for owner UI work, and `.ai/rules/frontend.md` points owner views here.

## 1. Why this document exists

The owner portal was built from a list of page names without page contents. Each implementer (often an agent) filled the gaps with its own idea of each page. The result, found by the owner UI audits (BQ-089 to BQ-112):

- **Overlap.** Booking details exist three times (bookings modal, calendar modal, schedule modal), each with its own actions. The dashboard carries revenue charts that belong to analytics. Analytics and schedule report both measure utilization.
- **Invented features.** A "Bulk Price Adjustments" panel with no backend, notification preferences stored only in the browser, integration cards that report platform internals (SMTP, Midtrans) as if the owner had connected them.
- **Settings in the wrong place.** Cancellation and reschedule deadlines (booking policy) sit in the schedule "availability" modal; logo, banner and brand colour sit in a hidden module, so released storefronts cannot be branded.
- **Promises without features.** The plan list (`config/plans.php`) sells staff, role management, WhatsApp reminders, vouchers, analytics, export, BYOPG, multi-location, white-label and automation, most of which are hidden, locked or out of scope (§9).

The fix is a contract per page, written before code, that every implementer follows.

## 2. Owner jobs

Pages are organised around what the owner comes to do, ordered by how often they do it. Steps refer to the core loop in `product.md` §3.

| Frequency | Job | Core loop step | Target page |
|---|---|---|---|
| Several times a day | See what happens today and what needs me | 9 | Beranda |
| Several times a day | See who comes when; record a walk-in; finish a booking | 9 | Kalender |
| Daily | Find a booking, check its payment, cancel or reschedule it | 8–9 | Booking |
| Weekly | Open slots for the coming weeks, block holidays, adjust prices | 3 | Jadwal |
| Weekly | Look up a customer, read or add a note, contact them | 10 | Pelanggan |
| Weekly | Read and answer reviews | 10 | Ulasan |
| Monthly | Check money earned, withdraw it | 7, platform | Saldo |
| Monthly | See how the business is doing over time | 11 | Laporan |
| Rarely | Set up the business, services, policy, branding | 1–2 | Layanan, Pengaturan |
| Rarely | Manage the BookQu plan | platform | Langganan |

Daily jobs get the top of the navigation and must work well on a phone, because owners run them at the counter.

## 3. Placement rules

These rules decide where anything new goes. A change that breaks one needs a Tech Lead decision.

1. **One job per page.** Every page answers one owner question (§6 "Question"). Content that answers another question links to that page instead.
2. **One home per entity.** Each entity is created and edited on exactly one page (§5). Other pages may show it read-only and open the shared drawer; they never get their own edit form.
3. **One component per entity view.** A booking is always shown by the shared booking drawer, a customer by the shared customer drawer (§7), wherever it is opened from.
4. **Now versus history.** Beranda shows today and what needs action, with no charts. Trends, comparisons and exports live in Laporan.
5. **Configuration versus operation.** Pages that set rules (Jadwal, Pengaturan) do not show daily operations; operational pages (Kalender, Booking) do not change rules.
6. **Integrations live with their job.** There is no "Integrations" page. A WhatsApp reminder is a notification setting, a Google Calendar sync is a calendar option, a tracking pixel is a storefront setting.
7. **Only working features are shown.** No teaser cards, decorative previews or controls without a backend. A locked (Tier 2) entry is allowed only for a module scheduled in the next release (§10); otherwise it is hidden.
8. **The plan list promises only released features.** A feature appears in `config/plans.php` only when it is Tier 1 for that plan (§9).
9. **Plan gating looks the same everywhere.** A feature above the owner's plan shows the shared upgrade prompt (§7), never a page-specific banner.
10. **Every page states who may use it.** Today only the owner signs in; the "Access" field in each contract prepares for team accounts (§8, "Tim").

## 4. Navigation

### 4.1 Target sidebar

| Group | Item | Page | Plan | Badge |
|---|---|---|---|---|
| — | Beranda | `/owner/dashboard` | all | — |
| — | Kalender | `/owner/calendar` | all | — |
| — | Booking | `/owner/bookings` | all | items needing action |
| — | Pelanggan | `/owner/customers` | all | — |
| — | Ulasan | `/owner/reviews` | all | unanswered reviews |
| Kelola | Layanan | `/owner/services` (tabs: Layanan, Kategori) | all | — |
| Kelola | Jadwal | `/owner/schedule` | all | — |
| Keuangan | Saldo | `/owner/settings/balance` | all | — |
| Keuangan | Laporan | `/owner/analytics` (absorbs schedule report) | medium+ | lock until released |
| Bottom | Pengaturan | `/owner/settings/business` (tabs, §6.11) | all | — |
| Bottom | Langganan | `/owner/subscription` (plan card) | all | days left in trial |

Notifications are reached from the topbar bell ("Lihat semua" opens `/owner/notifications`); they leave the sidebar. Eleven entries replace today's fifteen. Route paths stay as they are; only labels, grouping and page contents change, so old links keep working.

### 4.2 Every current page and module, and its fate

| Today | State today | Target | Why |
|---|---|---|---|
| Dashboard | released | **Beranda**, rebuilt as "today and needs action" | Charts move to Laporan (rule 4) |
| Notifications (sidebar item and page) | released | Bell plus page, no sidebar item | Reached from the bell; one way in |
| Calendar | released | **Kalender**, operational view and walk-in | — |
| Schedule | released | **Jadwal**, configuration only | Weekly booking grid, booking modals, "Recent Activity" and the fake price panel leave (rules 2, 5, 7) |
| Bookings | released | **Booking** | Booking details move into the shared drawer |
| Services | released | **Layanan**, tab "Layanan" | — |
| Categories | released | **Layanan**, tab "Kategori" | Too small for its own page; it only serves services |
| Customers | released | **Pelanggan** | — |
| Reviews | released | **Ulasan**, top level | Group "Marketing" disappears (one item) |
| Analytics (locked) | Tier 2 | **Laporan** | Merge with schedule report (one utilization view) |
| Schedule Report (locked) | Tier 2 | **Laporan**, tab "Utilisasi" | Same |
| Settings → Business | released | **Pengaturan** → Profil Bisnis, Akun | Business data and personal account separated |
| Notification Preferences (in settings) | fake (browser only) | **Pengaturan** → Notifikasi, real or removed | Rule 7 |
| Integrations (locked) | Tier 2 | **Removed**; future integrations go to their job (rule 6) | Shows platform internals; nothing an owner can connect |
| Saldo & Withdraw | released | **Saldo**, plus the transaction ledger | Ledger rows come from the hidden payment-settings page |
| Subscription, checkout, invoice | released | **Langganan** | — |
| Appearance (hidden) | Tier 3 | Logo, banner, brand colour → **Pengaturan → Halaman Booking** (Tier 1); fonts, radius, card layout → removed or folded into the Pro page builder | Storefront branding is basic, not premium (FR-PUBLIC-003) |
| Landing page builder (hidden) | Tier 3 | **Pengaturan → Halaman Booking**, Pro section (future) | One home for "how my booking page looks" |
| Media assets (hidden) | Tier 3 | **Removed** | No job of its own; images are uploaded where they are used |
| Direct payment / payment settings (hidden) | Tier 3 | **Removed**; ledger rows move to Saldo | Platform escrow is the model (ADR-0001); tenant gateways are not offered |
| Vouchers (hidden) | Tier 3 | **Promo**, group Kelola (future, medium+) | Needs checkout pricing first (§8) |
| Additional items (hidden) | Tier 3 | **Layanan**, tab "Add-on" (future) | Add-ons belong to services |
| Staff & resources (hidden) | Tier 3 | Frozen; meaning decided in D-007 point 6 | Today it affects nothing in booking (§8) |

## 5. Entity homes

C = create, E = edit, V = view, A = state actions (complete, cancel, reschedule), D = shared drawer. A blank cell means the entity does not appear.

| Entity | Beranda | Kalender | Booking | Pelanggan | Ulasan | Layanan | Jadwal | Saldo | Laporan | Pengaturan | Langganan |
|---|---|---|---|---|---|---|---|---|---|---|---|
| Booking | V, D | **C (walk-in)**, D | **C (walk-in), A**, D | V, D | link | | count only | link | aggregate | | |
| Payment (booking) | status | status | **V** in drawer | | | | | ledger row | aggregate | | |
| Customer | name | name | name, D | **E (notes)**, D | name | | | | aggregate | | |
| Review | count | | link | V | **E (reply, hide)** | | | | aggregate | | |
| Service | name | filter | filter | | | **C, E** | pick | | aggregate | | usage |
| Category | | | | | | **C, E** (tab) | | | | | |
| Schedule slot | count | V, block from empty slot | | | | | **C, E** | | utilization | | |
| Blocked date | | V | | | | | **C, E** | | | | |
| Booking policy | | | read in drawer | | | | | | | **E** | |
| Business profile, branding | | | | | | | | | | **E** | |
| Owner account | | | | | | | | | | **E** | |
| Balance, payout | available | | | | | | | **V, C (request)** | | | |
| Subscription | trial / expiry | | | | | limits | | | | | **V, C (checkout)** |
| Notification | bell | bell | bell | bell | bell | bell | bell | bell | bell | **E (preferences)** | bell |

Walk-in has two entry points (Kalender from an empty slot, Booking from a button) but one form and one action, so it is still one home.

## 6. Page contracts

Each contract is the acceptance criteria for its page. Fields: **Question** (the owner question the page answers), **Access**, **Plan**, **Shows** (with data source), **Actions**, **Not here** (what must not be added), **Empty state**, **Requirements**, **Open** (decisions or items that change the page).

### 6.1 Beranda (`/owner/dashboard`)

- **Question:** "What is happening today, and what needs me?"
- **Access:** owner (future: every team role). **Plan:** all.
- **Shows:**
  1. Header: greeting, today's date, the storefront link with copy and share buttons (built by `CustomerBookingRoutes`).
  2. Setup checklist, shown until done and then dismissed for good: business profile complete, logo uploaded, first service, slots opened, storefront link shared. Replaces today's profile prompt.
  3. Plan status (one place only): trial days left, expiry warning, or expired state with what still works (D-002).
  4. **Hari ini**: timeline of today's bookings in time order (time, customer, service, booking status, payment status). Each row opens the booking drawer; a paid booking whose time has passed offers "Selesai".
  5. **Perlu tindakan**: list with counts and links: refund requests awaiting a decision (D-001), bookings cancelled or rescheduled by customers since the last visit, unanswered reviews, payout status changes, and a **schedule runway warning** when a service has no open slots in the next 7 days.
  6. Four figures for the current period, each linking to its source page: bookings this week, paid revenue this month (from payments, not service prices), available balance, slot occupancy for the next 7 days.
- **Actions:** open drawer, mark complete, copy/share storefront link, go to the linked page.
- **Not here:** revenue or trend charts, service ranking, period toggles, export (all Laporan). Lists longer than today (Booking). Its own booking modal.
- **Live refresh:** the timeline and "Perlu tindakan" refresh by polling while the page is visible; values are rendered as text (BQ-089).
- **Empty state:** new tenant sees the checklist as the main content; a quiet day shows "Belum ada booking hari ini" with the next upcoming booking.
- **Requirements:** FR-DASH-001 to FR-DASH-007, FR-NOTIFICATION-001.
- **Open:** D-001 (refund requests appear only if approved), D-002 (expired state).

### 6.2 Kalender (`/owner/calendar`)

- **Question:** "Who comes when, and which slots are still free?"
- **Access:** owner (future: staff roles). **Plan:** all.
- **Shows:** day, week and month views of slots (available, booked, blocked) and the bookings in them; filter by service; "Hari ini" button; date navigation. Day view is the default on phones.
- **Actions:** click a booking → booking drawer (all booking actions live there). Click an empty slot → choose "Booking walk-in" (shared walk-in form) or "Blokir slot". Link "Atur jadwal" to Jadwal.
- **Not here:** creating slots in bulk, prices, availability rules, booking policy (Jadwal, Pengaturan). Its own booking detail modal.
- **Empty state:** no slots in range → "Belum ada slot di periode ini" with a button to Jadwal.
- **Requirements:** FR-CALENDAR-001 to FR-CALENDAR-006, FR-BOOKING-010.
- **Open:** Google Calendar sync lands here as an option (future, rule 6).

### 6.3 Booking (`/owner/bookings`)

- **Question:** "Where is this booking, and what is its state?"
- **Access:** owner. **Plan:** all; export medium+ (with Laporan).
- **Shows:** search by booking code, customer name, phone or email; filters by booking status, payment status, service and date range; quick tabs "Mendatang", "Hari ini", "Selesai", "Dibatalkan"; a count per tab. Rows: date and time, customer, service, slots, amount (from the payment record), booking status, payment status. Card layout on phones.
- **Actions:** open the booking drawer; "Booking walk-in" button (shared form); export CSV of the filtered list (medium+).
- **Not here:** charts or totals over time (Laporan), customer notes (Pelanggan), slot editing (Jadwal).
- **Empty state:** no bookings yet → explain that bookings arrive from the storefront, with the copyable link.
- **Requirements:** FR-BOOKING-008 to FR-BOOKING-016, FR-MULTIBOOK-005, FR-MULTIBOOK-006.
- **Open:** BQ-087 (actions locked during an open payment), BQ-095 (amount and payment state), BQ-109 / D-006 (reschedule price difference).

### 6.4 Shared booking drawer (opened from Beranda, Kalender, Booking, Pelanggan)

- **Shows:** booking code; customer (name, phone, email, link to Pelanggan, WhatsApp link); service and every slot of a multi-slot booking; customer note; payment (status, method, amount, paid at, payment deadline while pending); refund status if any; history from the booking log (created, paid, rescheduled from/to, cancelled with reason and actor).
- **Actions:** only those `BookingState::canTransition` allows for the current state and BQ-087 permits: "Selesai", "Reschedule" (slot picker of the same service), "Batalkan" (reason required; refund consequence explained per D-001). Copy the customer's access link where allowed (ADR-0006).
- **Not here:** editing customer data, editing price.
- **Requirements:** FR-BOOKING-008, FR-BOOKING-009, FR-BOOKING-011, FR-BOOKING-014, FR-PAYMENT-003.

### 6.5 Pelanggan (`/owner/customers`)

- **Question:** "Who are my customers, and what is their history with me?"
- **Access:** owner. **Plan:** all (core loop step 10).
- **Shows:** directory built from bookings (one row per customer identity): name, phone, email, number of bookings, total paid, last visit, cancellations; search and sort; summary counts (total, new this month, returning).
- **Actions:** open the customer drawer: booking history (each opens the booking drawer), internal notes (add, edit), WhatsApp and e-mail links.
- **Not here:** campaigns, segments, bulk messaging (out of scope, `product.md` §9), editing a customer's booking data.
- **Empty state:** "Pelanggan muncul otomatis setelah booking pertama."
- **Requirements:** FR-CUSTOMER-001 to FR-CUSTOMER-005.
- **Open:** BQ-100. Customer export may join the medium+ export set later.

### 6.6 Ulasan (`/owner/reviews`)

- **Question:** "What do customers say, and have I answered?"
- **Access:** owner. **Plan:** all.
- **Shows:** average rating and distribution; list with filters (rating, answered/unanswered, visible/hidden) and search; each review links to its booking.
- **Actions:** reply (edit reply), hide or show on the storefront.
- **Not here:** rating charts over time (Laporan).
- **Empty state:** explain that customers review through their access link after a completed booking.
- **Requirements:** FR-REVIEW-001 to FR-REVIEW-007.
- **Open:** BQ-101.

### 6.7 Layanan (`/owner/services`)

- **Question:** "What can customers book, and at what price?"
- **Access:** owner. **Plan:** all; number of services limited by plan.
- **Tab Layanan:** list (image, name, category, price and unit, duration, capacity note, active, popular); usage against the plan limit ("3 dari 5 layanan"); create/edit form: name, category, price, price unit, duration and unit, description, image, maximum people per booking (informational), popular flag, active. Delete only when FR-SERVICE-006 allows; otherwise offer deactivation.
- **Tab Kategori:** list with service counts, create, edit, activate, delete (when empty); "Lihat layanan" filters the first tab.
- **Future tab Add-on** (Tier 3, §8).
- **Not here:** slots and slot prices (Jadwal), staff breakdown cards (removed until §8 "Staf" is decided).
- **Empty state:** first-service guide that continues to Jadwal after saving.
- **Requirements:** FR-SERVICE-001 to FR-SERVICE-007, FR-CATEGORY-001 to FR-CATEGORY-005, FR-SUB-010.
- **Open:** BQ-099.

### 6.8 Jadwal (`/owner/schedule`)

- **Question:** "When can each service be booked, and at what price?"
- **Access:** owner. **Plan:** all; slot creation follows D-002 when the plan has expired.
- **Shows (tabs):**
  1. **Slot**: per service and week, a list or grid of slots with state and price; the runway per service ("slot terbuka sampai 14 Des").
  2. **Buat slot**: bulk generator (service, date range with a maximum, weekdays, start and end time, interval from the service duration); preview of how many slots will be created and which already exist.
  3. **Harga**: default price comes from the service; weekend rule (type and value); per-slot override from the Slot tab.
  4. **Libur**: blocked dates with conflict check against active bookings (BQ-108).
- **Actions:** create slots, override a slot price, delete or block a free slot (never one with an active booking, INV-BOOK-03), add or remove blocked dates.
- **Not here:** booking details and booking actions (drawer from Kalender/Booking), "Recent Activity", booking policy (Pengaturan → Kebijakan Booking), any control without a backend.
- **Empty state:** "Buka slot pertama" guide.
- **Requirements:** FR-SCHEDULE-001 to FR-SCHEDULE-010.
- **Open:** BQ-096, BQ-107, BQ-108. Future: weekly opening hours that generate slots automatically (§12).

### 6.9 Saldo (`/owner/settings/balance`)

- **Question:** "How much money is mine, and when do I get it?"
- **Access:** owner only, also in the future (money). **Plan:** all, also when expired (D-002).
- **Shows:** available balance, amount still in settlement, total earned; **ledger** of every movement (booking payment in, refund out, payout out) with date, booking code and amount; payout history with status, rejection reason and transfer proof.
- **Actions:** request a payout (bank account per D-004), view proof.
- **Not here:** gateway credentials or payment-method settings (removed), revenue charts (Laporan).
- **Requirements:** FR-PAYOUT-001 to FR-PAYOUT-003, FR-PAYMENT-011.
- **Open:** D-004, BQ-103.

### 6.10 Laporan (`/owner/analytics`)

- **Question:** "How is my business doing over time?"
- **Access:** owner. **Plan:** medium+. **Tier:** 2 (locked) until released (§10).
- **Shows:** one period filter (this month, last month, 3, 6, 12 months, custom) shared by four tabs:
  1. **Pendapatan**: paid revenue per month and per service, refunds, net (from payments).
  2. **Booking**: bookings per day or month, status distribution, cancellation and reschedule rates, walk-in share.
  3. **Layanan**: ranking by bookings and revenue.
  4. **Utilisasi** (today's schedule report): occupancy rate, busiest and quietest hours and days.
- **Actions:** export CSV per tab (formula-safe, BQ-111).
- **Not here:** today's operations (Beranda), individual bookings (Booking).
- **Requirements:** FR-ANALYTICS-001 to FR-ANALYTICS-006, FR-REPORT-001 to FR-REPORT-003.
- **Open:** BQ-111; locked exports must also refuse while locked.

### 6.11 Pengaturan (`/owner/settings/business`)

One page with tabs; each tab saves separately.

| Tab | Question | Contents | Plan | Requirements, open |
|---|---|---|---|---|
| **Profil Bisnis** | "How is my business described?" | Name, business type, address, phone, description; storefront address (slug) with change warning | all | FR-TENANT-004, FR-TENANT-005; BQ-110 |
| **Halaman Booking** | "How does my booking page look?" | Link, copy, QR code; logo, banner, brand colour with live preview. Pro section later: templates and sections (today's landing builder), custom domain (D-005) | all; Pro section pro | FR-PUBLIC-003, FR-APPEARANCE-001 to FR-APPEARANCE-004, FR-TENANT-007 |
| **Kebijakan Booking** | "What may customers change themselves?" | Cancellation deadline, reschedule deadline (moved out of the schedule modal); refund settings if D-001 is approved | all | FR-BOOKING-012, FR-BOOKING-013; D-001 |
| **Notifikasi** | "What am I told, and how?" | Per event (new booking, payment, cancellation, reschedule, review, payout, plan): in-app always, e-mail on/off. Stored on the server. WhatsApp later (medium+) | all | FR-NOTIFICATION-001 to FR-NOTIFICATION-005; BQ-097 |
| **Akun** | "How do I sign in?" | Owner name, e-mail, password (current password required), delete account | all | FR-AUTH-006; BQ-098 |
| **Tim** (future) | "Who else may sign in?" | See §8 | medium+ | D-007 point 6 |

- **Not here:** subscription (Langganan), balance (Saldo), payment gateway credentials (removed).

### 6.12 Langganan (`/owner/subscription`, checkout pages)

- **Question:** "Which plan am I on, what does it give me, and what have I paid?"
- **Access:** owner only. **Plan:** all, including expired.
- **Shows:** current plan, status and end date; usage against limits (services, bookings this month); plan comparison generated from `config/plans.php` (only released features, rule 8); billing history with invoices.
- **Actions:** choose a plan → checkout → payment → invoice (existing pages).
- **Requirements:** FR-SUB-001 to FR-SUB-011.
- **Open:** D-002, D-008, BQ-086, BQ-104.

### 6.13 Notifikasi (bell and `/owner/notifications`)

- **Question:** "What happened that I should know about?"
- **Shows:** bell with unread count and the latest items; the page lists all with filters (unread, type) and deep links to the booking drawer, review or payout.
- **Actions:** mark read, mark all read, delete (shared confirm dialog).
- **Requirements:** FR-NOTIFICATION-001 to FR-NOTIFICATION-005. **Open:** BQ-102.

## 7. Shared building blocks

Built once (BQ-092) and used by every page above:

| Block | Used by | Rule |
|---|---|---|
| Page header (title, one-line purpose, primary action) | all pages | One primary action per page |
| Booking drawer (§6.4) | Beranda, Kalender, Booking, Pelanggan | Rules 2–3 |
| Customer drawer | Pelanggan, booking drawer | Rules 2–3 |
| Walk-in form | Kalender, Booking | One action (`FR-BOOKING-010`) |
| Status badges (booking, payment, payout) | everywhere | One colour and label per state |
| Upgrade prompt | Laporan, export, plan limits, Pro sections | Rule 9 |
| Empty state (what it is, how to start, one button) | every list | — |
| Confirm dialog, toast | every destructive action | No native `alert`/`confirm` |
| Tabs | Layanan, Jadwal, Laporan, Pengaturan | Tab state in the URL so links and reloads keep it |

## 8. Supporting modules: target home and prerequisites

A module is promoted only when its prerequisites are done, its page contract is added here, and the release decision is recorded (`product.md` §8, ADR-0002, ADR-0011).

| Module | Target home | Plan | Prerequisites before release |
|---|---|---|---|
| **Laporan** (analytics + schedule report) | Keuangan → Laporan | medium+ | Figures from payments (not service prices), aggregated queries, formula-safe export, export refused while locked (BQ-111) |
| **Promo** (vouchers) | Kelola → Promo | medium+ | Price calculation in checkout and on the invoice, discount shown on payment and refund amounts, usage limits enforced under concurrency |
| **Add-on** (additional items) | Layanan → tab Add-on | all or medium+ (D-008) | Selection in the storefront Data & Pembayaran step, line items on payment and invoice, refund calculation |
| **Halaman Booking Pro** (landing builder, advanced appearance) | Pengaturan → Halaman Booking, Pro section | pro | Basic branding released first; D-005 for domains |
| **WhatsApp reminder** | Pengaturan → Notifikasi | medium+ | A provider decision (product change), consent and opt-out, cost per message |
| **Google Calendar sync** | Kalender option | pro | Product change; one-way export first |
| **Tim** (team accounts) | Pengaturan → Tim | medium+ | Product change: roles and the page access matrix (rule 10); authentication changes (Tech Lead) |
| **Staf sebagai penyedia layanan** (bookable staff or rooms) | Layanan and Jadwal | — | Product change: slots per staff or resource, availability per person; not planned |
| Media assets | removed | — | — |
| Direct payment gateway (BYOPG) | removed | — | Contradicts platform escrow (ADR-0001) |
| Integrations page | removed | — | Integrations live with their job (rule 6) |

**About "staff".** Today "staff" means two different things: the plan list sells "1 admin + 2 staff" and "role management" (people who sign in), while the hidden module stores names and roles that no booking uses. D-007 point 6 asks the Product Lead to choose: staff as **team accounts** (Tim, recommended next) or as **bookable providers** (a schedule redesign). Until then the module stays hidden and the plan list does not mention staff.

## 9. Plans and entitlements

What each plan really gives today, compared with what `config/plans.php` promises. D-008 asks the Product Lead to make the list honest.

| Promised in `config/plans.php` | Plan | Reality today | Proposed |
|---|---|---|---|
| Booking and service limits, booking page, calendar and slots, e-mail, dashboard, invoice, BookQu payments | small | Released | Keep |
| "1 admin + 2 staff" | small | Staff module hidden, no team accounts | Remove until Tim is released |
| "Hingga 15 staff", "Role management" | medium | Hidden / does not exist | Remove until Tim |
| "Customer CRM" | medium | Customers are released for every plan (core) | Remove from medium; customers stay in all plans |
| "WhatsApp & email reminders" | medium | No reminders exist | Remove until released |
| "Promo & voucher" | medium | Hidden | Remove until Promo is released |
| "Complete analytics", "Export Excel / CSV" | medium | Locked | Keep, marked "segera hadir" until Laporan is released |
| "BYOPG + platform payment" | medium | Not offered (ADR-0001) | Remove |
| "Unlimited staff", "Advanced permission" | pro | Do not exist | Remove until Tim |
| "Multi-location", "White-label", "Automation workflow" | pro | Out of scope (`product.md` §9) | Remove |
| "Custom domain", "Landing page builder" | pro | Hidden; D-005 | Keep only after release |
| "Advanced analytics", "Priority support" | pro | Not defined | Define or remove |

After cleanup the honest launch offer is: **small** = the full core loop with limits; **medium** = no service limit, more bookings, and Laporan once released; **pro** = unlimited bookings now, the Pro booking page and domain later. Pro has little real value at launch; D-008 asks whether to keep it, re-price it, or show it as "segera hadir".

Entitlement is decided only by `EntitlementRules` (INV-SUB-01); this table only feeds the plan list and the upgrade prompts. The correction itself (plan list, landing feature claims such as Google Calendar sync and down payments, one list for landing and subscription) is BQ-115; D-008 decides the list it applies.

## 10. Release path

| Stage | When | Contents |
|---|---|---|
| **0 — Remove what is false** | before launch, small PRs | Remove the fake price panel, browser-only notification preferences, staff remnants and the Integrations entry (hidden); make the plan list and landing claims honest (D-008, BQ-115). BQ-097 extended. Launch blockers (BQ-086, BQ-087, BQ-089, BQ-098) unaffected. |
| **1 — Foundations** | right after launch | Shared blocks of §7 (BQ-092), shell performance (BQ-090), navigation model of §4 (BQ-091), copy (BQ-093) |
| **2 — Page rework, core loop order** | one PR per page | Layanan (with Kategori) → Jadwal → Kalender + booking drawer → Booking → Beranda → Pelanggan → Ulasan → Saldo → Pengaturan (tabs, Halaman Booking branding, Kebijakan Booking) → Langganan |
| **3 — Laporan** | after stage 2 | Release Laporan for medium+ (Tier 2 → 1) |
| **4 — Growth modules** | after product decisions | Promo, Add-on, WhatsApp reminder |
| **5 — Pro** | after product decisions | Halaman Booking Pro, custom domain, Tim |

Each page in stage 2 is done when its contract in §6 is met, the audit items mapped to it in §11 are closed, and `OwnerSidebarNavigationTest` matches §4.

## 11. Effect on existing work items

| Item | Effect |
|---|---|
| BQ-089, BQ-098, BQ-086, BQ-087 | Unchanged; launch blockers |
| BQ-090, BQ-091, BQ-092, BQ-093 | Stage 1; BQ-091 targets the sidebar in §4.1 |
| BQ-097 | Stage 0; extended with the Integrations entry and the plan list |
| BQ-094 (dashboard figures) | Absorbed by the Beranda rework; fix only what survives (signed figures, payment-based revenue) |
| BQ-095 (bookings) | Absorbed by the Booking page and drawer |
| BQ-096 (calendar, schedule) | Absorbed by Jadwal and Kalender; the fake panel goes in stage 0 |
| BQ-099 (services, categories) | Absorbed by Layanan with the Kategori tab |
| BQ-100, BQ-101, BQ-103, BQ-104 | Done with their page in stage 2 |
| BQ-102, BQ-105, BQ-106 | Cross-cutting; done with stage 1 blocks or alongside each page |
| BQ-115 | Stage 0; applies D-008 to `config/plans.php` and the landing page |
| BQ-107, BQ-108, BQ-109, BQ-110, BQ-112, BQ-113, BQ-114, BQ-116 to BQ-121 | Backend and storefront correctness; independent of this map, keep their own order |
| BQ-111 | Prerequisite of stage 3 |

## 12. Looking further ahead

- **Automatic slot opening.** Owners must open slots by hand, so slots run out silently. Weekly opening hours per service that keep, for example, four weeks open through the scheduler would remove most Jadwal work; the runway warning on Beranda covers the gap until then. Product change.
- **Activation.** The setup checklist and storefront sharing (link, QR code) are the first thing a trial owner needs; trial conversion depends on reaching "first booking received" within the 7 days. `UsageLog` already records bookings and services and could measure this funnel.
- **Phone first for daily pages.** Beranda, Kalender and the booking drawer are used at the counter; design them for 375 px first, the configuration pages for desktop first.
- **Performance budget.** Each page loads only its own data; only Beranda and the bell poll; charts load only on Laporan.
- **Team readiness.** The "Access" field in every contract becomes the role matrix when Tim arrives; no page should assume the viewer is the owner.
- **One vocabulary.** Indonesian labels throughout (Beranda, Kalender, Booking, Pelanggan, Ulasan, Layanan, Jadwal, Saldo, Laporan, Pengaturan, Langganan), the same word for the same thing on every page (BQ-093).

## 13. Changing this document

- A new page, tab or section starts here: add or change its contract, then build it. Code that adds owner UI not described here is out of scope (`AGENTS.md` rule 15).
- What a page offers is a product decision (Product Lead, through `product-decisions.md`); where it sits and how it is built follows the rules in §3 (Tech Lead).
- Keep contracts short: question, access, plan, shows, actions, not here, empty state, requirements, open.
