# Customers CRM & reviews

Owns the owner's view of customers (derived from bookings), internal customer notes, and customer reviews.

## Entry points

| Route | Code |
|---|---|
| `owner.customers` (Pelanggan page), `owner.customers.panel` (shared customer panel), `owner.customers.detail`, `owner.customers.note` | `Owner/OwnerCustomerController` → `Actions/Customer/ListCustomers`, `GetCustomerPanel`, `GetCustomerDetail`, `SaveCustomerNote` |
| `owner.reviews` (Ulasan page), `owner.reviews.reply`, `owner.reviews.toggle` | `Owner/OwnerReviewController` → `Actions/Review/GetReviewsPage`, `ReplyToReview`, `ToggleReviewVisibility` |
| `booking.manage.review` | `Customer/BookingManageController::storeReview` (Management access grant required) |

Customers are not stored as their own table: a customer is identified from booking contact fields within one tenant by `App\Domain\Customer\CustomerIdentity` (normalized email, else phone, else the booking). The same key is `customer_notes.customer_identifier` (AC-PLG-01).

Spending (rule 8, AC-PLG-02) is `Actions/Customer/SumCustomerPayments`: successful booking payments, each counted once, for bookings that are paid, completed or no-show. The list and the panel use it; a Kasir gets no spending figures or payment history (AC-PLG-03).

## Data

`customer_notes` (tenant-scoped). `reviews`: `idtenant`, `idbooking`, `rating`, `komentar`, `balasan`, `dibalas_pada`, `is_hidden`.

## Evidence

`tests/Feature/Owner/CustomerManagementTest.php`, `Owner/PelangganPageTest.php`, `Customer/BookingReviewTest.php`.

BQ-143: the Ulasan page (`owner-portal.md` §8.11). It shows:

- the average and distribution from one grouped query;
- tabs Belum dibalas (no reply, empty reply counted as none, hidden reviews included; AC-ULS-02), Semua and Disembunyikan;
- search;
- 15 reviews per page;
- a link from each review to its booking.

Replies hold up to 1000 characters, with three suggested sentences chosen by the rating (4–5 positive, 1–3 negative). The review is looked up before validation, so another business's review answers 404. The public booking page shows no reviews today, so a hidden review never appears there (AC-ULS-01). Evidence: `Owner/UlasanPageTest`.

## Status

Functional: Implemented · Verification: Verified · Architecture: Target

## Known gaps

| Gap | Item |
|---|---|
| Audit P1: `$dispatch` error when saving a CRM note; fetch errors close the drawer silently; no review module in the payment-group portal | BQ-066, BQ-065 |
