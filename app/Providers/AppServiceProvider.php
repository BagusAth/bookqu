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
            $userId = auth()->id();
            $tenantId = session('current_tenant_id');
            $tenant = null;

            if (is_numeric($tenantId)) {
                $tenant = Tenant::find($tenantId);
                if ($tenant && $tenant->iduser !== $userId) {
                    $tenant = null;
                }
            }

            if (!$tenant && $userId) {
                $tenant = Tenant::where('iduser', $userId)->first();
            }

            $adalahpro = false;

            if ($tenant) {
                $langganan = Subscription::where('idtenant', $tenant->id)
                    ->with('plan')
                    ->where('status', '!=', 'expired')
                    ->latest()
                    ->first();

                if ($langganan && $langganan->plan) {
                    $adalahpro = str_contains(strtolower($langganan->plan->namapaket), 'pro');
                }
            }

            $view->with('adalahpro', $adalahpro);
        });
    }
}
