# Data inventory: owner portal

What data BookQu stores and what each owner page shows today. Facts only, checked against the code on 16 Nov 2026 (branch `agathan-bq-161-owner-ui-redesign`). Use it to judge whether a proposal can use existing data or needs new data.

## 1. Stored data that statistics can use

| Table | Useful columns | Notes |
|---|---|---|
| `bookings` | `idlayanan`, `idschedule`, `tanggalbooking`, `jam`, `peserta`, `status`, `namapelanggan`, `nomorhp`, `email`, `catatan`, `rescheduled_from_*`, `created_at` | Status: `pending`, `paid`, `cancelled`, `completed`, `no_show`. `created_at` minus `tanggalbooking` gives the lead time. |
| `booking_seats` | `idbooking`, `idschedule`, `seat_no`, `status` | Seats per schedule (capacity use). |
| `booking_logs` | `booking_id`, `event`, `description`, `metadata`, `created_at` | History of each booking (status changes, reschedules). |
| `payments` | `idbooking`, `tipe`, `jumlah`, `status` (`pending`/`sukses`/`gagal`), `metode`, `provider`, `price_breakdown`, `created_at` | `provider = 'manual'` means walk-in or cash. Any other provider means online. |
| `refunds` | `booking_id`, `payment_id`, `jumlah`, `status`, `cause`, `created_at` | |
| `schedules` | `idlayanan`, `tanggal`, `jam_mulai`, `jam_selesai`, `harga_override`, `status` | The open slots. |
| `services` | `idcategory`, `namalayanan`, `harga`, `durasi`, `kapasitas`, `is_active`, `is_popular` | |
| `units` | `name`, `type`, `kapasitas`, `is_active` | Linked to services. Unit use is computed by `UnitUtilisation`. |
| `vouchers` | `code`, `discount_type`, `discount_value`, `usage_limit`, `used_count`, `start_date`, `end_date` | |
| `reviews` | `idbooking`, `rating`, `komentar`, `balasan`, `dibalas_pada`, `is_hidden` | |
| `customer_notes` | `customer_identifier`, `notes` | |
| `opening_hours`, `peak_hour_rules`, `special_date_prices`, `owner_blocked_dates` | Opening hours and pricing rules | |
| `owner_payouts` | `jumlah`, `status`, dates | Payouts of the balance. |
| `usage_logs` | `jenis`, `jumlah`, `periode` | Plan quota usage. |
| `tenants` | `jenisbisnis`, policy fields (`cancel_before_hours`, `booking_min_lead_hours`, `slot_open_days_ahead`, …), `booking_link_shared_at` | |

## 2. Data that is not stored

- **No customer table.** A customer is identified by phone or email on bookings (`customer_identifier`). Birthdays, tags and marketing consent do not exist.
- **No traffic data.** Visits to the booking page and drop-off before payment are not recorded, so conversion cannot be computed.
- **No acquisition channel.** Bookings record no source (Instagram, WhatsApp, Maps). Only online vs walk-in can be told apart, from `payments.provider`.
- **No costs.** Profit or margin per service cannot be computed. Only revenue is known.
- **No staff on bookings.** Bookings are linked to units, not to the person who serves the customer.
- **No owner targets** (for example a monthly revenue goal).

## 3. What each page shows today

| Page (screenshots) | Data source | Content |
|---|---|---|
| Beranda (01–03) | `App\Actions\Dashboard\GetBeranda` | Banners (subscription ended, trial ending, services without slots, missing payout account, booking quota). **Setup guide** with 4 steps, shown until done. After the guide: four figures (bookings today, waiting for payment, revenue today if allowed, slots filled today), "Perlu tindakan" (open refunds, cancellation requests from Kasir, finished bookings not yet marked), today's timeline, tomorrow, next booking. Booking link card and quick actions. |
| Kalender (04–07) | `App\Actions\Calendar\*` | Day, week and month views of slots and bookings. |
| Booking + panel (08–11) | `OwnerBookingController`, `GetOwnerBookingPanel` | Tabs: Perlu tindakan, Mendatang, Menunggu bayar, Selesai, Dibatalkan, Semua. Filters: search, date range, service, source, sort. Panel: status, customer, payment, notes, history, actions. |
| Pelanggan (12–13) | `App\Actions\Customer\ListCustomers` | Total, new and returning customers. List sorted by last booking or most bookings. The customer panel shows bookings, total paid (`SumCustomerPayments`) and a note. |
| Layanan (14–16) | Service, Category and Add-on Actions | Services, categories, add-ons (`additional_items`). |
| Unit (17) | Unit Actions | Units, their services, today's use. |
| Jam Buka & Harga (18–21) | Schedule and PriceRule Actions | Opening hours, prices (peak hours, special dates, weekend), closed dates, slots and runway. |
| Promo (22), Ulasan (23) | Voucher and Review Actions | Vouchers with usage. Reviews with reply and hide. |
| Pembayaran (24–28) | `App\Actions\Owner\GetPaymentsPage` | Balance, transactions, balance and payouts, bank account, payment settings. |
| Laporan (29–34) | `App\Actions\Report\GetReport` | See section 4. |
| Halaman Booking (35–40) | `OwnerBookingPageController` | Link and QR, promotion material, appearance, media gallery, landing page, custom domain. |
| Paket & Tagihan (41) | `GetSubscriptionOverview` | Current plan, quota, plans ordered by price. |
| Pengaturan (42–47) | `GetOwnerSettings` | Business profile, booking policy, notifications, account, team, integrations. |
| Notifikasi (48), Bantuan (49) | | |

## 4. Laporan today (`GetReport`)

Period: this month by default, or a custom range of up to 366 days. CSV export on every tab.

| Tab | Figures |
|---|---|
| Ringkasan | Net revenue; bookings (multi-slot counted once); new customers; cancellation, completion and no-show rate (no-show only once that module is released); average rating and review count; top service; most used unit |
| Pendapatan | Gross, online, walk-in, refunds, net; net per day (or per month for long ranges) as bars; per service gross, refund and net |
| Okupansi | Slots total, booked and free; seats vs capacity rate; heatmap by weekday × hour; use per unit |
| Layanan | Bookings and revenue per service |
| Pelanggan | Active, new and returning customers; customer list with bookings in the period; promo use per customer and code (when the Promo module is in the plan) |

There is **no comparison with the previous period** and **no trend chart** beyond the daily bars. These were skipped in BQ-161 because `GetReport` does not prepare that data.

## 5. Roles (`App\Domain\Team\TenantPermission`)

| Role | Can do |
|---|---|
| Kasir | Beranda, Kalender, Booking (view, walk-in, complete, no-show, reschedule, **request** a cancellation), Pelanggan, own account, Notifikasi, Bantuan. No revenue figures, Laporan, settings or catalogue. |
| Manajer | Kasir, plus: cancel, refund decisions, Layanan, Jam Buka & Harga, Halaman Booking, Promo, Ulasan, Laporan, Pengaturan, Integrasi, revenue, Unit |
| Pemilik | Manajer, plus: payouts (Pembayaran), Paket & Tagihan, Tim, delete business, settle payments |

The sidebar shows only the menus a role may open. There are no Kasir screenshots, because no Kasir account exists in the local database. A Kasir sees the same pages as above, with fewer menus and no revenue.

## 6. Limits of the screenshots

- **Demo data.** Tenant "Brama Studio Creative" (seed data): 271 bookings created between June and 8 Nov 2026. The screenshots were taken on 16 Nov 2026, so "today" has few or no bookings. Customer names and numbers are fake.
- **Beranda shows the setup guide, not the daily view.** Step 3 (open slots for the coming days) is not done, so the four figures, "Perlu tindakan" and today's timeline are hidden. Their content is described in section 3.
- Full pages are cut at 3,000 px. `shots.json` lists every image with its URL, width and page height.
- Width 375 px (phone) exists only for Beranda, Kalender, Booking, the booking panel, Pelanggan and Laporan. Other pages are at 1366 px only.
