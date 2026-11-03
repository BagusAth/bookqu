<?php

namespace App\Providers;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\Infrastructure\Payments\PaymentGatewayManager;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class, function () {
            return new TenantContext;
        });

        $this->app->singleton(PaymentGatewayManager::class, function () {
            return new PaymentGatewayManager;
        });

        $this->app->bind(PaymentGatewayInterface::class, function ($app) {
            return $app->make(PaymentGatewayManager::class)->driver();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(DiagnosingHealth::class, function (DiagnosingHealth $event): void {
            $heartbeat = Cache::get('ops:scheduler-heartbeat');

            if ($heartbeat !== null && now()->timestamp - (int) $heartbeat > 300) {
                throw new \RuntimeException('Scheduler heartbeat is stale.');
            }
        });

        Carbon::setLocale(config('app.locale', 'id'));

        if (! $this->app->runningInConsole() && request()->getHost()) {
            URL::forceRootUrl(request()->getSchemeAndHttpHost());
        }

        // Share Pro subscription status with the sidebar
        View::composer('components.owner.sidebar', function ($view) {
            $userId = auth()->id();
            $tenantId = app(TenantContext::class)->getTenantId();
            $tenant = null;

            if (is_numeric($tenantId)) {
                $tenant = Tenant::find($tenantId);
                if ($tenant && $tenant->iduser !== $userId) {
                    $tenant = null;
                }
            }

            if (! $tenant && $userId) {
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
