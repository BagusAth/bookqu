<?php

namespace App\Actions\Team;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\Subscription\EntitlementRules;
use App\Domain\Team\TenantPermission;
use App\Jobs\SendTeamInvitation;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use stdClass;

final class ManageTeam
{
    public function invite(Tenant $tenant, User $actor, string $email, string $role): void
    {
        DB::transaction(function () use ($tenant, $actor, $email, $role): void {
            ClaimSlots::lockTenant($tenant->id);
            $this->assertOwner($tenant, $actor);
            $this->assertSeats($tenant);
            $email = strtolower($email);
            if ($this->pending()->where('idtenant', $tenant->id)->where('email', $email)->exists()
                || strcasecmp($actor->email, $email) === 0
                || DB::table('tenant_members')->join('users', 'users.id', '=', 'tenant_members.iduser')
                    ->where('tenant_members.idtenant', $tenant->id)->whereNotNull('tenant_members.accepted_at')
                    ->whereNull('tenant_members.revoked_at')->whereRaw('LOWER(users.email) = ?', [$email])->exists()) {
                throw ValidationException::withMessages(['email' => 'Akun ini sudah memiliki akses atau undangan.']);
            }

            $selector = bin2hex(random_bytes(16));
            $secret = bin2hex(random_bytes(32));
            $id = DB::table('tenant_invitations')->insertGetId([
                'idtenant' => $tenant->id,
                'invited_by' => $actor->id,
                'email' => $email,
                'role' => $role,
                'selector' => $selector,
                'secret_hash' => hash('sha256', $secret),
                'expires_at' => now()->addDay(),
                'created_at' => now(),
            ]);
            $this->record($tenant->id, $actor->id, 'pemilik', 'team.invited', $id);
            SendTeamInvitation::dispatch($id, $selector.'.'.$secret)->afterCommit();
        });
    }

    public function pending(): Builder
    {
        return DB::table('tenant_invitations')->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at', '>', now());
    }

    public function resolve(string $credential): stdClass
    {
        abort_unless(preg_match('/\A[a-f0-9]{32}\.[a-f0-9]{64}\z/', $credential) === 1, 404);
        [$selector, $secret] = explode('.', $credential);
        $invite = $this->pending()->where('selector', $selector)->first();
        abort_unless($invite && hash_equals((string) $invite->secret_hash, hash('sha256', $secret)), 404);

        return $invite;
    }

    /** @param array{name?: string, nomorhp?: string, password?: string} $data */
    public function accept(int $id, ?User $user, array $data): User
    {
        $initial = $this->pending()->find($id);
        abort_unless($initial instanceof stdClass, 404);

        return DB::transaction(function () use ($initial, $id, $user, $data): User {
            $tenantId = (int) $initial->idtenant;
            ClaimSlots::lockTenant($tenantId);
            $invite = $this->pending()->where('idtenant', $tenantId)->lockForUpdate()->find($id);
            abort_unless($invite instanceof stdClass, 404);
            $tenant = Tenant::findOrFail($tenantId);
            $email = (string) $invite->email;
            $this->assertSeats($tenant, $id);

            if ($user === null) {
                if (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                    throw ValidationException::withMessages(['email' => 'Masuk dengan akun yang diundang untuk melanjutkan.']);
                }
                abort_unless(isset($data['name'], $data['password'], $data['nomorhp']), 422);
                $user = User::create(['namalengkap' => $data['name'], 'nomorhp' => $data['nomorhp'], 'email' => $email, 'password' => $data['password'], 'role' => 'owner']);
            } else {
                $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                abort_if($user->isAdmin() || strcasecmp($user->email, $email) !== 0, 403);
            }

            // scope-bypass: invitation acceptance checks every membership of this verified user before choosing a tenant.
            $members = TenantMember::withoutGlobalScopes()->where('iduser', $user->id);
            $active = (clone $members)->whereNotNull('accepted_at')->whereNull('revoked_at')->lockForUpdate()->first();
            if ($active !== null) {
                throw ValidationException::withMessages(['email' => 'Akun ini sudah menjadi anggota bisnis.']);
            }

            $user->forceFill(['role' => 'owner', 'email_verified_at' => $user->email_verified_at ?? now()])->save();
            $members->updateOrCreate(['idtenant' => $tenantId, 'iduser' => $user->id], [
                'role' => $invite->role,
                'invited_by' => $invite->invited_by,
                'invited_at' => $invite->created_at,
                'accepted_at' => now(),
                'revoked_at' => null,
            ]);
            DB::table('tenant_invitations')->where('id', $id)->update(['accepted_at' => now()]);
            $this->record($tenantId, $user->id, (string) $invite->role, 'team.accepted', $id);

            return $user;
        });
    }

    public function change(Tenant $tenant, User $actor, int $id, ?string $role): void
    {
        DB::transaction(function () use ($tenant, $actor, $id, $role): void {
            ClaimSlots::lockTenant($tenant->id);
            $this->assertOwner($tenant, $actor);
            $member = TenantMember::where('idtenant', $tenant->id)->whereNotNull('accepted_at')->whereNull('revoked_at')->findOrFail($id);
            abort_if(TenantPermission::isOwnerRole((string) $member->role), 403);
            $member->update($role === null ? ['revoked_at' => now()] : ['role' => $role]);
            $this->record($tenant->id, $actor->id, 'pemilik', $role === null ? 'team.revoked' : 'team.role_changed', $id);
        });
    }

    public function revokeInvitation(Tenant $tenant, User $actor, int $id): void
    {
        DB::transaction(function () use ($tenant, $actor, $id): void {
            ClaimSlots::lockTenant($tenant->id);
            $this->assertOwner($tenant, $actor);
            abort_unless($this->pending()->where('idtenant', $tenant->id)->where('id', $id)->update(['revoked_at' => now()]) === 1, 404);
            $this->record($tenant->id, $actor->id, 'pemilik', 'team.invitation_revoked', $id);
        });
    }

    public function record(int $tenant, ?int $user, string $role, string $event, ?int $subject = null): void
    {
        DB::table('tenant_activity_logs')->insert([
            'idtenant' => $tenant, 'iduser' => $user, 'role' => $role, 'event' => $event,
            'subject_id' => $subject, 'created_at' => now(),
        ]);
    }

    private function assertOwner(Tenant $tenant, User $actor): void
    {
        abort_unless((int) $tenant->iduser === $actor->id, 403);
    }

    private function assertSeats(Tenant $tenant, ?int $excludingInvitation = null): void
    {
        // The public accept route has no tenant context, so both counts explicitly use the locked tenant.
        $members = DB::table('tenant_members')->where('idtenant', $tenant->id)->where('iduser', '!=', $tenant->iduser)
            ->whereNotNull('accepted_at')->whereNull('revoked_at')->count();
        $reservations = $this->pending()->where('idtenant', $tenant->id)
            ->when($excludingInvitation !== null, fn (Builder $query) => $query->where('id', '!=', $excludingInvitation))->count();

        // scope-bypass: public acceptance reads only the subscription of the locked invitation tenant.
        $subscription = Subscription::withoutGlobalScopes()->with('plan')->where('idtenant', $tenant->id)->latest('id')->first();
        if ($members + $reservations >= EntitlementRules::teamLimit($subscription)) {
            throw ValidationException::withMessages(['email' => 'Kursi tim pada paket ini sudah penuh.']);
        }
    }
}
