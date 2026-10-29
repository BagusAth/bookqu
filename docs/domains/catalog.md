# Catalog (services & categories)

Owns what customers can book: services and their categories.

## Entry points

| Route | Controller |
|---|---|
| `owner.services*` and legacy aliases `owner.programs*` | `Owner/OwnerProgramController` |
| `owner.categories*` | `Owner/OwnerCategoryController` |
| Storefront `customer.booking.*program` | `Customer/BookingController::showProgramSelection`, `selectProgram` |

## Operations

`Actions/Service/CreateService` (checks `EntitlementRules::canCreateService`), `UpdateService`, `DeleteService` (refuses when pending/paid/completed bookings exist), `ToggleServiceStatus`.

## Data

`services`: `idtenant`, `namalayanan`, `harga`, `durasi`, `deskripsi`, `kapasitas`, `satuan_harga`, `satuan_durasi`, `idcategory`, `is_active`, `is_popular`, `image_url`. `categories`. Pivots to staff, resources, additional items (Tier 3).

`Service::hasActiveFulfillment()` — if staff/resources are linked, at least one must be active, otherwise the service is not bookable.

## Cache

`tenant:{id}:services:active`, `tenant:{id}:service:{id}` (`App\Services\Schedule\ScheduleAvailabilityCache`, used through `App\Traits\ClearsBookingCache`).

## Evidence

`tests/Feature/Actions/ServiceActionsTest.php`, `Owner/ProgramManagementTest.php`, `Owner/ServicesDomainEndToEndTest.php`, `Owner/OwnerModulesCrudTest.php`.

## Status

Functional: Implemented · Verification: Verified · Architecture: Target

## Known gaps

| Gap | Item |
|---|---|
| Legacy "program" names in routes, controller and views | BQ-035, BQ-036 |
| Audit P0: quote characters break `@click` on cards; staff/resource relations not detached | BQ-044, BQ-045 |
