<?php

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

