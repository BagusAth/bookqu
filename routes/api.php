<?php

use App\Http\Controllers\Api\OwnerDataController;
use App\Http\Middleware\AuthenticateOwnerApiKey;
use Illuminate\Support\Facades\Route;

Route::get('/v1/{resource}', [OwnerDataController::class, 'index'])
    ->whereIn('resource', ['services', 'schedules', 'bookings', 'customers'])
    ->middleware(AuthenticateOwnerApiKey::class)
    ->name('api.v1.index');
