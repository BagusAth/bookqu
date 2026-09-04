<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingManageController;
use App\Http\Controllers\DummyRegistrationController;
use App\Http\Controllers\OwnerAnalyticsController;
use App\Http\Controllers\OwnerBookingController;
use App\Http\Controllers\OwnerCheckoutController;
use App\Http\Controllers\OwnerDashboardController;
use App\Http\Controllers\OwnerLandingPageController;
use App\Http\Controllers\OwnerPortalController;
use App\Http\Controllers\OwnerAdditionalItemController;
use App\Http\Controllers\OwnerAssetController;
use App\Http\Controllers\OwnerCategoryController;
use App\Http\Controllers\OwnerProgramController;
use App\Http\Controllers\OwnerReviewController;
use App\Http\Controllers\OwnerScheduleController;
use App\Http\Controllers\OwnerSettingController;
use App\Http\Controllers\OwnerStaffResourceController;
use App\Http\Controllers\OwnerSubscriptionController;
use App\Http\Controllers\OwnerVoucherController;
use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Password;

Route::get('/', function () {
    return view('welcome');
});

// ── Authentication Routes ──
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');
    
    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email atau password salah.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $request->session()->forget('current_tenant_id');

        $user = $request->user();

        if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if ($user?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user?->isOwner()) {
            return redirect()->route('owner.dashboard');
        }

        return redirect('/');
    })->name('login.store');
    
    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');
    
    Route::post('/register', function (Request $request) {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'max:100', 'unique:users,email'],
            'nomorhp'      => ['required', 'string', 'max:20'],
            'nama_bisnis'  => ['required', 'string', 'max:150'],
            'jenis_bisnis' => ['required', 'string', 'max:150'],
            'alamat'       => ['required', 'string', 'max:255'],
            'password'     => ['required', 'confirmed', Password::min(8)],
            'terms'        => ['accepted'],
        ]);

        $user = User::create([
            'namalengkap' => $validated['name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'nomorhp'     => $validated['nomorhp'],
            'role'        => 'owner',
        ]);

        // FS-002 & FS-003: Buat Tenant dengan auto-generate slug dari nama bisnis
        $slug     = \Illuminate\Support\Str::slug($validated['nama_bisnis']);
        $slugBase = $slug;
        $counter  = 1;
        while (\App\Models\Tenant::where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . $counter++;
        }

        $tenant = \App\Models\Tenant::create([
            'iduser'      => $user->id,
            'namabisnis'  => $validated['nama_bisnis'],
            'jenisbisnis' => $validated['jenis_bisnis'],
            'alamat'      => $validated['alamat'],
            'nomorhp'     => $validated['nomorhp'],
            'slug'        => $slug,
        ]);

        // FS-018: Buat Subscription trial 7 hari setara paket Pro
        $proPlan = \App\Models\Plan::firstOrCreate(
            ['namapaket' => 'pro'],
            [
                'hargabulanan' => 100000,
                'maxlayanan'   => 10,
                'maxbooking'   => 500,
                'isunlimited'  => false,
            ]
        );

        \App\Models\Subscription::create([
            'idtenant'      => $tenant->id,
            'idplan'        => $proPlan->id,
            'status'        => 'trial',
            'trial_berakhir' => now()->addDays(7),
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('current_tenant_id', $tenant->id);
        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    })->name('register.store');

});

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    Auth::logout();

    return redirect()->route('login')->with('status', 'Akun berhasil diaktivasi. Silakan login.');
})->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('status', 'Link verifikasi baru sudah dikirim ke email Anda.');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::middleware('auth')->group(function () {
    Route::post('/logout', function () {
        Auth::logout();
        return redirect('/');
    })->name('logout');
});

// Dummy registration module (isolated for slug testing, only in local)
if (app()->environment('local', 'staging', 'testing')) {
    Route::get('/dummy-register', [DummyRegistrationController::class, 'showForm'])->name('dummy-register.form');
    Route::post('/dummy-register', [DummyRegistrationController::class, 'processForm'])->name('dummy-register.process');
}

// ── Owner Dashboard Routes ──
Route::prefix('owner')
    ->middleware(['auth', 'verified', 'role:owner', 'tenant'])
    ->group(function () {
    Route::get('/dashboard', [OwnerDashboardController::class, 'index'])->name('owner.dashboard');
    Route::get('/dashboard/polling', [OwnerDashboardController::class, 'pollingData'])->name('owner.dashboard.polling');
    Route::get('/settings', [OwnerSettingController::class, 'index'])->name('owner.settings');
    Route::post('/profile/complete', [OwnerSettingController::class, 'storeProfile'])->name('owner.profile.complete');
    Route::post('/settings/profile', [OwnerSettingController::class, 'updateBusinessProfile'])->name('owner.settings.profile');
    Route::post('/settings/account', [OwnerSettingController::class, 'updateAccount'])->name('owner.settings.account');
    Route::post('/settings/payment', [OwnerSettingController::class, 'updatePaymentSettings'])->name('owner.settings.payment');
    Route::post('/payouts', [OwnerSettingController::class, 'requestPayout'])->name('owner.payouts.request');

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
        Route::get('/analytics', [OwnerAnalyticsController::class, 'index'])->name('owner.analytics')->middleware('subscription:pro');
        Route::get('/analytics/export', [OwnerAnalyticsController::class, 'export'])->name('owner.analytics.export')->middleware('subscription:pro');
        Route::get('/subscription', [OwnerSubscriptionController::class, 'index'])->name('owner.subscription');
        Route::get('/landing-page', [OwnerLandingPageController::class, 'index'])->name('owner.landing-page')->middleware('subscription:pro');
        Route::post('/landing-page', [OwnerLandingPageController::class, 'store'])->name('owner.landing-page.store')->middleware('subscription:pro');

        // ── Extended Core Business Modules (Tahap 2) ──
        Route::get('/calendar', [OwnerPortalController::class, 'calendar'])->name('owner.calendar');
        Route::get('/schedule-report', [OwnerPortalController::class, 'scheduleReport'])->name('owner.schedule-report');

        // Services & Programs
        Route::get('/services', [OwnerProgramController::class, 'index'])->name('owner.services');
        Route::post('/services/{id}/toggle', [OwnerProgramController::class, 'toggleStatus'])->name('owner.services.toggle');
        Route::post('/programs/{id}/toggle', [OwnerProgramController::class, 'toggleStatus'])->name('owner.programs.toggle');

        // Categories
        Route::get('/categories', [OwnerCategoryController::class, 'index'])->name('owner.categories');
        Route::post('/categories', [OwnerCategoryController::class, 'store'])->name('owner.categories.store');
        Route::put('/categories/{id}', [OwnerCategoryController::class, 'update'])->name('owner.categories.update');
        Route::delete('/categories/{id}', [OwnerCategoryController::class, 'destroy'])->name('owner.categories.destroy');
        Route::post('/categories/{id}/toggle', [OwnerCategoryController::class, 'toggleStatus'])->name('owner.categories.toggle');

        // Staff & Resources
        Route::get('/staff-resources', [OwnerStaffResourceController::class, 'index'])->name('owner.staff-resources');
        Route::post('/staff', [OwnerStaffResourceController::class, 'storeStaff'])->name('owner.staff.store');
        Route::put('/staff/{id}', [OwnerStaffResourceController::class, 'updateStaff'])->name('owner.staff.update');
        Route::delete('/staff/{id}', [OwnerStaffResourceController::class, 'destroyStaff'])->name('owner.staff.destroy');
        Route::post('/staff/{id}/toggle', [OwnerStaffResourceController::class, 'toggleStaffStatus'])->name('owner.staff.toggle');
        Route::post('/resources', [OwnerStaffResourceController::class, 'storeResource'])->name('owner.resources.store');
        Route::put('/resources/{id}', [OwnerStaffResourceController::class, 'updateResource'])->name('owner.resources.update');
        Route::delete('/resources/{id}', [OwnerStaffResourceController::class, 'destroyResource'])->name('owner.resources.destroy');
        Route::post('/resources/{id}/toggle', [OwnerStaffResourceController::class, 'toggleResourceStatus'])->name('owner.resources.toggle');

        // Additional Items
        Route::get('/additional-items', [OwnerAdditionalItemController::class, 'index'])->name('owner.additional-items');
        Route::post('/additional-items', [OwnerAdditionalItemController::class, 'store'])->name('owner.additional-items.store');
        Route::put('/additional-items/{id}', [OwnerAdditionalItemController::class, 'update'])->name('owner.additional-items.update');
        Route::delete('/additional-items/{id}', [OwnerAdditionalItemController::class, 'destroy'])->name('owner.additional-items.destroy');
        Route::post('/additional-items/{id}/toggle', [OwnerAdditionalItemController::class, 'toggleStatus'])->name('owner.additional-items.toggle');

        // Vouchers
        Route::get('/vouchers', [OwnerVoucherController::class, 'index'])->name('owner.vouchers');
        Route::post('/vouchers', [OwnerVoucherController::class, 'store'])->name('owner.vouchers.store');
        Route::put('/vouchers/{id}', [OwnerVoucherController::class, 'update'])->name('owner.vouchers.update');
        Route::delete('/vouchers/{id}', [OwnerVoucherController::class, 'destroy'])->name('owner.vouchers.destroy');
        Route::post('/vouchers/{id}/toggle', [OwnerVoucherController::class, 'toggleStatus'])->name('owner.vouchers.toggle');

        // Reviews
        Route::get('/reviews', [OwnerReviewController::class, 'index'])->name('owner.reviews');
        Route::post('/reviews/{id}/reply', [OwnerReviewController::class, 'reply'])->name('owner.reviews.reply');
        Route::post('/reviews/{id}/toggle', [OwnerReviewController::class, 'toggleVisibility'])->name('owner.reviews.toggle');

        // Customers CRM
        Route::get('/customers', [OwnerPortalController::class, 'customers'])->name('owner.customers');
        Route::post('/customers/note', [OwnerPortalController::class, 'saveCustomerNote'])->name('owner.customers.note');

        // Settings & Configurations
        Route::get('/settings/business', [OwnerSettingController::class, 'index'])->name('owner.settings.business');
        Route::get('/settings/appearance', [OwnerPortalController::class, 'appearance'])->name('owner.settings.appearance');
        Route::post('/settings/appearance', [OwnerPortalController::class, 'updateAppearance'])->name('owner.settings.appearance.update');
        Route::get('/settings/payment-setting', [OwnerPortalController::class, 'paymentSettings'])->name('owner.settings.payment-setting');
        Route::get('/settings/payments', [OwnerPortalController::class, 'paymentSettings'])->name('owner.settings.payments');
        Route::get('/settings/assets', [OwnerAssetController::class, 'index'])->name('owner.settings.assets');
        Route::post('/settings/assets', [OwnerAssetController::class, 'store'])->name('owner.settings.assets.store');
        Route::delete('/settings/assets/{id}', [OwnerAssetController::class, 'destroy'])->name('owner.settings.assets.destroy');
        Route::get('/settings/balance', [OwnerPortalController::class, 'balance'])->name('owner.settings.balance');
        Route::get('/settings/integrations', [OwnerPortalController::class, 'integrations'])->name('owner.settings.integrations');
    });
});

// ── Midtrans Webhook (tanpa auth & CSRF, dipanggil oleh Midtrans) ──
Route::post('/midtrans/webhook', [\App\Http\Controllers\MidtransWebhookController::class, 'handle'])
    ->name('midtrans.webhook');

// ── Booking Management Without Account (tokenized URLs) ──
Route::prefix('manage')->group(function () {
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
});

Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    });

if (app()->environment('local', 'staging', 'testing')) {
    Route::get('/test-isolasi/{slug}', function () {
        return "Route Test Isolasi Tenant";
    });
}

$customerRoutes = function () {
    Route::get('/', [BookingController::class, 'showProgramSelection'])
        ->name('customer.booking.program');

    Route::post('/booking/select-program', [BookingController::class, 'selectProgram'])
        ->name('customer.booking.select-program');

    Route::get('/booking/date', [BookingController::class, 'showDateSelection'])
        ->name('customer.booking.date');

    Route::post('/booking/select-date', [BookingController::class, 'selectDate'])
        ->name('customer.booking.select-date');

    Route::get('/booking/time', [BookingController::class, 'showTimeSelection'])
        ->name('customer.booking.time');

    Route::post('/booking/select-time', [BookingController::class, 'selectTime'])
        ->name('customer.booking.select-time');

    Route::get('/booking/checkout', [BookingController::class, 'showCheckout'])
        ->name('customer.booking.checkout');

    Route::post('/booking/checkout', [BookingController::class, 'processCheckout'])
        ->name('customer.booking.process-checkout');

    Route::get('/booking/payment/{payment:order_id}', [BookingController::class, 'showPayment'])
        ->name('customer.booking.payment');

    Route::post('/booking/payment/{payment:order_id}/check-status', [BookingController::class, 'checkPaymentStatus'])
        ->name('customer.booking.check-status');

    Route::post('/booking/payment/{payment:order_id}/callback', [BookingController::class, 'handleCallback'])
        ->name('customer.booking.callback');

    Route::get('/booking/payment/{payment:order_id}/invoice', [BookingController::class, 'showInvoice'])
        ->name('customer.booking.invoice');
};

// Custom domain routing
$host = request()->getHost();
if ($host !== '127.0.0.1' && $host !== 'localhost' && !str_contains($host, 'bookqu.test')) {
    Route::middleware('tenant')->group($customerRoutes);
}

// Subdirectory routing (default)
Route::prefix('{slug_usaha}')
    ->middleware('tenant')
    ->group($customerRoutes);
