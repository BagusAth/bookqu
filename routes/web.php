<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\DummyRegistrationController;
use App\Http\Controllers\OwnerAnalyticsController;
use App\Http\Controllers\OwnerBookingController;
use App\Http\Controllers\OwnerCheckoutController;
use App\Http\Controllers\OwnerDashboardController;
use App\Http\Controllers\OwnerLandingPageController;
use App\Http\Controllers\OwnerProgramController;
use App\Http\Controllers\OwnerScheduleController;
use App\Http\Controllers\OwnerSettingController;
use App\Http\Controllers\OwnerSubscriptionController;
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
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'nomorhp' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'namalengkap' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'nomorhp' => $validated['nomorhp'],
            'role' => 'owner',
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('current_tenant_id');
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

// Dummy registration module (isolated for slug testing)
Route::get('/dummy-register', [DummyRegistrationController::class, 'showForm'])->name('dummy-register.form');
Route::post('/dummy-register', [DummyRegistrationController::class, 'processForm'])->name('dummy-register.process');

// ── Owner Dashboard Routes ──
Route::prefix('owner')
    ->middleware(['auth', 'verified', 'role:owner', 'tenant'])
    ->group(function () {
    Route::get('/dashboard', [OwnerDashboardController::class, 'index'])->name('owner.dashboard');
    Route::get('/settings', [OwnerSettingController::class, 'index'])->name('owner.settings');
    Route::post('/profile/complete', [OwnerSettingController::class, 'storeProfile'])->name('owner.profile.complete');
    Route::post('/settings/profile', [OwnerSettingController::class, 'updateBusinessProfile'])->name('owner.settings.profile');
    Route::post('/settings/account', [OwnerSettingController::class, 'updateAccount'])->name('owner.settings.account');
    Route::post('/settings/payment', [OwnerSettingController::class, 'updatePaymentSettings'])->name('owner.settings.payment');
    Route::post('/payouts', [OwnerSettingController::class, 'requestPayout'])->name('owner.payouts.request');

    // ── Checkout Routes (tanpa owner.profile middleware, karena ini billing) ──
    Route::get('/checkout/{plan}', [OwnerCheckoutController::class, 'showCheckout'])->name('owner.checkout');
    Route::post('/checkout', [OwnerCheckoutController::class, 'processCheckout'])->name('owner.checkout.process');
    Route::get('/checkout/{payment}/payment', [OwnerCheckoutController::class, 'showPayment'])->name('owner.checkout.payment');
    Route::post('/checkout/{payment}/check-status', [OwnerCheckoutController::class, 'checkPaymentStatus'])->name('owner.checkout.check-status');
    Route::post('/checkout/{payment}/callback', [OwnerCheckoutController::class, 'handleCallback'])->name('owner.checkout.callback');
    Route::get('/checkout/{payment}/invoice', [OwnerCheckoutController::class, 'showInvoice'])->name('owner.checkout.invoice');

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
        Route::get('/analytics', [OwnerAnalyticsController::class, 'index'])->name('owner.analytics');
        Route::get('/analytics/export', [OwnerAnalyticsController::class, 'export'])->name('owner.analytics.export');
        Route::get('/subscription', [OwnerSubscriptionController::class, 'index'])->name('owner.subscription');
        Route::get('/landing-page', [OwnerLandingPageController::class, 'index'])->name('owner.landing-page');
    });
});

// ── Midtrans Webhook (tanpa auth & CSRF, dipanggil oleh Midtrans) ──
Route::post('/midtrans/webhook', [OwnerCheckoutController::class, 'handleWebhook'])
    ->name('midtrans.webhook');

Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('admin.dashboard');
    });

Route::get('/test-isolasi/{slug}', function () {
    $services = \App\Models\Service::all();

    // Query all() ini akan otomatis terfilter sesuai tenant dari slug URL.
    return response()->json($services);
})->middleware('tenant');
Route::prefix('{slug_usaha}')
    ->middleware('tenant')
    ->group(function () {
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
    });
