# Glossary

Canonical BookQu terms and the legacy names that still appear in the code.

**Rule:** new code, docs and tests use the canonical term. Legacy names stay in the database and old code until a dedicated migration task renames them (see `docs/architecture.md` → Naming). Never introduce a new synonym.

## Core terms

| Canonical term | Meaning | Legacy names in code / DB | Do not introduce |
|---|---|---|---|
| **Tenant** | One business operating inside BookQu; the isolation boundary. UI may say "Business" / "Bisnis". | `tenants` table; `idtenant` FK; `namabisnis`, `jenisbisnis`, `alamat`, `nomorhp`, `slug_usaha` (route param) | a separate `Business` entity |
| **Owner** | The authenticated user who runs a tenant. | `users.role = 'owner'`; `users.namalengkap`; `Tenant::user()` via `iduser` | Seller, Merchant |
| **Customer** | Person who makes or receives a booking. Not a `User`; has no account. | `bookings.namapelanggan`, `bookings.nomorhp`, `bookings.email`; `payments.nama_pembayar`, `email_pembayar`, `hp_pembayar` | Customer-as-User, Member |
| **Platform Admin** | BookQu staff with platform-wide oversight. | `users.role = 'admin'` | — |
| **Team member** | A user invited by the owner to work in one tenant with a role (D-011, FR-TEAM). Not a service provider. | not implemented yet (BQ-126); the old `staff` table means something else and is archived | Staff (for a person who signs in), Employee, Agent |
| **Role** (Pemilik, Manajer, Kasir) | What a user may do inside one tenant: Pemilik = the owner; Manajer = operations and configuration without money, plan or team; Kasir = the counter, without revenue figures (FR-TEAM-002). | not implemented yet | custom role names |
| **Service** | Something a customer can book (what). | `services` table; `idlayanan` FK; `namalayanan`, `harga`, `durasi`, `kapasitas`, `satuan_harga`, `satuan_durasi`; `Booking::layanan()`; routes/controller `OwnerProgramController`, `/owner/programs`, `select-program`, `program-selection` | Program, Layanan (as a concept), Bookable Program |
| **Category** | Grouping of services. | `categories`, `services.idcategory` | — |
| **Schedule** | One bookable time slot of a service (when). | `schedules` table; `idschedule`; `tanggal`, `jam_mulai`, `jam_selesai`, `harga_override`; "slot" in UI and routes | Program slot |
| **Availability** | Whether a schedule can be booked now. Derived, never stored. | `AvailabilityRules` statuses `AVAILABLE`, `BOOKED`, `BLOCKED`, `UNAVAILABLE` | a stored "availability" entity |
| **Blocked date** | A date the owner closed for booking. | `owner_blocked_dates.tanggal` | — |
| **Booking** | A reservation of one schedule. A multi-slot reservation is several bookings sharing one payment. | `bookings` table; `tanggalbooking`, `jam`, `catatan`, `booking_code` | Order, Reservation entity |
| **Walk-in booking** | Booking created by the owner or a team member for an on-site customer. | `CreateWalkInBooking`, route `owner.bookings.walkin` | a separate walk-in domain |
| **No-show** | Final booking state for a paid booking whose customer did not come; UI "Tidak datang" (D-013). | target `bookings.status = 'no_show'` | Absent, Missed |
| **Capacity** / **Participants** | Capacity: how many participants one slot of a service takes. Participants: how many people one booking covers (D-014, FR-BOOKING-021). | `services.kapasitas` (stored, not yet enforced) | Seats, Quota (for capacity) |
| **Booking policy** | The tenant's rules for refunds, cancellation and reschedule deadlines and the booking window (FR-TENANT-008). | `tenants.cancel_before_hours`, `tenants.reschedule_before_hours` | separate policy entities per rule |
| **Refund request** | A customer's request to cancel a paid booking and get money back, decided by the owner (100%, 50% or rejected) (D-001, FR-REFUND). | not implemented yet | Refund claim |
| **Payment group** | All bookings paid by one payment (multi-slot). | `payments.id` ↔ `bookings.idpayment`; `payments.idbooking` = first booking (legacy, deprecated) | — |
| **Payment** | A money transaction: booking payment or subscription payment. | `payments` table; `tipe` (`booking`, `subscription`), `jumlah`, `metode`, `provider`, `order_id`, `external_id` | generic Transaction |
| **Refund** | Money owed back after an eligible cancellation or a late payment success. Recorded `pending`, returned manually. | `refunds` table; `jumlah` | — |
| **Payment lifecycle module** | The one module that changes a payment's status (provider outcome, expiry, payer cancel, owner settlement, free/walk-in); fans out booking-group and subscription consequences (ADR-0004). | `App\Actions\Payment\PaymentLifecycle` with `Lifecycle/*Consequences` adapters; `PaymentTransition`, `PaymentTransitionOutcome` | writing `payments.status` anywhere else |
| **Owner settlement** | Owner records that a booking was paid outside the gateway (on site); settles the whole payment group as `sukses` with `metode = cash`. | `UpdateBookingStatus` → `PaymentTransition::SettledByOwner` | per-slot payment marking |
| **Late success** | Provider reports success for a payment already `gagal`; creates a refund owed and an alert. | `PaymentLifecycle` → outcome `refund-owed` | — |
| **Provider outcome** | A provider's verified statement about one payment, translated by its adapter into a neutral result (`sukses`, `gagal`, `pending`, `unknown`) with amount, currency and provider transaction id (ADR-0007). | `App\Domain\Payment\ProviderOutcome`, built by `queryOutcome()` and `outcomeFromNotification()` in each adapter | provider status words (`settlement`, `capture`) outside adapters |
| **Outcome intake** | The module that validates provider outcomes once (payment, provider, exact amount, currency, tenant) and hands them to the Payment lifecycle (ADR-0007). | `App\Actions\Payment\PaymentOutcomeIntake` (`receive`, `refresh`, `check`) | per-provider validation copies |
| **Payment deadline** | The single instant (`payments.expired_at`) after which a pending payment may be reclaimed; slot hold, sweep, links and countdowns derive from it, plus a settlement margin (ADR-0009). | `payments.expired_at`; `PaymentRules::deadlineFor`, `isReclaimable`; `config/payment.php` → `deadline` | separate hold or link timers |
| **Checkout draft** | The server-side, tenant-bound state of an unfinished storefront booking; holds no authority and reserves nothing (ADR-0008). | not implemented: the session array `booking` in `Customer/BookingController` plus the checkout fingerprint of `StorefrontCheckoutState` (BQ-083); typed draft is BQ-029 | treating the draft as authorization |
| **Module registry** | `config/modules.php`: release state (`released`, `locked`, `hidden`) of each staged module, enforced by `module:<name>` middleware (ADR-0011). | `EnsureModuleReleased` (alias `module`), `MODULE_*` env overrides | per-view tier checks |
| **Plan** | A subscription package definition. | `plans` table; `namapaket` (`small`, `medium`, `pro`), `hargabulanan`, `maxlayanan`, `maxbooking`, `isunlimited` | Package (when ambiguous) |
| **Subscription** | A tenant's relationship to a plan and its state. | `subscriptions` table; `trial_berakhir`, `langganan_mulai`, `langganan_berakhir`; view variable `$langganan` | Membership |
| **Entitlement** | What a subscription allows (features, limits). | `EntitlementRules`, `PlanCapability`; middleware `subscription:<feature>` | ad-hoc plan-name checks |
| **Unit** | A court, room or tool used by one or more services; never booked twice at the same time (D-012, FR-UNIT). Pro. | `resources`, `service_resource` (to be renamed); `/owner/staff-resources` today | Resource (in new code), Asset, Room |
| **Staff** (legacy) | Former "service provider" records; withdrawn (FR-STAFF-001). | `staff`, `service_staff`; archived | — |
| **Additional Item** | Optional extra sold with a service; UI label "Add-on". Counted in the payment amount (D-015). | `additional_items`, `additional_item_service` | Add-on domain |
| **Voucher** | Promotional discount code; UI label "Promo". Counted in the payment amount (D-015). | `vouchers` | Coupon domain |
| **Media gallery** | All images of one tenant, used by every image field (FR-ASSET-005). | `assets` table, `/owner/settings/assets` today | per-page image stores |
| **Payout account** | One of up to three saved bank accounts a tenant withdraws to; one is primary (D-004). | `tenant_payout_accounts`; `owner_payouts.payout_account_id` | — |
| **Review** | Customer feedback on an eligible booking. | `reviews`; `rating`, `komentar` (text), `balasan` (owner reply), `dibalas_pada`, `is_hidden` | Rating domain |
| **Platform balance** | Money the platform holds for a tenant; source of payouts. | `tenants.saldo_platform`; `owner_payouts` | — |
| **Public business page** | Tenant storefront at `/{slug}` or a custom domain. | `slug_usaha`, `custom_domain`, `customer.booking.*` routes | "subdomain" for `/{slug}` |
| **Access grant** | Revocable authorization for one booking or payment group and an explicit set of customer operations (profile). | `customer_access_grants`; `IssueCustomerAccess`, `ResolveCustomerAccess`, `CustomerAccessSession` (ADR-0006) | a customer account or wildcard permission |
| **Access credential** | Secret proving possession of an access grant; stored only as a hash; distinct from a public booking or payment reference. | `/access#<credential>` fragment exchanged by `POST /access` | treating an order ID as a secret |
| **Access link** / **Management link** | Customer link carrying access to the named management operations for one reservation, without an account. | Management profile; `/manage/...` routes. Legacy `payments.manage_token`, `bookings.cancellation_token`, `bookings.reschedule_token` remain in the schema and never authorize | "token link"; a read-only link that implicitly gains other rights |
| **Checkout link** | Customer access to one booking payment and its invoice, without booking cancellation or reschedule authority. | Checkout profile, issued at checkout | an owner or subscription checkout credential |

## Team and process terms

| Term | Meaning |
|---|---|
| **Product Lead** / **Tech Lead** / **Developer** | Decision roles; holders listed in `AGENTS.md` → "Roles" |
| **Integration branch** | `integration`: where finished work is combined by PR before release (`docs/workflow.md` §6). Older records call it `agathan`. |
| **Light lane** / **Heavy lane** | How much process a task needs: light (UI, copy, tests, docs) or heavy (domain rules, schema, security, payment, product) — `docs/workflow.md` §3 |
| **Product decision** (`D-NNN`) | A question for the Product Lead with a proposed answer, kept in `docs/product-decisions.md`; work items follow only after approval — `docs/workflow.md` §5 |

## State values

| Field | Values | Notes |
|---|---|---|
| `bookings.status` | `pending`, `paid`, `cancelled`, `completed` | Constants in `App\Domain\Booking\BookingState`. `no_show` is added by D-013 (FR-BOOKING-020); not in code yet. |
| `payments.status` | `pending`, `sukses` (success), `gagal` (failed) | Expiry is not a status; an expired payment becomes `gagal`. Constants in `PaymentState`. |
| `refunds.status` | `pending`, `processed`, `failed` | |
| `subscriptions.status` | `trial`, `active`, `expired`, `cancelled` | Constants in `SubscriptionState`. |
| `schedules.status` | `tersedia` (open), `tidak_tersedia` (closed) | DB enum. `AvailabilityRules` also checks `diblokir`, which the enum cannot store. |
| `owner_payouts.status` | `pending`, `approved`, `success`, `failed`, `rejected` | Transitions in `App\Domain\Payout\PayoutState`: pending → approved/rejected, approved → success/failed. |
| `users.role` | `owner`, `admin` | Migrations also allow `superadmin`/`customer`; neither is used. Team roles (Manajer, Kasir) are added by D-011; their storage is decided in ADR-0016. |
| `tenants.payment_mode` | `platform` (default), `owner` | `owner` = tenant's own Midtrans keys; withdrawn by D-016 together with the stored `midtrans_*` credentials. |

## Owner menu names

The owner portal redesign (`owner-portal.md` §10) renames menus. Old names still appear in code and older documents.

| Old name | New menu |
|---|---|
| Dashboard | Beranda |
| Calendar | Kalender |
| Bookings | Booking |
| Customers | Pelanggan |
| Services, Programs, Categories, Additional Items | Layanan (tabs Layanan, Kategori, Add-on) |
| Staff & Resources | Unit (resources); Pengaturan → Tim (team members) |
| Schedule | Jam Buka & Harga |
| Appearance, Assets, Landing Page | Halaman Booking |
| Vouchers | Promo |
| Reviews | Ulasan |
| Saldo & Withdraw, Payments | Pembayaran |
| Analytics, Schedule Report | Laporan |
| Settings, Integrations | Pengaturan |
| Subscription | Paket & Tagihan |
| Notifications | Bell (Notifikasi) |

## Indonesian words you will meet

| Word | Meaning | Word | Meaning |
|---|---|---|---|
| layanan | service | jadwal | schedule |
| tanggal | date | jam | time / hour |
| harga | price | durasi | duration |
| pelanggan | customer | pemilik | owner |
| bisnis / usaha | business (tenant) | paket | plan |
| langganan | subscription | pembayaran | payment |
| sukses / gagal | success / failed | tersedia | available |
| catatan | note | komentar | comment |
| balasan | reply | saldo | balance |
| pesan (flash) | message | sukses (flash) | success message |
