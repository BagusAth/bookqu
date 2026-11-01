<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPayoutController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Customer\BookingController;
use App\Http\Controllers\Customer\BookingManageController;
use App\Http\Controllers\Customer\CustomerAccessController;
use App\Http\Controllers\Owner\OwnerAdditionalItemController;
use App\Http\Controllers\Owner\OwnerAnalyticsController;
use App\Http\Controllers\Owner\OwnerAppearanceController;
use App\Http\Controllers\Owner\OwnerAssetController;
use App\Http\Controllers\Owner\OwnerBalanceController;
use App\Http\Controllers\Owner\OwnerBookingController;
use App\Http\Controllers\Owner\OwnerCalendarController;
use App\Http\Controllers\Owner\OwnerCategoryController;
use App\Http\Controllers\Owner\OwnerCheckoutController;
use App\Http\Controllers\Owner\OwnerCustomerController;
use App\Http\Controllers\Owner\OwnerDashboardController;
use App\Http\Controllers\Owner\OwnerIntegrationController;
use App\Http\Controllers\Owner\OwnerLandingPageController;
use App\Http\Controllers\Owner\OwnerNotificationController;
use App\Http\Controllers\Owner\OwnerPaymentSettingsController;
use App\Http\Controllers\Owner\OwnerProgramController;
use App\Http\Controllers\Owner\OwnerReviewController;
use App\Http\Controllers\Owner\OwnerScheduleController;
use App\Http\Controllers\Owner\OwnerScheduleReportController;
use App\Http\Controllers\Owner\OwnerSettingController;
use App\Http\Controllers\Owner\OwnerStaffResourceController;
use App\Http\Controllers\Owner\OwnerSubscriptionController;
use App\Http\Controllers\Owner\OwnerVoucherController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Webhook\MidtransWebhookController;
use App\Http\Controllers\Webhook\SingaPayWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/kebijakan-privasi', function () {
    return view('privacy-policy');
})->name('privacy-policy');

Route::permanentRedirect('/privacy-policy', '/kebijakan-privasi');

Route::get('/syarat-ketentuan', function () {
    return view('terms-conditions');
})->name('terms-conditions');

Route::permanentRedirect('/terms-conditions', '/syarat-ketentuan');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// ── Authentication Routes ──
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1,auth-login:')->name('login.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1,auth-register:')->name('register.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:5,1,auth-password:')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:5,1,auth-password:')->name('password.store');
    Route::post('/reset-password-update', [NewPasswordController::class, 'store'])->middleware('throttle:5,1,auth-password:')->name('password.update');
});

Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
    ->middleware('auth')
    ->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// ── Owner Dashboard Routes ──
Route::prefix('owner')
    ->middleware(['auth', 'verified', 'role:owner', 'tenant'])
    ->group(function () {
        Route::get('/dashboard', [OwnerDashboardController::class, 'index'])->name('owner.dashboard');
        Route::get('/dashboard/polling', [OwnerDashboardController::class, 'pollingData'])->name('owner.dashboard.polling');
        Route::get('/notifications', [OwnerNotificationController::class, 'index'])->name('owner.notifications');
        Route::post('/notifications/read-all', [OwnerNotificationController::class, 'markAllAsRead'])->name('owner.notifications.read-all');
        Route::post('/notifications/{id}/read', [OwnerNotificationController::class, 'markAsRead'])->name('owner.notifications.read');
        Route::delete('/notifications/{id}', [OwnerNotificationController::class, 'destroy'])->name('owner.notifications.destroy');
        Route::get('/settings', [OwnerSettingController::class, 'index'])->name('owner.settings');
        Route::post('/profile/complete', [OwnerSettingController::class, 'storeProfile'])->name('owner.profile.complete');
        Route::post('/settings/profile', [OwnerSettingController::class, 'updateBusinessProfile'])->name('owner.settings.profile');
        Route::post('/settings/account', [OwnerSettingController::class, 'updateAccount'])->name('owner.settings.account');
        Route::delete('/settings/account', [OwnerSettingController::class, 'deleteAccount'])->name('owner.settings.account.delete');
        Route::post('/settings/payment', [OwnerSettingController::class, 'updatePaymentSettings'])->middleware('module:direct-payment')->name('owner.settings.payment');
        Route::post('/payouts', [OwnerSettingController::class, 'requestPayout'])->middleware('module:balance')->name('owner.payouts.request');

        // ── Checkout Routes (tanpa owner.profile middleware, karena ini billing) ──
        Route::get('/checkout/{plan}', [OwnerCheckoutController::class, 'showCheckout'])->name('owner.checkout');
        Route::post('/checkout', [OwnerCheckoutController::class, 'processCheckout'])->name('owner.checkout.process');
        Route::get('/checkout/{payment:order_id}/payment', [OwnerCheckoutController::class, 'showPayment'])->name('owner.checkout.payment');
        Route::post('/checkout/{payment:order_id}/check-status', [OwnerCheckoutController::class, 'checkPaymentStatus'])->name('owner.checkout.check-status');
        Route::post('/checkout/{payment:order_id}/callback', [OwnerCheckoutController::class, 'handleCallback'])->name('owner.checkout.callback');
        Route::get('/checkout/{payment:order_id}/invoice', [OwnerCheckoutController::class, 'showInvoice'])->name('owner.checkout.invoice');

        Route::middleware('owner.profile')->group(function () {
            Route::get('/programs', [OwnerProgramController::class, 'index'])->name('owner.programs');
            Route::post('/programs', [OwnerProgramController::class, 'store'])->name('owner.programs.store');
            Route::put('/programs/{program}', [OwnerProgramController::class, 'update'])->name('owner.programs.update');
            Route::delete('/programs/{program}', [OwnerProgramController::class, 'destroy'])->name('owner.programs.destroy');
            Route::get('/schedule', [OwnerScheduleController::class, 'index'])->name('owner.schedule');
            Route::post('/schedule/bulk-slots', [OwnerScheduleController::class, 'bulkStore'])->name('owner.schedule.bulk-store');
            Route::delete('/schedule/slots/{slot}', [OwnerScheduleController::class, 'destroy'])->name('owner.schedule.slots.destroy');
            Route::post('/schedule/default-pricing', [OwnerScheduleController::class, 'updateDefaultPricing'])->name('owner.schedule.default-pricing');
            Route::post('/schedule/availability', [OwnerScheduleController::class, 'updateAvailability'])->name('owner.schedule.availability');
            Route::delete('/schedule/blocked-dates/{blockedDate}', [OwnerScheduleController::class, 'deleteBlockedDate'])->name('owner.schedule.blocked-dates.delete');
            Route::get('/bookings', [OwnerBookingController::class, 'index'])->name('owner.bookings');
            Route::patch('/bookings/{booking}/status', [OwnerBookingController::class, 'updateStatus'])->name('owner.bookings.status');
            Route::post('/bookings/walkin', [OwnerBookingController::class, 'walkinStore'])->name('owner.bookings.walkin');
            Route::get('/bookings/{booking}/available-slots', [OwnerBookingController::class, 'getAvailableSlots'])->name('owner.bookings.available-slots');
            Route::post('/bookings/{booking}/reschedule', [OwnerBookingController::class, 'reschedule'])->name('owner.bookings.reschedule');
            Route::get('/analytics', [OwnerAnalyticsController::class, 'index'])->name('owner.analytics')->middleware(['module:analytics', 'subscription:medium']);
            Route::get('/analytics/export', [OwnerAnalyticsController::class, 'export'])->name('owner.analytics.export')->middleware(['module:analytics', 'subscription:medium']);
            Route::get('/subscription', [OwnerSubscriptionController::class, 'index'])->name('owner.subscription');
            Route::get('/landing-page', [OwnerLandingPageController::class, 'index'])->name('owner.landing-page')->middleware(['module:landing-page', 'subscription:pro']);
            Route::post('/landing-page', [OwnerLandingPageController::class, 'store'])->name('owner.landing-page.store')->middleware(['module:landing-page', 'subscription:pro']);

            // ── Extended Core Business Modules (Tahap 2) ──
            Route::get('/calendar', [OwnerCalendarController::class, 'index'])->name('owner.calendar');
            Route::get('/schedule-report', [OwnerScheduleReportController::class, 'index'])->middleware('module:schedule-report')->name('owner.schedule-report');
            Route::get('/schedule-report/export', [OwnerScheduleReportController::class, 'export'])->middleware('module:schedule-report')->name('owner.schedule-report.export');

            // Services & Programs
            Route::get('/services', [OwnerProgramController::class, 'index'])->name('owner.services');
            Route::post('/services', [OwnerProgramController::class, 'store'])->name('owner.services.store');
            Route::put('/services/{program}', [OwnerProgramController::class, 'update'])->name('owner.services.update');
            Route::delete('/services/{program}', [OwnerProgramController::class, 'destroy'])->name('owner.services.destroy');
            Route::match(['post', 'patch'], '/services/{id}/toggle', [OwnerProgramController::class, 'toggleStatus'])->name('owner.services.toggle');
            Route::match(['post', 'patch'], '/programs/{id}/toggle', [OwnerProgramController::class, 'toggleStatus'])->name('owner.programs.toggle');

            // Categories
            Route::get('/categories', [OwnerCategoryController::class, 'index'])->name('owner.categories');
            Route::post('/categories', [OwnerCategoryController::class, 'store'])->name('owner.categories.store');
            Route::put('/categories/{id}', [OwnerCategoryController::class, 'update'])->name('owner.categories.update');
            Route::delete('/categories/{id}', [OwnerCategoryController::class, 'destroy'])->name('owner.categories.destroy');
            Route::match(['post', 'patch'], '/categories/{id}/toggle', [OwnerCategoryController::class, 'toggleStatus'])->name('owner.categories.toggle');

            // Staff & Resources
            Route::get('/staff-resources', [OwnerStaffResourceController::class, 'index'])->middleware('module:staff-resources')->name('owner.staff-resources');
            Route::post('/staff', [OwnerStaffResourceController::class, 'storeStaff'])->middleware('module:staff-resources')->name('owner.staff.store');
            Route::put('/staff/{id}', [OwnerStaffResourceController::class, 'updateStaff'])->middleware('module:staff-resources')->name('owner.staff.update');
            Route::delete('/staff/{id}', [OwnerStaffResourceController::class, 'destroyStaff'])->middleware('module:staff-resources')->name('owner.staff.destroy');
            Route::match(['post', 'patch'], '/staff/{id}/toggle', [OwnerStaffResourceController::class, 'toggleStaffStatus'])->middleware('module:staff-resources')->name('owner.staff.toggle');
            Route::post('/resources', [OwnerStaffResourceController::class, 'storeResource'])->middleware('module:staff-resources')->name('owner.resources.store');
            Route::put('/resources/{id}', [OwnerStaffResourceController::class, 'updateResource'])->middleware('module:staff-resources')->name('owner.resources.update');
            Route::delete('/resources/{id}', [OwnerStaffResourceController::class, 'destroyResource'])->middleware('module:staff-resources')->name('owner.resources.destroy');
            Route::match(['post', 'patch'], '/resources/{id}/toggle', [OwnerStaffResourceController::class, 'toggleResourceStatus'])->middleware('module:staff-resources')->name('owner.resources.toggle');

            // Additional Items
            Route::get('/additional-items', [OwnerAdditionalItemController::class, 'index'])->middleware('module:additional-items')->name('owner.additional-items');
            Route::post('/additional-items', [OwnerAdditionalItemController::class, 'store'])->middleware('module:additional-items')->name('owner.additional-items.store');
            Route::put('/additional-items/{id}', [OwnerAdditionalItemController::class, 'update'])->middleware('module:additional-items')->name('owner.additional-items.update');
            Route::delete('/additional-items/{id}', [OwnerAdditionalItemController::class, 'destroy'])->middleware('module:additional-items')->name('owner.additional-items.destroy');
            Route::match(['post', 'patch'], '/additional-items/{id}/toggle', [OwnerAdditionalItemController::class, 'toggleStatus'])->middleware('module:additional-items')->name('owner.additional-items.toggle');

            // Vouchers
            Route::get('/vouchers', [OwnerVoucherController::class, 'index'])->middleware('module:vouchers')->name('owner.vouchers');
            Route::post('/vouchers', [OwnerVoucherController::class, 'store'])->middleware('module:vouchers')->name('owner.vouchers.store');
            Route::put('/vouchers/{id}', [OwnerVoucherController::class, 'update'])->middleware('module:vouchers')->name('owner.vouchers.update');
            Route::delete('/vouchers/{id}', [OwnerVoucherController::class, 'destroy'])->middleware('module:vouchers')->name('owner.vouchers.destroy');
            Route::match(['post', 'patch'], '/vouchers/{id}/toggle', [OwnerVoucherController::class, 'toggleStatus'])->middleware('module:vouchers')->name('owner.vouchers.toggle');

            // Reviews
            Route::get('/reviews', [OwnerReviewController::class, 'index'])->name('owner.reviews');
            Route::post('/reviews/{id}/reply', [OwnerReviewController::class, 'reply'])->name('owner.reviews.reply');
            Route::match(['post', 'patch'], '/reviews/{id}/toggle', [OwnerReviewController::class, 'toggleVisibility'])->name('owner.reviews.toggle');

            // Customers CRM
            Route::get('/customers', [OwnerCustomerController::class, 'index'])->name('owner.customers');
            Route::get('/customers/detail', [OwnerCustomerController::class, 'show'])->name('owner.customers.detail');
            Route::post('/customers/note', [OwnerCustomerController::class, 'saveNote'])->name('owner.customers.note');

            // Settings & Configurations
            Route::get('/settings/business', [OwnerSettingController::class, 'index'])->name('owner.settings.business');
            Route::get('/settings/appearance', [OwnerAppearanceController::class, 'index'])->middleware('module:appearance')->name('owner.settings.appearance');
            Route::post('/settings/appearance', [OwnerAppearanceController::class, 'update'])->middleware('module:appearance')->name('owner.settings.appearance.update');
            Route::get('/settings/payment-setting', [OwnerPaymentSettingsController::class, 'index'])->middleware('module:direct-payment')->name('owner.settings.payment-setting');
            Route::get('/settings/payments', [OwnerPaymentSettingsController::class, 'index'])->middleware('module:direct-payment')->name('owner.settings.payments');
            Route::get('/settings/assets', [OwnerAssetController::class, 'index'])->middleware('module:assets')->name('owner.settings.assets');
            Route::post('/settings/assets', [OwnerAssetController::class, 'store'])->middleware('module:assets')->name('owner.settings.assets.store');
            Route::delete('/settings/assets/{id}', [OwnerAssetController::class, 'destroy'])->middleware('module:assets')->name('owner.settings.assets.destroy');
            Route::get('/settings/balance', [OwnerBalanceController::class, 'index'])->middleware('module:balance')->name('owner.settings.balance');
            Route::get('/payouts/{payout}/proof', [OwnerBalanceController::class, 'proof'])->whereNumber('payout')->middleware('module:balance')->name('owner.payouts.proof');
            Route::get('/settings/integrations', [OwnerIntegrationController::class, 'index'])->middleware('module:integrations')->name('owner.settings.integrations');
        });
    });

// ── Midtrans Webhook (tanpa auth & CSRF, dipanggil oleh Midtrans) ──
Route::post('/midtrans/webhook', [MidtransWebhookController::class, 'handle'])
    ->name('midtrans.webhook');

// ── SingaPay Webhook (tanpa auth & CSRF, dipanggil oleh SingaPay) ──
Route::post('/api/webhooks/singapay/transaction', [SingaPayWebhookController::class, 'handle'])
    ->name('singapay.webhook.transaction');

Route::get('/access', [CustomerAccessController::class, 'show'])
    ->middleware('throttle:30,1,customer-status:')->name('customer.access.show');
Route::post('/access', [CustomerAccessController::class, 'store'])
    ->middleware('throttle:15,1,customer-exchange:')->name('customer.access.store');

// Credential-free adapters; legacy token parameters never authorize access.
Route::prefix('manage')->middleware(['throttle:60,1,customer-private:', 'customer.access:manage', 'tenant'])->group(function () {
    // Payment Group Management (New Primary Customer Management)
    Route::get('/payment/{order_id}', [BookingManageController::class, 'showPaymentGroup'])
        ->name('booking.manage.payment');
    Route::get('/payment/{order_id}/invoice', [BookingManageController::class, 'invoicePaymentGroup'])
        ->name('booking.manage.payment.invoice');

    // Legacy Individual Booking Management (Backward Compatibility)
    Route::get('/{booking_code}', [BookingManageController::class, 'show'])
        ->name('booking.manage');
    Route::post('/{booking_code}/cancel', [BookingManageController::class, 'cancel'])
        ->name('booking.manage.cancel');
    Route::get('/{booking_code}/reschedule', [BookingManageController::class, 'showReschedule'])
        ->name('booking.manage.reschedule.show');
    Route::post('/{booking_code}/reschedule', [BookingManageController::class, 'reschedule'])
        ->name('booking.manage.reschedule.store');
    Route::get('/{booking_code}/reschedule/slots', [BookingManageController::class, 'getTimeSlots'])
        ->name('booking.manage.reschedule.slots');
    Route::get('/{booking_code}/invoice', [BookingManageController::class, 'invoice'])
        ->name('booking.manage.invoice');
    Route::post('/{booking_code}/review', [BookingManageController::class, 'storeReview'])
        ->name('booking.manage.review');
});

Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/payouts', [AdminPayoutController::class, 'index'])->name('admin.payouts');
        Route::post('/payouts/{payout}/approve', [AdminPayoutController::class, 'approve'])->whereNumber('payout')->name('admin.payouts.approve');
        Route::post('/payouts/{payout}/complete', [AdminPayoutController::class, 'complete'])->whereNumber('payout')->name('admin.payouts.complete');
        Route::post('/payouts/{payout}/reject', [AdminPayoutController::class, 'reject'])->whereNumber('payout')->name('admin.payouts.reject');
        Route::post('/payouts/{payout}/fail', [AdminPayoutController::class, 'fail'])->whereNumber('payout')->name('admin.payouts.fail');
    });

// Shared booking sub-routes (no root GET /) — used by both custom-domain and slug groups.
$bookingSubRoutes = function (string $namePrefix) {
    Route::post('/booking/select-program', [BookingController::class, 'selectProgram'])
        ->name($namePrefix.'select-program');

    Route::get('/booking/date', [BookingController::class, 'showDateSelection'])
        ->name($namePrefix.'date');

    Route::post('/booking/select-date', [BookingController::class, 'selectDate'])
        ->name($namePrefix.'select-date');

    Route::get('/booking/time', [BookingController::class, 'showTimeSelection'])
        ->name($namePrefix.'time');

    Route::post('/booking/select-time', [BookingController::class, 'selectTime'])
        ->name($namePrefix.'select-time');

    Route::get('/booking/checkout', [BookingController::class, 'showCheckout'])
        ->name($namePrefix.'checkout');

    Route::post('/booking/checkout', [BookingController::class, 'processCheckout'])
        ->middleware('throttle:10,1,storefront-checkout:')
        ->name($namePrefix.'process-checkout');

    Route::post('/booking/validate-voucher', [BookingController::class, 'validateVoucher'])
        ->middleware('module:vouchers')
        ->name($namePrefix.'validate-voucher');

    Route::get('/booking/payment/{payment:order_id}', [BookingController::class, 'showPayment'])
        ->middleware(['throttle:60,1,customer-private:', 'customer.access:payment'])
        ->name($namePrefix.'payment');

    Route::post('/booking/payment/{payment:order_id}/check-status', [BookingController::class, 'checkPaymentStatus'])
        ->middleware(['throttle:30,1,customer-status:', 'customer.access:payment'])
        ->name($namePrefix.'check-status');

    Route::post('/booking/payment/{payment:order_id}/callback', [BookingController::class, 'handleCallback'])
        ->middleware(['throttle:30,1,customer-status:', 'customer.access:payment'])
        ->name($namePrefix.'callback');

    Route::post('/booking/payment/{payment:order_id}/cancel', [BookingController::class, 'cancelPayment'])
        ->middleware(['throttle:10,1,customer-mutation:', 'customer.access:cancel-payment'])
        ->name($namePrefix.'cancel');

    Route::get('/booking/payment/{payment:order_id}/invoice', [BookingController::class, 'showInvoice'])
        ->middleware(['throttle:60,1,customer-private:', 'customer.access:invoice'])
        ->name($namePrefix.'invoice');
};

// Custom domain routing:
// Route::domain('{custom_domain}') acts as a wildcard that matches any hostname that
// is NOT the main bookqu.my.id domain (excluded via the 'custom_domain' where constraint).
// TenantMiddleware resolves the tenant from $request->getHost() and injects slug_usaha
// into route parameters, so the controller still receives $slug_usaha correctly.
$mainDomain = explode(':', parse_url(config('app.url'), PHP_URL_HOST) ?? 'bookqu.my.id')[0];
$excludedDomains = array_unique(array_filter([$mainDomain, 'localhost', '127.0.0.1', 'bookqu.my.id', 'bookqu.test']));
$customDomainPattern = '^(?!('.implode('|', array_map(fn ($d) => preg_quote($d, '/'), $excludedDomains)).')$).*';

Route::domain('{custom_domain}')
    ->where(['custom_domain' => $customDomainPattern])
    ->middleware(['tenant', 'no-store'])
    ->group(function () use ($bookingSubRoutes) {
        Route::get('/', [BookingController::class, 'showProgramSelection'])
            ->name('customer.booking.program');
        $bookingSubRoutes('customer.booking.');
    });

// Subdirectory (slug-based) routing:
// GET /{slug_usaha} → showProgramSelection($slug_usaha)
// All /{slug_usaha}/booking/... routes follow.
// bookqu.my.id/ is NOT included here — it stays as the welcome page (defined above at line 33).
Route::prefix('{slug_usaha}')
    ->where(['slug_usaha' => '^(?!(sitemap\.xml|kebijakan-privasi|privacy-policy|syarat-ketentuan|terms-conditions)$)[^/]+$'])
    ->middleware(['tenant', 'no-store'])
    ->group(function () use ($bookingSubRoutes) {
        Route::get('/', [BookingController::class, 'showProgramSelection'])
            ->name('customer.booking.slug.program');
        $bookingSubRoutes('customer.booking.slug.');
    });
