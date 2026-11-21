<?php

namespace App\Providers;

use App\Actions\Owner\GetOwnerShell;
use App\Actions\Subscription\GetPublicPlanCards;
use App\Actions\Team\TeamNotificationAccess;
use App\Contracts\Payments\PaymentGatewayInterface;
use App\Domain\Integrations\BookingIntegrationEvent;
use App\Domain\Modules\ModuleRelease;
use App\Domain\Notifications\NotificationPreferences;
use App\Infrastructure\Payments\PaymentGatewayManager;
use App\Jobs\DeliverGoogleCalendarEvent;
use App\Models\AdditionalItem;
use App\Models\Asset;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Category;
use App\Models\CustomerNote;
use App\Models\OwnerBlockedDate;
use App\Models\OwnerIntegration;
use App\Models\OwnerPayout;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\TenantPayoutAccount;
use App\Models\Unit;
use App\Models\User;
use App\Models\Voucher;
use App\Observers\BookingIntegrationObserver;
use App\Policies\TenantResourcePolicy;
use App\Support\TenantActor;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->app->scoped(TenantActor::class);
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
        RateLimiter::for('current-password', fn (Request $request): Limit => Limit::perMinute(5)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));

        Event::listen(DiagnosingHealth::class, function (DiagnosingHealth $event): void {
            $heartbeat = Cache::get('ops:scheduler-heartbeat');

            if ($heartbeat !== null && now()->timestamp - (int) $heartbeat > 300) {
                throw new \RuntimeException('Scheduler heartbeat is stale.');
            }
        });

        Event::listen(NotificationSending::class, function (NotificationSending $event): bool {
            if (! app(TeamNotificationAccess::class)->allows($event->notifiable, $event->notification)) {
                return false;
            }

            return $event->channel !== 'mail' || ! $event->notifiable instanceof User
                || NotificationPreferences::allowsMail($event->notifiable, $event->notification);
        });
        Gate::define('tenant-ability', fn (User $user, ?string $ability) => app(TenantResourcePolicy::class)->ability($user, $ability));
        foreach ([Booking::class, Service::class, Category::class, Schedule::class, OwnerBlockedDate::class, CustomerNote::class, Review::class, OwnerPayout::class, TenantPayoutAccount::class, Tenant::class, Asset::class, Voucher::class, AdditionalItem::class, Payment::class, Subscription::class, TenantMember::class, Unit::class] as $resource) {
            Gate::policy($resource, 'App\\Policies\\'.class_basename($resource).'Policy');
        }

        Booking::observe(BookingIntegrationObserver::class);
        Event::listen(BookingIntegrationEvent::class, function (BookingIntegrationEvent $event): void {
            if (ModuleRelease::state('integrations') !== 'released') {
                return;
            }

            // scope-bypass: an after-commit event contains the tenant of the saved booking.
            if (OwnerIntegration::withoutGlobalScopes()->where('idtenant', $event->tenantId)->whereNotNull('google_refresh_token')->exists()) {
                DeliverGoogleCalendarEvent::dispatch($event);
            }
        });

        Event::listen('eloquent.created: '.Booking::class, function (Booking $booking): void {
            BookingLog::record($booking->id, 'created', 'Booking dibuat.', [
                'actor' => request()->routeIs('customer.*') ? 'customer' : 'system',
            ]);
        });

        foreach ([Service::class, Category::class, Schedule::class, OwnerBlockedDate::class, CustomerNote::class,
            Review::class, OwnerPayout::class, TenantPayoutAccount::class, Tenant::class,
            Asset::class, Voucher::class, AdditionalItem::class, Payment::class, Subscription::class] as $record) {
            foreach (['created', 'updated', 'deleted'] as $event) {
                Event::listen("eloquent.{$event}: {$record}", fn ($model) => app(TenantActor::class)->record($model, $event));
            }
        }

        View::composer('components.landing.pricing', fn ($view) => $view->with(app(GetPublicPlanCards::class)->execute(auth()->user())));

        Carbon::setLocale(config('app.locale', 'id'));

        if (! $this->app->runningInConsole() && request()->getHost()) {
            URL::forceRootUrl(request()->getSchemeAndHttpHost());
        }

        // The owner app shell (sidebar, top bar, phone navigation) is prepared once per request (BQ-128).
        View::composer('layouts.owner-layout', function ($view): void {
            $view->with('shell', app(GetOwnerShell::class)->execute(auth()->user(), request()->route()?->getName()));
        });
    }
}
