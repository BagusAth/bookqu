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
| **Service** | Something a customer can book (what). | `services` table; `idlayanan` FK; `namalayanan`, `harga`, `durasi`, `kapasitas`, `satuan_harga`, `satuan_durasi`; `Booking::layanan()`; routes/controller `OwnerProgramController`, `/owner/programs`, `select-program`, `program-selection` | Program, Layanan (as a concept), Bookable Program |
| **Category** | Grouping of services. | `categories`, `services.idcategory` | — |
| **Schedule** | One bookable time slot of a service (when). | `schedules` table; `idschedule`; `tanggal`, `jam_mulai`, `jam_selesai`, `harga_override`; "slot" in UI and routes | Program slot |
| **Availability** | Whether a schedule can be booked now. Derived, never stored. | `AvailabilityRules` statuses `AVAILABLE`, `BOOKED`, `BLOCKED`, `UNAVAILABLE` | a stored "availability" entity |
| **Blocked date** | A date the owner closed for booking. | `owner_blocked_dates.tanggal` | — |
| **Booking** | A reservation of one schedule. A multi-slot reservation is several bookings sharing one payment. | `bookings` table; `tanggalbooking`, `jam`, `catatan`, `booking_code` | Order, Reservation entity |
| **Walk-in booking** | Booking created by the owner for an on-site customer. | `CreateWalkInBooking`, route `owner.bookings.walkin` | a separate walk-in domain |
| **Payment group** | All bookings paid by one payment (multi-slot). | `payments.id` ↔ `bookings.idpayment`; `payments.idbooking` = first booking (legacy, deprecated) | — |
| **Payment** | A money transaction: booking payment or subscription payment. | `payments` table; `tipe` (`booking`, `subscription`), `jumlah`, `metode`, `provider`, `order_id`, `external_id` | generic Transaction |
| **Refund** | Money owed back after an eligible cancellation. | `refunds` table; `jumlah` | — |
| **Plan** | A subscription package definition. | `plans` table; `namapaket` (`small`, `medium`, `pro`), `hargabulanan`, `maxlayanan`, `maxbooking`, `isunlimited` | Package (when ambiguous) |
| **Subscription** | A tenant's relationship to a plan and its state. | `subscriptions` table; `trial_berakhir`, `langganan_mulai`, `langganan_berakhir`; view variable `$langganan` | Membership |
| **Entitlement** | What a subscription allows (features, limits). | `EntitlementRules`, `PlanCapability`; middleware `subscription:<feature>` | ad-hoc plan-name checks |
| **Staff** / **Resource** | People / physical assets linked to services (Tier 3, hidden). | `staff`, `resources`, `service_staff`, `service_resource` | — |
| **Additional Item** | Optional extra sold with a service (Tier 3, hidden). | `additional_items`, `additional_item_service` | Add-on domain |
| **Voucher** | Promotional discount (Tier 3, hidden; checkout endpoint returns 422). | `vouchers` | Coupon domain |
| **Review** | Customer feedback on an eligible booking. | `reviews`; `rating`, `komentar` (text), `balasan` (owner reply), `dibalas_pada`, `is_hidden` | Rating domain |
| **Platform balance** | Money the platform holds for a tenant; source of payouts. | `tenants.saldo_platform`; `owner_payouts` | — |
| **Public business page** | Tenant storefront at `/{slug}` or a custom domain. | `slug_usaha`, `custom_domain`, `customer.booking.*` routes | "subdomain" for `/{slug}` |
| **Management link** | Tokenized URL a customer uses to view/cancel/reschedule/review without an account. | `payments.manage_token`, `bookings.cancellation_token`, `bookings.reschedule_token`, `/manage/...` | — |

## State values

| Field | Values | Notes |
|---|---|---|
| `bookings.status` | `pending`, `paid`, `cancelled`, `completed` | Constants in `App\Domain\Booking\BookingState`. |
| `payments.status` | `pending`, `sukses` (success), `gagal` (failed) | Expiry is not a status; an expired payment becomes `gagal`. Constants in `PaymentState`. |
| `refunds.status` | `pending`, `processed`, `failed` | |
| `subscriptions.status` | `trial`, `active`, `expired`, `cancelled` | Constants in `SubscriptionState`. |
| `schedules.status` | `tersedia` (open), `tidak_tersedia` (closed) | DB enum. `AvailabilityRules` also checks `diblokir`, which the enum cannot store. |
| `owner_payouts.status` | `pending`, `success`, `rejected` | |
| `users.role` | `owner`, `admin` | Migrations also allow `superadmin`/`customer`; neither is used. |
| `tenants.payment_mode` | `platform` (default), `owner` | `owner` = tenant's own Midtrans keys (Tier 3, hidden). |

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
