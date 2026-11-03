<?php

namespace App\Providers;

use App\Actions\Team\GetVisibleNotifications;
use App\Actions\Team\TeamNotificationAccess;
use App\Contracts\Payments\PaymentGatewayInterface;
use App\Domain\Subscription\EntitlementRules;
use App\Infrastructure\Payments\PaymentGatewayManager;
use App\Models\AdditionalItem;
use App\Models\Asset;
use App\Models\Booking;
use App\Models\Category;
use App\Models\CustomerNote;
use App\Models\OwnerBlockedDate;
use App\Models\OwnerPayout;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\TenantPayoutAccount;
use App\Models\User;
use App\Models\Voucher;
use App\Policies\TenantResourcePolicy;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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

        Event::listen(NotificationSending::class, fn ($event) => app(TeamNotificationAccess::class)->allows($event->notifiable, $event->notification));
        Gate::define('tenant-ability', fn (User $user, ?string $ability) => app(TenantResourcePolicy::class)->ability($user, $ability));
        foreach ([Booking::class, Service::class, Category::class, Schedule::class, OwnerBlockedDate::class, CustomerNote::class, Review::class, OwnerPayout::class, TenantPayoutAccount::class, Tenant::class, Staff::class, \App\Models\Resource::class, Asset::class, Voucher::class, AdditionalItem::class, Payment::class, Subscription::class, TenantMember::class] as $resource) {
            Gate::policy($resource, 'App\\Policies\\'.class_basename($resource).'Policy');
        }

        Carbon::setLocale(config('app.locale', 'id'));

        if (! $this->app->runningInConsole() && request()->getHost()) {
            URL::forceRootUrl(request()->getSchemeAndHttpHost());
        }

        View::composer(['components.owner.sidebar', 'components.owner.topbar'], function ($view): void {
            $user = auth()->user();
            $count = $user ? app(GetVisibleNotifications::class)->execute($user)->whereNull('read_at')->count() : 0;
            $view->with('visibleUnreadCount', $count);
        });

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
                    ->latest()
                    ->first();

                if ($langganan && $langganan->plan) {
                    $adalahpro = EntitlementRules::isPro($langganan);
                }
            }

            $view->with('adalahpro', $adalahpro);
        });
    }
}
