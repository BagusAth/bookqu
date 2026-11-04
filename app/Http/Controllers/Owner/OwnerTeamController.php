<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Team\ManageTeam;
use App\Domain\Subscription\EntitlementRules;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class OwnerTeamController extends Controller
{
    public function index(TenantContext $context, ManageTeam $team): View
    {
        $tenant = $context->tenant();
        abort_if($tenant === null, 403);

        return view('owner.team', [
            'members' => TenantMember::with('user')->whereNotNull('accepted_at')->whereNull('revoked_at')->orderBy('id')->get(),
            'invitations' => $team->pending()->where('idtenant', $tenant->id)->get(['id', 'email', 'role', 'expires_at']),
            'activity' => DB::table('tenant_activity_logs')->leftJoin('users', 'users.id', '=', 'tenant_activity_logs.iduser')
                ->where('tenant_activity_logs.idtenant', $tenant->id)->orderByDesc('tenant_activity_logs.id')
                ->paginate(20, ['tenant_activity_logs.*', 'users.namalengkap as actor_name']),
            'seatLimit' => EntitlementRules::teamLimit($tenant->subscription),
        ]);
    }

    public function invite(Request $request, TenantContext $context, ManageTeam $team): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:100'], 'role' => ['required', 'in:manajer,kasir']]);
        [$tenant, $user] = $this->owner($request, $context);
        $team->invite($tenant, $user, $data['email'], $data['role']);

        return back()->with('sukses', 'Undangan dikirim.');
    }

    public function change(Request $request, TenantContext $context, ManageTeam $team, int $memberId): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', 'in:manajer,kasir']]);
        [$tenant, $user] = $this->owner($request, $context);
        $team->change($tenant, $user, $memberId, $data['role']);

        return back()->with('sukses', 'Peran anggota diperbarui.');
    }

    public function revoke(Request $request, TenantContext $context, ManageTeam $team, int $memberId): RedirectResponse
    {
        [$tenant, $user] = $this->owner($request, $context);
        $team->change($tenant, $user, $memberId, null);

        return back()->with('sukses', 'Akses anggota dicabut.');
    }

    public function revokeInvitation(Request $request, TenantContext $context, ManageTeam $team, int $invitationId): RedirectResponse
    {
        [$tenant, $user] = $this->owner($request, $context);
        $team->revokeInvitation($tenant, $user, $invitationId);

        return back()->with('sukses', 'Undangan dibatalkan.');
    }

    /** @return array{Tenant, User} */
    private function owner(Request $request, TenantContext $context): array
    {
        $tenant = $context->tenant();
        $user = $request->user();
        abort_unless($tenant !== null && $user instanceof User, 403);

        return [$tenant, $user];
    }
}
