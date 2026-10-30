<?php

namespace App\Actions\CustomerAccess;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\CustomerAccess\AccessContext;
use App\Domain\CustomerAccess\AccessOperation;
use App\Domain\CustomerAccess\AccessProfile;
use App\Models\Booking;
use App\Models\CustomerAccessGrant;
use App\Models\Payment;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

final class ResolveCustomerAccess
{
    public function credential(#[\SensitiveParameter] string $credential): AccessContext
    {
        abort_unless(preg_match('/\A([a-f0-9]{32})\.([a-f0-9]{64})\z/D', $credential, $parts) === 1, 404);
        // scope-bypass: selector bootstraps identity; subject and any existing tenant context are checked next.
        $grant = CustomerAccessGrant::withoutGlobalScopes()->where('selector', $parts[1])->first();
        abort_unless($grant !== null && hash_equals($grant->secret_hash, hash('sha256', $parts[2])), 404);

        return $this->execute((int) $grant->id);
    }

    public function execute(int $grantId): AccessContext
    {
        // scope-bypass: server-side session references a grant; verify tenant against its stored subject.
        $grant = CustomerAccessGrant::withoutGlobalScopes()->find($grantId);
        abort_unless($grant !== null && $grant->revoked_at === null && $grant->expires_at->isFuture(), 404);
        abort_unless(AccessProfile::tryFrom($grant->getRawOriginal('profile')) !== null, 404);
        $tenantId = app(TenantContext::class)->getTenantId();
        abort_if($tenantId !== null && $tenantId !== (int) $grant->idtenant, 404);

        return app(LoadAccessSubject::class)->execute($grant);
    }

    public function underLock(AccessContext $context, AccessOperation $operation, Payment|Booking $subject): AccessContext
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Customer authorization requires the business transaction.');
        }
        ClaimSlots::lockTenant((int) $context->grant->idtenant);
        $current = $this->execute((int) $context->grant->id);
        $current->authorize($operation, $subject instanceof Payment ? $subject : null, $subject instanceof Booking ? $subject : null);

        return $current;
    }
}
