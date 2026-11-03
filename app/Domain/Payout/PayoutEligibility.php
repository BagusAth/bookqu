<?php

declare(strict_types=1);

namespace App\Domain\Payout;

use App\Models\OwnerPayout;
use App\Models\Tenant;
use App\Models\TenantPayoutAccount;
use App\Models\User;

class PayoutEligibility
{
    /**
     * Compute all eligibility rejection reasons in strict priority order.
     *
     * @return array<int, string>
     */
    public static function reasons(Tenant $tenant, ?User $user = null): array
    {
        $reasons = [];
        $owner = $user ?? ($tenant->user instanceof User ? $tenant->user : null);

        // 1. Email verification
        if ($owner && ! $owner->hasVerifiedEmail()) {
            $reasons[] = 'Verifikasi e-mail Anda terlebih dahulu.';
        }

        // 2. Business profile completeness (namabisnis, slug, jenisbisnis, nomorhp)
        $isProfileComplete = (bool) (
            ! empty($tenant->namabisnis) &&
            ! empty($tenant->slug) &&
            ! empty($tenant->jenisbisnis) &&
            ! empty($tenant->nomorhp)
        );
        if (! $isProfileComplete) {
            $reasons[] = 'Lengkapi profil bisnis terlebih dahulu.';
        }

        // 3. Payment mode
        if (($tenant->payment_mode ?? 'platform') !== 'platform') {
            $reasons[] = 'Withdraw hanya tersedia untuk mode pembayaran platform.';
        }

        // 4. Available balance
        if ((float) $tenant->saldo_platform <= 0) {
            $reasons[] = 'Saldo belum tersedia untuk ditarik.';
        }

        // 5. Open in-flight payouts (pending + approved) >= max_open
        $maxOpen = config('payouts.max_open');
        if ($maxOpen !== null && is_numeric($maxOpen)) {
            // scope-bypass: eligibility evaluates counts for the explicitly provided tenant
            $openCount = OwnerPayout::withoutGlobalScopes()
                ->where('idtenant', $tenant->id)
                ->whereIn('status', ['pending', 'approved'])
                ->count();
            if ($openCount >= (int) $maxOpen) {
                $reasons[] = 'Masih ada pengajuan withdraw yang sedang diproses. Tunggu sampai selesai sebelum mengajukan lagi.';
            }
        }

        // 6. Saved payout accounts exist
        // scope-bypass: eligibility evaluates accounts for the explicitly provided tenant
        $accountCount = TenantPayoutAccount::withoutGlobalScopes()
            ->where('idtenant', $tenant->id)
            ->whereNull('deleted_at')
            ->count();
        if ($accountCount === 0) {
            $reasons[] = 'Tambahkan rekening penarikan terlebih dahulu.';
        }

        return $reasons;
    }

    public static function isEligible(Tenant $tenant, ?User $user = null): bool
    {
        return empty(self::reasons($tenant, $user));
    }
}
