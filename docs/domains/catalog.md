# Catalog (services & categories)

Owns what customers can book: services and their categories.

## Entry points

| Route | Controller |
|---|---|
| `owner.services*` (Layanan page, tabs Layanan and Kategori); `GET owner.programs` answers 301 to `owner.services` with the query kept, the `owner.programs.*` write aliases still work | `Owner/OwnerProgramController` |
| `owner.categories*` (`GET` renders the Layanan page on its Kategori tab) | `Owner/OwnerCategoryController` |
| Storefront `customer.booking.*program` | `Customer/BookingController::showProgramSelection`, `selectProgram` |

## Operations

`Actions/Service/CreateService` (checks `EntitlementRules::canCreateService`), `UpdateService`, `DeleteService` (refuses when the service has any booking, cancelled ones included: FR-SERVICE-006, AC-LYN-02), `ToggleServiceStatus`, `GetLayananPage` (page data and the usage meter from `EntitlementRules::canCreateService`).

`Actions/Category/CreateCategory`, `UpdateCategory`, `DeleteCategory` (refuses while any service uses the category), `ToggleCategoryStatus`. Category colours come from `App\Support\CategoryColor`.

## Data

`services`: `idtenant`, `namalayanan`, `harga`, `durasi`, `deskripsi`, `kapasitas`, `satuan_harga`, `satuan_durasi`, `idcategory`, `is_active`, `is_popular`, `image_url`. `categories`: `idtenant`, `name`, `slug`, `description`, `color`, `image_url` (public disk `categories/`), `sort_order` (storefront order, then name), `is_active`. Pivots to staff, resources, additional items (Tier 3).

`Service::hasActiveFulfillment()` — if staff/resources are linked, at least one must be active, otherwise the service is not bookable.

## Cache

`tenant:{id}:services:active`, `tenant:{id}:service:{id}` (`App\Services\Schedule\ScheduleAvailabilityCache`, used through `App\Traits\ClearsBookingCache`).

## Evidence

`tests/Feature/Actions/ServiceActionsTest.php`, `Owner/ProgramManagementTest.php`, `Owner/ServicesDomainEndToEndTest.php`, `Owner/OwnerModulesCrudTest.php`.

BQ-114: changing a service's `durasi` affects only slots created afterwards; existing slots and bookings keep their stored range (`Domain/Booking/BookingTimeRange`, evidence `Customer/BookingTimeRangeAfterDurationChangeTest`).

BQ-132: the Layanan page (`owner-portal.md` §8.6) replaces the programs and categories pages. Service form fields: name, category, base price and `satuan_harga`, duration and `satuan_durasi`, description, image, popular, active; unit links are sent back unchanged (edited on the Unit page, BQ-152). Evidence `Owner/LayananPageTest`.

## Status

Functional: Implemented · Verification: Verified · Architecture: Target

## Known gaps

| Gap | Item |
|---|---|
| Legacy "program" names in routes, controller and views | BQ-035, BQ-036 |
| Audit P0: quote characters break `@click` on cards; staff/resource relations not detached | BQ-044, BQ-045 |

BQ-150 backend: add-ons linked to the selected service can enter the booking quote when the module and plan allow it. A null stock in the existing owner form means unlimited. Finite stock accounts for pending payment reservations and is decremented on settlement.
