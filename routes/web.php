<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/{slug_usaha}', [BookingController::class, 'showProgramSelection'])
    ->name('customer.booking.program');
