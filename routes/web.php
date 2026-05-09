<?php

use App\Http\Controllers\OwnerAnalyticsController;
use App\Http\Controllers\OwnerAuthController;
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

Route::redirect('/login', '/owner/login')->name('login');
Route::redirect('/register', '/owner/register')->name('register');

// ── Owner Auth Routes ──
Route::get('/owner/login', [OwnerAuthController::class, 'showlogin'])->name('owner.login');
Route::post('/owner/login', [OwnerAuthController::class, 'login'])->name('owner.login.submit');
Route::get('/owner/register', [OwnerAuthController::class, 'showregister'])->name('owner.register');
Route::post('/owner/register', [OwnerAuthController::class, 'register'])->name('owner.register.submit');
Route::post('/owner/logout', [OwnerAuthController::class, 'logout'])
    ->middleware(['auth', 'owner'])
    ->name('owner.logout');

// ── Owner Dashboard Routes ──
Route::prefix('owner')->middleware(['auth', 'owner'])->group(function () {
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

