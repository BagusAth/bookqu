<?php

namespace App\Providers;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share Pro subscription status with the sidebar
        View::composer('components.owner.sidebar', function ($view) {
            $tenant = Tenant::first();
            $adalahpro = false;

            if ($tenant) {
                $langganan = Subscription::where('idtenant', $tenant->id)
                    ->with('plan')
                    ->where('status', '!=', 'expired')
                    ->latest()
                    ->first();

                $adalahpro = $langganan?->plan?->namapaket === 'pro';
            }

            $view->with('adalahpro', $adalahpro);
        });
    }
}
