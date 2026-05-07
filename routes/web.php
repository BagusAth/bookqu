<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

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
