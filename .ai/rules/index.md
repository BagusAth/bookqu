# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file.

| Applies to | Rule file |
| --- | --- |
| app/Actions/**, app/Domain/**, app/Notifications/**, app/Mail/**, app/Console/Commands/** | .ai/rules/actions-domain.md |
| app/Actions/Booking/**, app/Actions/Schedule/**, app/Actions/Calendar/**, app/Domain/Booking/**, app/Domain/Schedule/**, app/Http/Controllers/Customer/**, app/Http/Controllers/Owner/OwnerBookingController.php, app/Http/Controllers/Owner/OwnerScheduleController.php, app/Http/Controllers/Owner/OwnerCalendarController.php, app/Services/Schedule/** | .ai/rules/booking-availability.md |
| app/Models/**, database/**, app/Traits/ClearsBookingCache.php, app/Services/Schedule/** | .ai/rules/data.md |
| docs/**, AGENTS.md, .ai/rules/**, README.md | .ai/rules/documentation.md |
| resources/views/**, resources/js/**, resources/css/**, public/js/**, public/css/** | .ai/rules/frontend.md |
| app/Http/Controllers/**, app/Http/Requests/**, routes/** | .ai/rules/http-layer.md |
| app/Actions/Payment/**, app/Domain/Payment/**, app/Infrastructure/Payments/**, app/Contracts/Payments/**, app/Http/Controllers/Webhook/**, app/Http/Controllers/Owner/OwnerCheckoutController.php, app/Services/MidtransPaymentService.php, app/Console/Commands/ExpireBookingPayments.php, config/payment.php, config/midtrans.php | .ai/rules/payment.md |
| app/Domain/Subscription/**, app/Actions/Subscription/**, app/Http/Middleware/CheckSubscription.php, app/Http/Controllers/Owner/OwnerSubscriptionController.php, app/Console/Commands/CheckExpiredSubscriptions.php, app/Providers/AppServiceProvider.php | .ai/rules/subscription.md |
| app/Http/Middleware/**, app/Support/**, app/Traits/**, app/Models/Scopes/**, app/Http/Controllers/Owner/**, app/Http/Controllers/Auth/**, app/Http/Controllers/Customer/BookingManageController.php, app/Console/Commands/**, bootstrap/app.php, routes/** | .ai/rules/tenancy-security.md |
| tests/** | .ai/rules/tests.md |
