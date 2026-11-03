<?php

namespace App\Actions\Subscription;

use App\Actions\Team\ResolveTenantMembership;
use App\Domain\Subscription\EntitlementRules;
use App\Domain\Team\TenantPermission;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Support\TenantContext;

final class GetPublicPlanCards
{
    /** @return array<string, mixed> */
    public function execute(?User $user): array
    {

        $plans = Plan::all()->keyBy(fn ($plan) => strtolower(trim((string) $plan->namapaket)));

        $member = $user && $user->isOwner() ? app(ResolveTenantMembership::class)->execute($user) : null;
        $currentTenant = $member?->tenant;
        $currentSubscription = null;
        if ($currentTenant) {
            $context = app(TenantContext::class);
            $previous = $context->getTenantId();
            try {
                $context->setTenantId((int) $currentTenant->id);
                $currentSubscription = Subscription::with('plan')->where('idtenant', $currentTenant->id)->latest()->first();
            } finally {
                $context->setTenantId($previous);
            }
        }
        $activePlanName = EntitlementRules::activePaidPlanName($currentSubscription);

        $activePlan = $activePlanName ? $plans->get($activePlanName) : null;

        $resolvePlanAction = function (string $planKey, string $defaultLabel, string $focusRingColor = 'focus:ring-[#4F46E5]') use ($user, $plans, $activePlanName, $activePlan, $member) {
            $targetPlan = $plans->get(strtolower($planKey));

            // 1. Guest (unauthenticated)
            if (! $user) {
                return [
                    'url' => route('register', ['plan' => $planKey]),
                    'label' => $defaultLabel,
                    'is_active_plan' => false,
                    'show_login' => true,
                    'class' => "btn-paket w-full py-3.5 px-4 rounded-xl border border-gray-200 text-gray-700 font-semibold text-sm mt-auto focus:outline-none focus:ring-2 {$focusRingColor} text-center block",
                ];
            }

            // 2. Admin
            if ($user->isAdmin()) {
                return [
                    'url' => route('admin.dashboard'),
                    'label' => 'Buka Dashboard Admin',
                    'is_active_plan' => false,
                    'show_login' => false,
                    'class' => "btn-paket w-full py-3.5 px-4 rounded-xl border border-gray-200 text-gray-700 font-semibold text-sm mt-auto focus:outline-none focus:ring-2 {$focusRingColor} text-center block",
                ];
            }

            // 3. Owner belum verifikasi email
            if ($user->isOwner() && ! $user->hasVerifiedEmail()) {
                return [
                    'url' => route('verification.notice'),
                    'label' => 'Verifikasi Email Anda',
                    'is_active_plan' => false,
                    'show_login' => false,
                    'class' => 'btn-paket w-full py-3.5 px-4 rounded-xl border border-amber-300 bg-amber-50 text-amber-800 font-semibold text-sm mt-auto focus:outline-none focus:ring-2 focus:ring-amber-500 text-center block',
                ];
            }

            if ($member !== null && ! TenantPermission::allowsRole((string) $member->role, 'subscription.manage')) {
                return ['url' => route('owner.dashboard'), 'label' => 'Kembali ke bisnis', 'is_active_plan' => false, 'show_login' => false,
                    'class' => 'btn-paket w-full py-3.5 px-4 rounded-xl border border-gray-200 text-gray-700 font-semibold text-center block'];
            }

            // 4. Owner dengan paket ini sedang aktif
            if ($activePlanName !== null && $activePlanName === strtolower($planKey)) {
                return [
                    'url' => route('owner.subscription'),
                    'label' => '✓ Paket Aktif Anda',
                    'is_active_plan' => true,
                    'show_login' => false,
                    'class' => 'btn-paket-current w-full py-3.5 px-4 rounded-xl border border-emerald-300 bg-emerald-50 text-emerald-800 font-bold text-sm mt-auto text-center block cursor-default shadow-xs',
                ];
            }

            // 5. Owner on trial / upgrade / memilih paket lain
            if ($user->isOwner()) {
                $checkoutUrl = $targetPlan ? route('owner.checkout', $targetPlan->id) : route('owner.subscription');
                $label = $defaultLabel;
                if ($activePlan && $targetPlan) {
                    if ($targetPlan->hargabulanan > $activePlan->hargabulanan) {
                        $label = 'Upgrade ke '.ucfirst($planKey);
                    } else {
                        $label = 'Pilih '.ucfirst($planKey);
                    }
                } elseif ($activePlanName !== null) {
                    $label = 'Upgrade ke '.ucfirst($planKey);
                }

                return [
                    'url' => $checkoutUrl,
                    'label' => $label,
                    'is_active_plan' => false,
                    'show_login' => false,
                    'class' => "btn-paket w-full py-3.5 px-4 rounded-xl border border-gray-200 text-gray-700 font-semibold text-sm mt-auto focus:outline-none focus:ring-2 {$focusRingColor} text-center block",
                ];
            }

            // 6. Logged in customer / other role
            return [
                'url' => route('register', ['plan' => $planKey]),
                'label' => $defaultLabel,
                'is_active_plan' => false,
                'show_login' => false,
                'class' => "btn-paket w-full py-3.5 px-4 rounded-xl border border-gray-200 text-gray-700 font-semibold text-sm mt-auto focus:outline-none focus:ring-2 {$focusRingColor} text-center block",
            ];
        };

        $smallAction = $resolvePlanAction('small', 'Mulai Sekarang', 'focus:ring-[#4F46E5]');
        $mediumAction = $resolvePlanAction('medium', 'Pilih Medium', 'focus:ring-[#4F46E5]');
        $proAction = $resolvePlanAction('pro', 'Pilih Pro', 'focus:ring-purple-600');

        return compact('plans', 'smallAction', 'mediumAction', 'proAction');
    }
}
