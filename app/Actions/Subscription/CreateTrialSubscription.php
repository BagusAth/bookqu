<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

use App\Domain\Subscription\PlanCapability;
use App\Domain\Subscription\SubscriptionState;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;

class CreateTrialSubscription
{
    /**
     * Create a default 7-day Pro trial subscription for a new tenant.
     */
    public function execute(Tenant $tenant, int $trialDays = 7): Subscription
    {
        $proPlan = Plan::firstOrCreate(
            ['namapaket' => PlanCapability::PLAN_PRO],
            [
                'hargabulanan' => 499000,
                'maxlayanan'   => 0,
                'maxbooking'   => 0,
                'isunlimited'  => true,
            ]
        );

        return Subscription::create([
            'idtenant'       => $tenant->id,
            'idplan'         => $proPlan->id,
            'status'         => SubscriptionState::STATUS_TRIAL,
            'trial_berakhir' => now()->addDays($trialDays),
        ]);
    }
}
