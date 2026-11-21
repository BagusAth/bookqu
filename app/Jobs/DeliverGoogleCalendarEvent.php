<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Integrations\ManageGoogleCalendar;
use App\Domain\Integrations\BookingIntegrationEvent;
use App\Domain\Modules\ModuleRelease;
use App\Domain\Subscription\EntitlementRules;
use App\Support\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class DeliverGoogleCalendarEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 35;

    public function __construct(public BookingIntegrationEvent $event) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(TenantContext $context, ManageGoogleCalendar $calendar): void
    {
        if (ModuleRelease::state('integrations') !== 'released') {
            return;
        }

        $context->run($this->event->tenantId, function () use ($context, $calendar): void {
            $tenant = $context->tenant();
            if ($tenant !== null && EntitlementRules::canAccessFeature($tenant->subscription()->with('plan')->first(), 'integrations')['allowed']) {
                $calendar->deliver($tenant, $this->event);
            }
        });
    }
}
