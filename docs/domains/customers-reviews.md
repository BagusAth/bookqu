# Customers CRM & reviews

Owns the owner's view of customers (derived from bookings), internal customer notes, and customer reviews.

## Entry points

| Route | Code |
|---|---|
| `owner.customers`, `owner.customers.detail`, `owner.customers.note` | `Owner/OwnerCustomerController` → `Actions/Customer/GetCustomerDetail`, `SaveCustomerNote` |
| `owner.reviews`, `owner.reviews.reply`, `owner.reviews.toggle` | `Owner/OwnerReviewController` |
| `booking.manage.review` | `Customer/BookingManageController::storeReview` (management token required) |

Customers are not stored as their own table: a customer is identified from booking contact fields (name, phone, email) within one tenant. Notes live in `customer_notes`.

## Data

`customer_notes` (tenant-scoped). `reviews`: `idtenant`, `idbooking`, `rating`, `komentar`, `balasan`, `dibalas_pada`, `is_hidden`.

## Evidence

`tests/Feature/Owner/CustomerManagementTest.php` (18 tests), `Customer/BookingReviewTest.php`.

## Status

Functional: Implemented · Verification: Verified · Architecture: Target

## Known gaps

| Gap | Item |
|---|---|
| Owner review page prints `$rev->review`, but the column is `komentar`, so review text is blank (`resources/views/owner/reviews.blade.php:137`) | BQ-059 |
| Audit P1: `$dispatch` error when saving a CRM note; fetch errors close the drawer silently; no review module in the payment-group portal | BQ-066, BQ-065 |
