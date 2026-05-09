<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\DummyRegistrationController;
use App\Http\Controllers\OwnerAnalyticsController;
use App\Http\Controllers\OwnerBookingController;
use App\Http\Controllers\OwnerDashboardController;
use App\Http\Controllers\OwnerLandingPageController;
use App\Http\Controllers\OwnerProgramController;
use App\Http\Controllers\OwnerScheduleController;
use App\Http\Controllers\OwnerSettingController;
use App\Http\Controllers\OwnerSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ── Authentication Routes ──
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');
    
    Route::post('/login', function (\Illuminate\Http\Request $request) {
        // Handle login here
    })->name('login.store');
    
    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');
    
    Route::post('/register', function (\Illuminate\Http\Request $request) {
        // Handle registration here
    })->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', function (\Illuminate\Support\Facades\Auth $auth) {
        $auth::logout();
        return redirect('/');
    })->name('logout');
});

// Dummy registration module (isolated for slug testing)
Route::get('/dummy-register', [DummyRegistrationController::class, 'showForm'])->name('dummy-register.form');
Route::post('/dummy-register', [DummyRegistrationController::class, 'processForm'])->name('dummy-register.process');

// ── Owner Dashboard Routes ──
Route::prefix('owner')->group(function () {
    Route::get('/dashboard', [OwnerDashboardController::class, 'index'])->name('owner.dashboard');
    Route::get('/programs', [OwnerProgramController::class, 'index'])->name('owner.programs');
    Route::post('/programs', [OwnerProgramController::class, 'store'])->name('owner.programs.store');
    Route::get('/schedule', [OwnerScheduleController::class, 'index'])->name('owner.schedule');
    Route::post('/schedule/bulk-slots', [OwnerScheduleController::class, 'bulkStore'])->name('owner.schedule.bulk-store');
    Route::get('/bookings', [OwnerBookingController::class, 'index'])->name('owner.bookings');
    Route::get('/analytics', [OwnerAnalyticsController::class, 'index'])->name('owner.analytics');
    Route::get('/subscription', [OwnerSubscriptionController::class, 'index'])->name('owner.subscription');
    Route::get('/landing-page', [OwnerLandingPageController::class, 'index'])->name('owner.landing-page');
    Route::get('/settings', [OwnerSettingController::class, 'index'])->name('owner.settings');
});

Route::get('/test-isolasi/{slug}', function () {
    $services = \App\Models\Service::all();

    // Query all() ini akan otomatis terfilter sesuai tenant dari slug URL.
    return response()->json($services);
})->middleware('tenant');
Route::prefix('{slug_usaha}')
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
