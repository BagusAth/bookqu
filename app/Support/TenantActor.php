<?php

namespace App\Support;

use App\Models\TenantMember;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class TenantActor
{
    private ?TenantMember $member = null;

    public function run(?TenantMember $member, Closure $action): mixed
    {
        $previous = $this->member;
        $this->member = $member;

        try {
            return $action();
        } finally {
            $this->member = $previous;
        }
    }

    /** @return array{actor_iduser: ?int, actor_role: string} */
    public function bookingMetadata(string $fallback): array
    {
        return [
            'actor_iduser' => $this->member === null ? null : (int) $this->member->iduser,
            'actor_role' => $this->member === null ? $fallback : (string) $this->member->role,
        ];
    }

    public function recordAccountChange(int $userId): void
    {
        if ($this->member !== null && (int) $this->member->iduser === $userId) {
            $this->write('account.updated', $userId);
        }
    }

    public function record(Model $record, string $event): void
    {
        if ($this->member === null) {
            return;
        }

        $tenantId = $record->getTable() === 'tenants' ? $record->getKey() : $record->getAttribute('idtenant');
        if ((int) $tenantId !== (int) $this->member->idtenant || ($record->getTable() === 'tenants' && $event === 'deleted')) {
            return;
        }

        $this->write($record->getTable().'.'.$event, (int) $record->getKey());
    }

    private function write(string $event, int $subject): void
    {
        if ($this->member === null) {
            return;
        }

        DB::table('tenant_activity_logs')->insert([
            'idtenant' => $this->member->idtenant,
            'iduser' => $this->member->iduser,
            'role' => $this->member->role,
            'event' => $event,
            'subject_id' => $subject,
            'created_at' => now(),
        ]);
    }
}
