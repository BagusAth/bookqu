<?php

namespace App\Actions\Payment;

use App\Domain\Team\TenantPermission;
use App\Models\User;
use App\Notifications\RefundTeamNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class NotifyRefundTeam
{
    public function execute(int $tenantId, int $subjectId, string $event): void
    {
        $members = DB::table('tenant_members')->where('idtenant', $tenantId)
            ->whereNotNull('accepted_at')->whereNull('revoked_at')->get(['iduser', 'role']);
        foreach ($members as $member) {
            if (TenantPermission::allowsRole($member->role, 'refunds.decide')) {
                try {
                    User::find($member->iduser)?->notify(new RefundTeamNotification($tenantId, $subjectId, $event));
                } catch (\Throwable $error) {
                    Log::warning('Refund team notification failed', ['tenant_id' => $tenantId, 'subject_id' => $subjectId, 'type' => $error::class]);
                }
            }
        }
    }
}
