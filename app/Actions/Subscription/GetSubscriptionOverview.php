<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

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
                'langgananaktif'        => null,
                'semuapaket'            => Plan::all(),
                'jumlahlayanan'         => 0,
                'jumlahbookingbulanini' => 0,
                'jumlahstaff'           => 0,
                'maxlayanan'            => 0,
                'maxbooking'            => 0,
                'maxstaff'              => 0,
                'isunlimited'           => false,
                'isunlimitedstaff'      => false,
                'persenlayanan'         => 0,
                'persenbooking'         => 0,
                'persenstaff'           => 0,
                'statustrial'           => false,
                'sisahari'              => 0,
                'riwayatpembayaran'     => collect(),
            ];
        }

        $idtenant = $tenant->id;

        /** @var Subscription|null $langgananaktif */
        $langgananaktif = Subscription::withoutGlobalScopes()
            ->where('idtenant', $idtenant)
            ->with('plan')
            ->latest()
            ->first();

        $semuapaket = Plan::all();

        $jumlahlayanan = SubscriptionUsage::countServices($idtenant);
        $jumlahbookingbulanini = SubscriptionUsage::countMonthlyBookings($idtenant);
        $jumlahstaff = SubscriptionUsage::countStaff($idtenant);

        $isTrial = $langgananaktif !== null
            && $langgananaktif->status === SubscriptionState::STATUS_TRIAL
            && !SubscriptionState::isExpired($langgananaktif);

        $plan = $langgananaktif?->plan;
        $planName = strtolower(trim((string) ($plan?->namapaket ?? PlanCapability::PLAN_SMALL)));
        $isunlimited = (bool) ($plan?->isunlimited ?? false);

        $maxlayanan = PlanCapability::getServiceLimit($plan, $isTrial);
        $maxbooking = PlanCapability::getBookingLimit($plan, $isTrial);
        $isunlimitedstaff = $isunlimited || $planName === PlanCapability::PLAN_PRO || $isTrial;
        $maxstaff = PlanCapability::getStaffLimit($planName, $isTrial, $isunlimited);

        $persenlayanan = SubscriptionUsage::calculatePercentage($jumlahlayanan, $maxlayanan, $isunlimited || $isTrial);
        $persenbooking = SubscriptionUsage::calculatePercentage($jumlahbookingbulanini, $maxbooking, $isunlimited || $planName === PlanCapability::PLAN_PRO || $isTrial);
        $persenstaff = SubscriptionUsage::calculatePercentage($jumlahstaff, $maxstaff, $isunlimitedstaff);

        $sisahari = SubscriptionState::getRemainingTrialDays($langgananaktif);
        $statustrial = $isTrial && $langgananaktif?->trial_berakhir !== null;

        $riwayatpembayaran = Payment::withoutGlobalScopes()
            ->where('idtenant', $idtenant)
            ->where('tipe', 'subscription')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return [
            'langgananaktif'        => $langgananaktif,
            'semuapaket'            => $semuapaket,
            'jumlahlayanan'         => $jumlahlayanan,
            'jumlahbookingbulanini' => $jumlahbookingbulanini,
            'jumlahstaff'           => $jumlahstaff,
            'maxlayanan'            => $maxlayanan,
            'maxbooking'            => $maxbooking,
            'maxstaff'              => $maxstaff,
            'isunlimited'           => $isunlimited,
            'isunlimitedstaff'      => $isunlimitedstaff,
            'persenlayanan'         => $persenlayanan,
            'persenbooking'         => $persenbooking,
            'persenstaff'           => $persenstaff,
            'statustrial'           => $statustrial,
            'sisahari'              => $sisahari,
            'riwayatpembayaran'     => $riwayatpembayaran,
        ];
    }
}
