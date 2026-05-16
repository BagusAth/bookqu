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
use App\Models\User;
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

        $user = $request->user();

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

        return redirect()->route('owner.dashboard');
    })->name('register.store');
});

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
    ->middleware(['auth', 'role:owner', 'tenant'])
    ->group(function () {
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
