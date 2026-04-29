<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/tenants', function () {
        $tenants = DB::table('tenants')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('admin.tenants.index', [
            'tenants' => $tenants,
        ]);
    })->name('tenants');

    Route::get('/subscriptions', function () {
        $subscriptions = DB::table('subscriptions')
            ->join('tenants', 'subscriptions.idtenant', '=', 'tenants.id')
            ->join('plans', 'subscriptions.idplan', '=', 'plans.id')
            ->select('subscriptions.*', 'tenants.namabisnis', 'plans.namapaket')
            ->orderByDesc('subscriptions.created_at')
            ->limit(20)
            ->get();

        return view('admin.subscriptions.index', [
            'subscriptions' => $subscriptions,
        ]);
    })->name('subscriptions');
});
