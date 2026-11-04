<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

use App\Domain\Subscription\EntitlementRules;
use App\Domain\Subscription\PlanCapability;
use App\Domain\Subscription\SubscriptionState;
use App\Domain\Subscription\SubscriptionUsage;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class GetSubscriptionOverview
{
    /**
     * Compute and aggregate subscription, plan, usage, limits, and payment history for a tenant.
     *
     * @return array{
     *     langgananaktif: ?Subscription,
     *     recommendedPlanId: mixed,
     *     semuapaket: Collection<int, Plan>,
     *     jumlahlayanan: int,
     *     jumlahbookingbulanini: int,
     *     jumlahstaff: int,
     *     maxlayanan: int,
     *     maxbooking: int,
     *     maxstaff: int,
     *     isunlimited: bool,
     *     isunlimitedstaff: bool,
     *     persenlayanan: int,
     *     persenbooking: int,
     *     persenstaff: int,
     *     statustrial: bool,
     *     sisahari: int,
     *     riwayatpembayaran: Collection<int, Payment>
     * }
     */
    public function execute(?Tenant $tenant): array
    {
        if ($tenant === null) {
            return [
                'langgananaktif' => null,
                'recommendedPlanId' => Plan::where('namapaket', PlanCapability::PLAN_MEDIUM)->value('id'),
                'semuapaket' => Plan::all(),
                'jumlahlayanan' => 0,
                'jumlahbookingbulanini' => 0,
                'jumlahstaff' => 0,
                'maxlayanan' => 0,
                'maxbooking' => 0,
                'maxstaff' => 0,
                'isunlimited' => false,
                'isunlimitedstaff' => false,
                'persenlayanan' => 0,
                'persenbooking' => 0,
                'persenstaff' => 0,
                'statustrial' => false,
                'sisahari' => 0,
                'riwayatpembayaran' => collect(),
            ];
        }

        $idtenant = $tenant->id;

        /** @var Subscription|null $langgananaktif */
        // scope-bypass: filtered by the authenticated tenant
        $langgananaktif = Subscription::withoutGlobalScopes()
            ->where('idtenant', $idtenant)
            ->with('plan')
            ->latest('id')
            ->first();

        $semuapaket = Plan::all();

        $jumlahlayanan = SubscriptionUsage::countServices($idtenant);
        $jumlahbookingbulanini = SubscriptionUsage::countMonthlyBookings($idtenant);
        $jumlahstaff = SubscriptionUsage::countStaff($idtenant);

        $isTrial = $langgananaktif !== null
            && $langgananaktif->status === SubscriptionState::STATUS_TRIAL
            && ! SubscriptionState::isExpired($langgananaktif);

        $serviceQuota = EntitlementRules::canCreateService($langgananaktif, 0);
        $bookingQuota = EntitlementRules::canCreateBooking($langgananaktif, 0);
        $staffQuota = EntitlementRules::canCreateStaff($langgananaktif, 0);
        $maxlayanan = $serviceQuota['maxServices'];
        $maxbooking = $bookingQuota['maxBooking'];
        $maxstaff = $staffQuota['maxStaff'];
        $isunlimited = $bookingQuota['isUnlimited'];
        $isunlimitedstaff = $staffQuota['isUnlimited'];

        $persenlayanan = SubscriptionUsage::calculatePercentage($jumlahlayanan, $maxlayanan, $serviceQuota['isUnlimited']);
        $persenbooking = SubscriptionUsage::calculatePercentage($jumlahbookingbulanini, $maxbooking, $bookingQuota['isUnlimited']);
        $persenstaff = SubscriptionUsage::calculatePercentage($jumlahstaff, $maxstaff, $isunlimitedstaff);

        $sisahari = SubscriptionState::getRemainingTrialDays($langgananaktif);
        $statustrial = $isTrial && $langgananaktif?->trial_berakhir !== null;

        // scope-bypass: filtered by the authenticated tenant
        $riwayatpembayaran = Payment::withoutGlobalScopes()
            ->where('idtenant', $idtenant)
            ->where('tipe', 'subscription')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return [
            'langgananaktif' => $langgananaktif,
            'recommendedPlanId' => $semuapaket->firstWhere('namapaket', PlanCapability::PLAN_MEDIUM)?->id,
            'semuapaket' => $semuapaket,
            'jumlahlayanan' => $jumlahlayanan,
            'jumlahbookingbulanini' => $jumlahbookingbulanini,
            'jumlahstaff' => $jumlahstaff,
            'maxlayanan' => $maxlayanan,
            'maxbooking' => $maxbooking,
            'maxstaff' => $maxstaff,
            'isunlimited' => $isunlimited,
            'isunlimitedstaff' => $isunlimitedstaff,
            'persenlayanan' => $persenlayanan,
            'persenbooking' => $persenbooking,
            'persenstaff' => $persenstaff,
            'statustrial' => $statustrial,
            'sisahari' => $sisahari,
            'riwayatpembayaran' => $riwayatpembayaran,
        ];
    }
}
