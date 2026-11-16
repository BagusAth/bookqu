<?php

use App\Http\Controllers\Owner\OwnerApiController;
use Illuminate\Support\Facades\Route;

// BQ-156 owns these settings routes and shares the owner portal's authorization stack.
Route::prefix('owner/settings/api')->name('owner.api.')->middleware([
    'auth', 'verified', 'role:owner', 'tenant', 'module:api', 'subscription:api',
    'tenant.permission', 'subscription.create', 'owner.profile',
])->group(function (): void {
    Route::get('/', [OwnerApiController::class, 'index'])->name('index');
    Route::post('/keys', [OwnerApiController::class, 'createKey'])->name('keys.store');
    Route::delete('/keys/{id}', [OwnerApiController::class, 'revokeKey'])->whereNumber('id')->name('keys.revoke');
    Route::post('/webhooks', [OwnerApiController::class, 'createWebhook'])->name('webhooks.store');
    Route::delete('/webhooks/{id}', [OwnerApiController::class, 'deleteWebhook'])->whereNumber('id')->name('webhooks.destroy');
});
