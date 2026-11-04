<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\Subscription\PlanCapability;
use App\Domain\Subscription\SubscriptionState;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTrialSubscription
{
    /**
     * Create a default 7-day Pro trial subscription for a new tenant.
     */
    public function execute(Tenant $tenant, int $trialDays = 7): Subscription
    {
        return DB::transaction(function () use ($tenant, $trialDays): Subscription {
            ClaimSlots::lockTenant((int) $tenant->id);
            $owner = User::query()->lockForUpdate()->findOrFail($tenant->iduser);
            // scope-bypass: trial issuance is tied to the stored owning account and locked tenant.
            $existing = Subscription::withoutGlobalScopes()->where('idtenant', $tenant->id)->latest('id')->first();
            if ($existing) {
                return $existing;
            }
            $proPlan = Plan::firstOrCreate(
                ['namapaket' => PlanCapability::PLAN_PRO],
                [
                    'hargabulanan' => 499000,
                    'maxlayanan' => 0,
                    'maxbooking' => 0,
                    'isunlimited' => true,
                ]
            );

            $used = $owner->trial_used_at !== null;
            if (! $used) {
                $owner->forceFill(['trial_used_at' => now()])->save();
            }

            return Subscription::create([
                'idtenant' => $tenant->id,
                'idplan' => $proPlan->id,
                'status' => $used ? SubscriptionState::STATUS_EXPIRED : SubscriptionState::STATUS_TRIAL,
                'trial_berakhir' => $used ? now() : now()->addDays($trialDays),
            ]);
        });
    }
}
