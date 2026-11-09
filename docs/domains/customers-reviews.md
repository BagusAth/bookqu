# Customers CRM & reviews

Owns the owner's view of customers (derived from bookings), internal customer notes, and customer reviews.

## Entry points

| Route | Code |
|---|---|
| `owner.customers` (Pelanggan page), `owner.customers.panel` (shared customer panel), `owner.customers.detail`, `owner.customers.note` | `Owner/OwnerCustomerController` → `Actions/Customer/ListCustomers`, `GetCustomerPanel`, `GetCustomerDetail`, `SaveCustomerNote` |
| `owner.reviews`, `owner.reviews.reply`, `owner.reviews.toggle` | `Owner/OwnerReviewController` |
| `booking.manage.review` | `Customer/BookingManageController::storeReview` (Management access grant required) |

Customers are not stored as their own table: a customer is identified from booking contact fields within one tenant by `App\Domain\Customer\CustomerIdentity` (normalized email, else phone, else the booking). The same key is `customer_notes.customer_identifier` (AC-PLG-01).

Spending (rule 8, AC-PLG-02) is `Actions/Customer/SumCustomerPayments`: successful booking payments, each counted once, for bookings that are paid, completed or no-show. The list and the panel use it; a Kasir gets no spending figures or payment history (AC-PLG-03).

## Data

`customer_notes` (tenant-scoped). `reviews`: `idtenant`, `idbooking`, `rating`, `komentar`, `balasan`, `dibalas_pada`, `is_hidden`.

## Evidence

`tests/Feature/Owner/CustomerManagementTest.php`, `Owner/PelangganPageTest.php`, `Customer/BookingReviewTest.php`.

## Status

Functional: Implemented · Verification: Verified · Architecture: Target

## Known gaps

| Gap | Item |
|---|---|
| Owner review page prints `$rev->review`, but the column is `komentar`, so review text is blank (`resources/views/owner/reviews.blade.php:137`) | BQ-059 |
| Audit P1: `$dispatch` error when saving a CRM note; fetch errors close the drawer silently; no review module in the payment-group portal | BQ-066, BQ-065 |
| Reviews unbounded memory load without pagination, unescaped search interpolation, and reply modal governance | BQ-143 |
